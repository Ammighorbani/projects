<?php
session_start();
require 'db.php'; // اتصال به دیتابیس

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// تبدیل اعداد فارسی به انگلیسی
function fa_to_en($str) {
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($fa, $en, $str);
}

// دریافت اطلاعات اولیه users_account
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone_number,age,country,city,province FROM users_account WHERE id=? LIMIT 1");
$stmt->bind_param('i',$user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();
$stmt->close();

// آپدیت یا اضافه کردن اطلاعات فرم
if($_SERVER['REQUEST_METHOD']=='POST') {
    // بخش 1: personal_information
    $first_name = $_POST['first_name'] ?? $user_info['first_name'];
    $last_name = $_POST['last_name'] ?? $user_info['last_name'];
    $age = fa_to_en($_POST['age'] ?? $user_info['age']);
    $email = $_POST['email'] ?? $user_info['email'];
    $phone = fa_to_en($_POST['phone_number'] ?? $user_info['phone_number']);
    $country = $_POST['country'] ?? $user_info['country'];
    $city = $_POST['city'] ?? $user_info['city'];
    $province = $_POST['province'] ?? $user_info['province'];
    $linkedin = $_POST['linkedin'] ?? '';
    $github = $_POST['github'] ?? '';
    $personal_stmt = $_POST['personal_stmt'] ?? '';

    $stmt = $conn->prepare("INSERT INTO personal_information (user_id,first_name,last_name,age,email,phone_number,country,city,province,linkedin,github,personal_stmt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
        first_name=?,last_name=?,age=?,email=?,phone_number=?,country=?,city=?,province=?,linkedin=?,github=?,personal_stmt=?");
    $stmt->bind_param('ississssssssississsssss',$user_id,$first_name,$last_name,$age,$email,$phone,$country,$city,$province,$linkedin,$github,$personal_stmt,
        $first_name,$last_name,$age,$email,$phone,$country,$city,$province,$linkedin,$github,$personal_stmt);
    $stmt->execute();
    $stmt->close();

    // بخش 2: professional_summary
    $summary = $_POST['professional_summary'] ?? '';
    $stmt = $conn->prepare("INSERT INTO professional_summary(user_id,summary) VALUES(?,?) ON DUPLICATE KEY UPDATE summary=?");
    $stmt->bind_param('iss',$user_id,$summary,$summary);
    $stmt->execute();
    $stmt->close();

    // بخش 3: work_experience (array)
    if(isset($_POST['work'])) {
        foreach($_POST['work'] as $work) {
            $title = $work['title'] ?? '';
            $company = $work['company'] ?? '';
            $city_prov = $work['city_prov'] ?? '';
            $start = $work['start'] ?? '';
            $end = $work['end'] ?? '';
            $tasks = implode('|',$work['tasks'] ?? []);
            if($title || $company || $city_prov) {
                $stmt = $conn->prepare("INSERT INTO work_experience(user_id,title,company,city_prov,start,end,tasks) VALUES(?,?,?,?,?,?,?)");
                $stmt->bind_param('issssss',$user_id,$title,$company,$city_prov,$start,$end,$tasks);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // بخش 4: education
    if(isset($_POST['education'])) {
        foreach($_POST['education'] as $edu) {
            $degree = $edu['degree'] ?? '';
            $univ = $edu['univ'] ?? '';
            $year = $edu['year'] ?? '';
            if($degree || $univ) {
                $stmt = $conn->prepare("INSERT INTO education(user_id,degree,univ,year) VALUES(?,?,?,?)");
                $stmt->bind_param('isss',$user_id,$degree,$univ,$year);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // بخش 5: skills
    if(isset($_POST['skills'])) {
        foreach($_POST['skills'] as $skill) {
            $name = $skill['name'] ?? '';
            $type = $skill['type'] ?? '';
            $level = $skill['level'] ?? '';
            if($name) {
                $stmt = $conn->prepare("INSERT INTO skills(user_id,name,type,level) VALUES(?,?,?,?)");
                $stmt->bind_param('isss',$user_id,$name,$type,$level);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // بخش 6: courses_certificates
    if(isset($_POST['courses'])) {
        foreach($_POST['courses'] as $course) {
            $name = $course['name'] ?? '';
            $year = $course['year'] ?? '';
            if($name) {
                $stmt = $conn->prepare("INSERT INTO courses_certificates(user_id,name,year) VALUES(?,?,?)");
                $stmt->bind_param('iss',$user_id,$name,$year);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // بخش 7: project
    if(isset($_POST['projects'])) {
        foreach($_POST['projects'] as $p) {
            $title = $p['title'] ?? '';
            $desc = $p['desc'] ?? '';
            if($title) {
                $stmt = $conn->prepare("INSERT INTO project(user_id,title,description) VALUES(?,?,?)");
                $stmt->bind_param('iss',$user_id,$title,$desc);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // بخش 8: passport_info
    $passport = $_POST['passport'] ?? '';
    $stmt = $conn->prepare("INSERT INTO passport_info(user_id,passport) VALUES(?,?) ON DUPLICATE KEY UPDATE passport=?");
    $stmt->bind_param('iss',$user_id,$passport,$passport);
    $stmt->execute();
    $stmt->close();

    // بخش 9: visa_info
    $visa = $_POST['visa_info'] ?? '';
    $stmt = $conn->prepare("INSERT INTO visa_info(user_id,visa_info) VALUES(?,?) ON DUPLICATE KEY UPDATE visa_info=?");
    $stmt->bind_param('iss',$user_id,$visa,$visa);
    $stmt->execute();
    $stmt->close();

    $success_msg = "رزومه شما با موفقیت ذخیره شد.";
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<title>فرم رزومه</title>
<link rel="stylesheet" href="/styles/resume_form.css">
</head>
<body>
<section class="resume-form-container">
    <h1>فرم رزومه</h1>
    <?php if(!empty($success_msg)) echo "<p class='success'>$success_msg</p>"; ?>
    <form method="post" id="resumeForm">
        <!-- بخش 1: اطلاعات فردی -->
        <section class="form-section">
            <h2>اطلاعات فردی</h2>
            <input type="text" name="first_name" placeholder="نام" value="<?php echo htmlspecialchars($user_info['first_name']); ?>" required>
            <input type="text" name="last_name" placeholder="نام خانوادگی" value="<?php echo htmlspecialchars($user_info['last_name']); ?>" required>
            <input type="text" name="age" placeholder="سن" value="<?php echo htmlspecialchars($user_info['age']); ?>" required>
            <input type="email" name="email" placeholder="ایمیل" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
            <input type="text" name="phone_number" placeholder="شماره تماس" value="<?php echo htmlspecialchars($user_info['phone_number']); ?>" required>
            <input type="text" name="country" placeholder="کشور" value="<?php echo htmlspecialchars($user_info['country']); ?>" required>
            <input type="text" name="city" placeholder="شهر" value="<?php echo htmlspecialchars($user_info['city']); ?>" required>
            <input type="text" name="province" placeholder="استان" value="<?php echo htmlspecialchars($user_info['province']); ?>" required>
            <input type="url" name="linkedin" placeholder="لینک LinkedIn" value="">
            <input type="url" name="github" placeholder="لینک GitHub" value="">
        </section>

        <!-- بخش 2: خلاصه حرفه ای -->
        <section class="form-section">
            <h2>خلاصه حرفه ای</h2>
            <textarea name="professional_summary" maxlength="3000" placeholder="خلاصه حرفه‌ای خود را وارد کنید"></textarea>
        </section>

        <!-- بخش 3: تجربه کاری (تکرارشونده با +) -->
        <section class="form-section">
            <h2>تجربه کاری <button type="button" id="addWorkBtn">+</button></h2>
            <div id="workContainer"></div>
        </section>

        <!-- بخش 4: تحصیلات -->
        <section class="form-section">
            <h2>تحصیلات <button type="button" id="addEduBtn">+</button></h2>
            <div id="eduContainer"></div>
        </section>

        <!-- بخش 5: مهارت‌ها -->
        <section class="form-section">
            <h2>مهارت‌ها <button type="button" id="addSkillBtn">+</button></h2>
            <div id="skillsContainer"></div>
        </section>

        <!-- بخش 6: دوره‌ها و گواهینامه‌ها -->
        <section class="form-section">
            <h2>دوره‌ها و گواهینامه‌ها <button type="button" id="addCourseBtn">+</button></h2>
            <div id="courseContainer"></div>
        </section>

        <!-- بخش 7: پروژه‌ها و افتخارات و داوطلبانه و علایق -->
        <section class="form-section">
            <h2>پروژه‌ها و افتخارات و علایق <button type="button" id="addProjectBtn">+</button></h2>
            <div id="projectContainer"></div>
        </section>

        <!-- بخش 8: شماره پاسپورت -->
        <section class="form-section">
            <h2>شماره پاسپورت</h2>
            <input type="text" name="passport" placeholder="شماره پاسپورت" required>
        </section>

        <!-- بخش 9: وضعیت ویزا -->
        <section class="form-section">
            <h2>وضعیت ویزا</h2>
            <textarea name="visa_info" maxlength="5000" placeholder="توضیحات وضعیت ویزا"></textarea>
        </section>

        <button type="submit" id="submitBtn">ذخیره رزومه</button>
    </form>
</section>

<script>
// JS برای اضافه کردن رکوردهای تکرارشونده
document.getElementById('addWorkBtn').addEventListener('click', function(){
    let container = document.getElementById('workContainer');
    let div = document.createElement('div');
    div.classList.add('dynamic-field');
    div.innerHTML = `
        <input type="text" name="work[][title]" placeholder="عنوان شغلی">
        <input type="text" name="work[][company]" placeholder="نام شرکت">
        <input type="text" name="work[][city_prov]" placeholder="شهر/استان">
        <input type="text" name="work[][start]" placeholder="تاریخ شروع">
        <input type="text" name="work[][end]" placeholder="تاریخ پایان">
        <textarea name="work[][tasks][]" placeholder="وظایف/دستاوردها (هر خط یک مورد)"></textarea>
    `;
    container.appendChild(div);
});

document.getElementById('addEduBtn').addEventListener('click', function(){
    let container = document.getElementById('eduContainer');
    let div = document.createElement('div');
    div.classList.add('dynamic-field');
    div.innerHTML = `
        <input type="text" name="education[][degree]" placeholder="نام رشته">
        <input type="text" name="education[][univ]" placeholder="نام دانشگاه">
        <input type="text" name="education[][year]" placeholder="سال فارغ التحصیلی">
    `;
    container.appendChild(div);
});

document.getElementById('addSkillBtn').addEventListener('click', function(){
    let container = document.getElementById('skillsContainer');
    let div = document.createElement('div');
    div.classList.add('dynamic-field');
    div.innerHTML = `
        <input type="text" name="skills[][name]" placeholder="نام مهارت">
        <select name="skills[][type]">
            <option value="hard">مهارت سخت</option>
            <option value="soft">مهارت نرم</option>
        </select>
        <select name="skills[][level]">
            <option value="beginner">مبتدی</option>
            <option value="intermediate">متوسط</option>
            <option value="advanced">حرفه‌ای</option>
        </select>
    `;
    container.appendChild(div);
});

document.getElementById('addCourseBtn').addEventListener('click', function(){
    let container = document.getElementById('courseContainer');
    let div = document.createElement('div');
    div.classList.add('dynamic-field');
    div.innerHTML = `
        <input type="text" name="courses[][name]" placeholder="نام دوره">
        <input type="text" name="courses[][year]" placeholder="سال دریافت">
    `;
    container.appendChild(div);
});

document.getElementById('addProjectBtn').addEventListener('click', function(){
    let container = document.getElementById('projectContainer');
    let div = document.createElement('div');
    div.classList.add('dynamic-field');
    div.innerHTML = `
        <input type="text" name="projects[][title]" placeholder="عنوان پروژه/افتخار/داوطلبانه/علایق">
        <textarea name="projects[][desc]" placeholder="توضیحات (حداکثر 3000 کاراکتر)"></textarea>
    `;
    container.appendChild(div);
});
</script>
</body>
</html>