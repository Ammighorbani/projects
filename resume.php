<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// چک کردن وضعیت پرداخت کاربر
$stmt = $conn->prepare("SELECT is_pay FROM users_account WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_pay_status = $result->fetch_assoc();
$stmt->close();

$is_paid = ($user_pay_status && $user_pay_status['is_pay'] == 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // اگر درخواست POST هست و کاربر پرداخت نکرده، اجازه ذخیره نده
    if (!$is_paid) {
        echo '<script>
                alert("برای ذخیره رزومه، ابتدا باید اشتراک خود را پرداخت کنید.");
                window.location.href = "https://jobtogo.ir/pay.php";
              </script>';
        exit;
    }
    // ... بقیه کد ذخیره‌سازی (همان که قبلاً داشتی)
}

function fa_to_en($str) {
    if (!$str) return null;
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    return (int)str_replace($fa, $en, trim($str));
}

// بارگذاری همه اطلاعات
$user_info = $personal = $summary = $passport = $visa = [];
$work = $education = $skills = $courses = $projects = [];

// اطلاعات کاربر
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone_number,age,country,city,province FROM users_account WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// اطلاعات شخصی
$stmt = $conn->prepare("SELECT linkedin, github, personal_stmt FROM personal_information WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$personal = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// خلاصه حرفه‌ای
$stmt = $conn->prepare("SELECT summary FROM professional_summary WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$summary = $row ? $row['summary'] : '';
$stmt->close();

// پاسپورت
$stmt = $conn->prepare("SELECT passport_number FROM passport_info WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$passport = $row ? $row['passport_number'] : '';
$stmt->close();

// ویزا
$stmt = $conn->prepare("SELECT description FROM visa_info WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$visa = $row ? $row['description'] : '';
$stmt->close();

// تجربه کاری
$stmt = $conn->prepare("SELECT *, start_date AS start, end_date AS end FROM work_experience WHERE user_id=? ORDER BY job_index");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tasks = array_filter([$row['bullet1'],$row['bullet2'],$row['bullet3'],$row['bullet4'],$row['bullet5']]);
    $row['tasks_text'] = implode("\n", $tasks);
    $work[] = $row;
}
$stmt->close();

// تحصیلات
$stmt = $conn->prepare("SELECT * FROM education WHERE user_id=? ORDER BY education_index");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $education[] = $row;
$stmt->close();

// مهارت‌ها
$stmt = $conn->prepare("SELECT * FROM skills WHERE user_id=? ORDER BY skill_index");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $skills[] = $row;
$stmt->close();

// دوره‌ها
$stmt = $conn->prepare("SELECT *, received_date AS date FROM courses_certificates WHERE user_id=? ORDER BY course_index");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $courses[] = $row;
$stmt->close();

// پروژه‌ها
$stmt = $conn->prepare("SELECT * FROM project WHERE user_id=? ORDER BY project_index");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $projects[] = $row;
$stmt->close();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // اطلاعات فردی + لینک‌ها
    $age = fa_to_en($_POST['age'] ?? '');
    $phone = fa_to_en($_POST['phone_number'] ?? '');

    $stmt = $conn->prepare("INSERT INTO personal_information 
        (user_id, first_name, last_name, age, email, phone_number, country, city, province, linkedin, github, personal_stmt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        first_name=VALUES(first_name), last_name=VALUES(last_name), age=VALUES(age), email=VALUES(email),
        phone_number=VALUES(phone_number), country=VALUES(country), city=VALUES(city), province=VALUES(province),
        linkedin=VALUES(linkedin), github=VALUES(github), personal_stmt=VALUES(personal_stmt)");
    $stmt->bind_param("isssssssssss",
        $user_id, $_POST['first_name'], $_POST['last_name'], $age, $_POST['email'], $phone,
        $_POST['country'], $_POST['city'], $_POST['province'], $_POST['linkedin'], $_POST['github'], $_POST['personal_stmt']
    );
    $stmt->execute();
    $stmt->close();

    // خلاصه + پاسپورت + ویزا
    $stmt = $conn->prepare("INSERT INTO professional_summary (user_id, summary) VALUES (?, ?) ON DUPLICATE KEY UPDATE summary=?");
    $stmt->bind_param("iss", $user_id, $_POST['summary'], $_POST['summary']);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO passport_info (user_id, passport_number) VALUES (?, ?) ON DUPLICATE KEY UPDATE passport_number=?");
    $stmt->bind_param("iss", $user_id, $_POST['passport'], $_POST['passport']);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO visa_info (user_id, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description=?");
    $stmt->bind_param("iss", $user_id, $_POST['visa'], $_POST['visa']);
    $stmt->execute();

    // تابع ذخیره عمومی (همه تاریخ‌ها متن ساده!)
    function saveList($key, $table, $index, $fields, $extras = []) {
        global $conn, $user_id;
        $existing = $conn->query("SELECT id FROM `$table` WHERE user_id=$user_id")->fetch_all(MYSQLI_ASSOC);
        $existing_ids = array_column($existing, 'id');
        $used = [];

        if (!empty($_POST[$key]) && is_array($_POST[$key])) {
            foreach ($_POST[$key] as $item) {
                $id = !empty($item['id']) ? (int)$item['id'] : 0;
                $vals = [];
                foreach ($fields as $f) $vals[] = $item[$f] ?? '';

                if ($table === 'work_experience') {
                    $tasks = array_slice(array_filter(array_map('trim', explode("\n", $item['tasks']??''))), 0, 5);
                    $vals = array_merge($vals, [$item['start_date']??'', $item['end_date']??'', $tasks[0]??'', $tasks[1]??'', $tasks[2]??'', $tasks[3]??'', $tasks[4]??'']);
                }

                if ($id > 0) {
                    $sets = implode('=?, ', array_merge($fields, $extras)).'=?';
                    $sql = "UPDATE `$table` SET $sets WHERE id=? AND user_id=?";
                    $types = str_repeat('s', count($vals)).'ii';
                    $params = array_merge($vals, [$id, $user_id]);
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $stmt->close();
                    $used[] = $id;
                } else {
                    $next = $conn->query("SELECT COALESCE(MAX($index),0)+1 FROM `$table` WHERE user_id=$user_id")->fetch_array()[0] ?? 1;
                    $cols = "user_id, $index, ".implode(',', array_merge($fields, $extras));
                    $ph = "?, ?,".str_repeat('?,', count($vals)-1).'?';
                    $sql = "INSERT INTO `$table` ($cols) VALUES ($ph)";
                    $types = "ii".str_repeat('s', count($vals));
                    $params = array_merge([$user_id, $next], $vals);
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $stmt->close();
                    $used[] = $conn->insert_id;
                }
            }
        }

        $deleted = array_diff($existing_ids, $used);
        if ($deleted) {
            $in = str_repeat('?,', count($deleted)-1).'?';
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id IN ($in) AND user_id=?");
            $types = str_repeat('i', count($deleted)+1);
            $params = array_merge($deleted, [$user_id]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }
    }

    saveList('work',      'work_experience',       'job_index',       ['job_title','company_name','company_city_province'], ['start_date','end_date','bullet1','bullet2','bullet3','bullet4','bullet5']);
    saveList('education', 'education',             'education_index', ['university_name','field_of_study','graduation_year','gpa','projects','honors'], []);
    saveList('skills',    'skills',                'skill_index',     ['skill_name','skill_type','skill_level'], []);
    saveList('courses',   'courses_certificates',  'course_index',    ['course_name','received_date'], []);
    saveList('projects',  'project',               'project_index',   ['type','title','description'], []);
    saveList('languages', 'languages', 'language_index', ['language_name','proficiency'], []);

    $success = "رزومه با موفقیت ذخیره شد!";
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>فرم رزومه</title>
<link rel="stylesheet" href="styles/resume.css">
</head>
<body>
<div class="c">
    <h1>فرم رزومه</h1>
    <?php if($success): ?><div class="success"><?=$success?></div><?php endif; ?>

    <form method="post">
        <div class="s"><h2>اطلاعات فردی</h2>
            <input type="text" name="first_name" value="<?=htmlspecialchars($user_info['first_name']??'')?>" placeholder="نام" required>
            <input type="text" name="last_name" value="<?=htmlspecialchars($user_info['last_name']??'')?>" placeholder="نام خانوادگی" required>
            <input type="text" name="age" value="<?=htmlspecialchars($user_info['age']??'')?>" placeholder="سن">
            <input type="email" name="email" value="<?=htmlspecialchars($user_info['email']??'')?>" placeholder="ایمیل" required>
            <input type="text" name="phone_number" value="<?=htmlspecialchars($user_info['phone_number']??'')?>" placeholder="موبایل">
            <input type="text" name="country" value="<?=htmlspecialchars($user_info['country']??'')?>" placeholder="کشور">
            <input type="text" name="city" value="<?=htmlspecialchars($user_info['city']??'')?>" placeholder="شهر">
            <input type="text" name="province" value="<?=htmlspecialchars($user_info['province']??'')?>" placeholder="استان">
            <input type="url" name="linkedin" value="<?=htmlspecialchars($personal['linkedin']??'')?>" placeholder="لینکدین">
            <input type="url" name="github" value="<?=htmlspecialchars($personal['github']??'')?>" placeholder="گیت‌هاب">
            <textarea name="personal_stmt" placeholder="درباره خودتان"><?=htmlspecialchars($personal['personal_stmt']??'')?></textarea>
        </div>

        <div class="s"><h2>خلاصه حرفه‌ای</h2>
            <textarea name="summary" placeholder="خلاصه حرفه‌ای"><?=htmlspecialchars($summary)?></textarea>
        </div>

        <div class="s"><h2>اطلاعات پاسپورت و ویزا</h2>
            <input type="text" name="passport" value="<?=htmlspecialchars($passport)?>" placeholder="شماره پاسپورت">
            <textarea name="visa" placeholder="وضعیت ویزا"><?=htmlspecialchars($visa)?></textarea>
        </div>

        <div class="s"><h2>تجربه کاری <button type="button" class="add" onclick="addWork()">+ اضافه کردن</button></h2>
            <div id="workContainer">
                <?php foreach($work as $i => $w): ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="work[<?=$i?>][id]" value="<?=$w['id']?>">
                    <input type="text" name="work[<?=$i?>][job_title]" value="<?=htmlspecialchars($w['job_title']??'')?>" placeholder="عنوان شغلی">
                    <input type="text" name="work[<?=$i?>][company_name]" value="<?=htmlspecialchars($w['company_name']??'')?>" placeholder="نام شرکت">
                    <input type="text" name="work[<?=$i?>][company_city_province]" value="<?=htmlspecialchars($w['company_city_province']??'')?>" placeholder="شهر/استان">
                    <input type="text" name="work[<?=$i?>][start_date]" value="<?=htmlspecialchars($w['start']??'')?>" placeholder="مثلاً ۱۴۰۲-۰۵ یا 2021-03">
                    <input type="text" name="work[<?=$i?>][end_date]" value="<?=htmlspecialchars($w['end']??'')?>" placeholder="مثلاً تاکنون یا ۱۴۰۴-۰۲">
                    <textarea name="work[<?=$i?>][tasks]" placeholder="وظایف - هر خط یکی"><?=htmlspecialchars($w['tasks_text']??'')?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="s"><h2>تحصیلات <button type="button" class="add" onclick="addEdu()">+ اضافه کردن</button></h2>
            <div id="eduContainer">
                <?php foreach($education as $i => $e): ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="education[<?=$i?>][id]" value="<?=$e['id']?>">
                    <input type="text" name="education[<?=$i?>][university_name]" value="<?=htmlspecialchars($e['university_name']??'')?>" placeholder="دانشگاه">
                    <input type="text" name="education[<?=$i?>][field_of_study]" value="<?=htmlspecialchars($e['field_of_study']??'')?>" placeholder="رشته">
                    <input type="text" name="education[<?=$i?>][graduation_year]" value="<?=htmlspecialchars($e['graduation_year']??'')?>" placeholder="سال فارغ‌التحصیلی (مثلاً ۱۴۰۳)">
                    <input type="text" name="education[<?=$i?>][gpa]" value="<?=htmlspecialchars($e['gpa']??'')?>" placeholder="معدل">
                    <textarea name="education[<?=$i?>][projects]" placeholder="پروژه‌ها"><?=htmlspecialchars($e['projects']??'')?></textarea>
                    <textarea name="education[<?=$i?>][honors]" placeholder="افتخارات"><?=htmlspecialchars($e['honors']??'')?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="s"><h2>مهارت‌ها <button type="button" class="add" onclick="addSkill()">+ اضافه کردن</button></h2>
            <div id="skillContainer">
                <?php foreach($skills as $i => $s): ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="skills[<?=$i?>][id]" value="<?=$s['id']?>">
                    <input type="text" name="skills[<?=$i?>][skill_name]" value="<?=htmlspecialchars($s['skill_name']??'')?>" placeholder="نام مهارت">
                    <select name="skills[<?=$i?>][skill_type]">
                        <option value="hard" <?=($s['skill_type']??'')=='hard'?'selected':''?>>سخت</option>
                        <option value="soft" <?=($s['skill_type']??'')=='soft'?'selected':''?>>نرم</option>
                    </select>
                    <select name="skills[<?=$i?>][skill_level]">
                        <option value="beginner" <?=($s['skill_level']??'')=='beginner'?'selected':''?>>مبتدی</option>
                        <option value="intermediate" <?=($s['skill_level']??'')=='intermediate'?'selected':''?>>متوسط</option>
                        <option value="advanced" <?=($s['skill_level']??'')=='advanced'?'selected':''?>>حرفه‌ای</option>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="s"><h2>دوره‌ها و گواهینامه‌ها <button type="button" class="add" onclick="addCourse()">+ اضافه کردن</button></h2>
            <div id="courseContainer">
                <?php foreach($courses as $i => $c): ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="courses[<?=$i?>][id]" value="<?=$c['id']?>">
                    <input type="text" name="courses[<?=$i?>][course_name]" value="<?=htmlspecialchars($c['course_name']??'')?>" placeholder="نام دوره">
                    <input type="text" name="courses[<?=$i?>][received_date]" value="<?=htmlspecialchars($c['date']??'')?>" placeholder="مثلاً ۱۴۰۳ یا 2023-06">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

                <!-- === بخش زبان‌ها === -->
        <div class="s">
            <h2>زبان‌ها <button type="button" class="add" onclick="addLanguage()">+ اضافه کردن</button></h2>
            <div id="languageContainer">
                <?php 
                $stmt = $conn->prepare("SELECT * FROM languages WHERE user_id=? ORDER BY language_index");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $langCount = 0;
                while ($l = $res->fetch_assoc()): 
                ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="languages[<?=$langCount?>][id]" value="<?=$l['id']?>">

                    <input type="text" 
                           name="languages[<?=$langCount?>][language_name]" 
                           value="<?=htmlspecialchars($l['language_name'] ?? '')?>" 
                           placeholder="نام زبان: English, Persian, French..." 
                           oninput="this.value = this.value ? this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase() : ''"
                           style="width: 48%; margin-left: 4px;">

                    <select name="languages[<?=$langCount?>][proficiency]" style="width: 48%; padding: 14px; border-radius: 8px;">
                        <option value="beginner"     <?=($l['proficiency']??'')=='beginner'?'selected':''?>>Beginner</option>
                        <option value="intermediate" <?=($l['proficiency']??'')=='intermediate'?'selected':''?>>Intermediate</option>
                        <option value="advanced"     <?=($l['proficiency']??'')=='advanced'?'selected':''?>>Advanced</option>
                        <option value="native"       <?=($l['proficiency']??'')=='native'?'selected':''?>>Native</option>
                    </select>
                </div>
                <?php 
                $langCount++;
                endwhile; 
                ?>
            </div>
        </div>
        <!-- === پایان بخش زبان‌ها === -->

        <div class="s"><h2>پروژه‌ها <button type="button" class="add" onclick="addProject()">+ اضافه کردن</button></h2>
            <div id="projectContainer">
                <?php foreach($projects as $i => $p): ?>
                <div class="item">
                    <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
                    <input type="hidden" name="projects[<?=$i?>][id]" value="<?=$p['id']?>">
                    <select name="projects[<?=$i?>][type]">
                        <option value="project" <?=($p['type']??'')=='project'?'selected':''?>>شخصی</option>
                        <option value="freelance" <?=($p['type']??'')=='freelance'?'selected':''?>>فریلنس</option>
                        <option value="open-source" <?=($p['type']??'')=='open-source'?'selected':''?>>ا��پن سورس</option>
                        <option value="university" <?=($p['type']??'')=='university'?'selected':''?>>دانشگاهی</option>
                    </select>
                    <input type="text" name="projects[<?=$i?>][title]" value="<?=htmlspecialchars($p['title']??'')?>" placeholder="عنوان پروژه">
                    <textarea name="projects[<?=$i?>][description]" placeholder="توضیحات"><?=htmlspecialchars($p['description']??'')?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" onclick="checkPaymentAndSubmit()">ذخیره کامل رزومه</button>
    </form>
</div>

<script>
let c = {work:<?=count($work)?>, edu:<?=count($education)?>, skill:<?=count($skills)?>, course:<?=count($courses)?>, project:<?=count($projects)?>};

function addWork() { let n=c.work++; document.getElementById('workContainer').insertAdjacentHTML('beforeend', `<div class="item"><button type="button" class="del" onclick="this.parentNode.remove()">حذف</button><input type="hidden" name="work[${n}][id]" value=""><input type="text" name="work[${n}][job_title]" placeholder="عنوان"><input type="text" name="work[${n}][company_name]" placeholder="شرکت"><input type="text" name="work[${n}][company_city_province]" placeholder="شهر"><input type="text" name="work[${n}][start_date]" placeholder="1402-05"><input type="text" name="work[${n}][end_date]" placeholder="تاکنون"><textarea name="work[${n}][tasks]" placeholder="وظایف"></textarea></div>`); }

function addEdu() { let n=c.edu++; document.getElementById('eduContainer').insertAdjacentHTML('beforeend', `<div class="item"><button type="button" class="del" onclick="this.parentNode.remove()">حذف</button><input type="hidden" name="education[${n}][id]" value=""><input type="text" name="education[${n}][university_name]" placeholder="دانشگاه"><input type="text" name="education[${n}][field_of_study]" placeholder="رشته"><input type="text" name="education[${n}][graduation_year]" placeholder="1403"><input type="text" name="education[${n}][gpa]" placeholder="معدل"><textarea name="education[${n}][projects]"></textarea><textarea name="education[${n}][honors]"></textarea></div>`); }

function addSkill() { let n=c.skill++; document.getElementById('skillContainer').insertAdjacentHTML('beforeend', `<div class="item"><button type="button" class="del" onclick="this.parentNode.remove()">حذف</button><input type="hidden" name="skills[${n}][id]" value=""><input type="text" name="skills[${n}][skill_name]" placeholder="مهارت"><select name="skills[${n}][skill_type]"><option value="hard">سخت</option><option value="soft">نرم</option></select><select name="skills[${n}][skill_level]"><option value="beginner">مبتدی</option><option value="intermediate">متوسط</option><option value="advanced">حرفه‌ای</option></select></div>`); }

function addCourse() { let n=c.course++; document.getElementById('courseContainer').insertAdjacentHTML('beforeend', `<div class="item"><button type="button" class="del" onclick="this.parentNode.remove()">حذف</button><input type="hidden" name="courses[${n}][id]" value=""><input type="text" name="courses[${n}][course_name]" placeholder="نام دوره"><input type="text" name="courses[${n}][received_date]" placeholder="1403 یا 2023"></div>`); }

function addProject() { let n=c.project++; document.getElementById('projectContainer').insertAdjacentHTML('beforeend', `<div class="item"><button type="button" class="del" onclick="this.parentNode.remove()">حذف</button><input type="hidden" name="projects[${n}][id]" value=""><select name="projects[${n}][type]"><option value="project">شخصی</option><option value="freelance">فریلنس</option><option value="open-source">اوپن سورس</option><option value="university">دانشگاهی</option></select><input type="text" name="projects[${n}][title]" placeholder="عنوان"><textarea name="projects[${n}][description]"></textarea></div>`); }


let langCount = <?= $langCount ?>;

function addLanguage() {
    let n = langCount++;
    let html = `
        <div class="item">
            <button type="button" class="del" onclick="this.parentNode.remove()">حذف</button>
            <input type="hidden" name="languages[${n}][id]" value="">
            <input type="text" 
                   name="languages[${n}][language_name]" 
                   placeholder="نام زبان: English, Persian, French..." 
                   oninput="this.value = this.value ? this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase() : ''"
                   style="width: 48%; margin-left: 4px;">
            <select name="languages[${n}][proficiency]" style="width: 48%; padding: 14px; border-radius: 8px;">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="native">Native</option>
            </select>
        </div>`;
    document.getElementById('languageContainer').insertAdjacentHTML('beforeend', html);
}
</script>

<script>
function checkPaymentAndSubmit() {
    <?php if ($is_paid): ?>
        // اگر پرداخت کرده، فرم رو سابمیت کن
        document.querySelector('form').submit();
    <?php else: ?>
        // اگر پرداخت نکرده، هشدار بده و ریدایرکت کن
        alert("برای ذخیره رزومه، ابتدا باید اشتراک خود را پرداخت کنید.");
        window.location.href = "https://jobtogo.ir/pay.php";
    <?php endif; ?>
}
</script>

</body>
</html>