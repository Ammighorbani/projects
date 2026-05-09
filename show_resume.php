<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// تشخیص زبان
$is_english = isset($_GET['lang']) && $_GET['lang'] === 'en';

// تابع ترجمه
function t($fa, $en) {
    global $is_english;
    return $is_english ? $en : $fa;
}

// اطلاعات کاربر
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, country, city, province FROM users_account WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$full_name = $user_info['first_name'] . ' ' . $user_info['last_name'];

// بقیه اطلاعات (همون قبلی)
$stmt = $conn->prepare("SELECT linkedin, github, personal_stmt FROM personal_information WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$personal = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$stmt = $conn->prepare("SELECT summary FROM professional_summary WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$summary = $stmt->get_result()->fetch_assoc()['summary'] ?? '';
$stmt->close();

// تجربه کاری
$work = [];
$stmt = $conn->prepare("SELECT *, start_date AS start, end_date AS end FROM work_experience WHERE user_id=? ORDER BY job_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['tasks'] = array_filter([$row['bullet1'],$row['bullet2'],$row['bullet3'],$row['bullet4'],$row['bullet5']]);
    $work[] = $row;
}
$stmt->close();

// تحصیلات
$education = [];
$stmt = $conn->prepare("SELECT * FROM education WHERE user_id=? ORDER BY education_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (trim($row['university_name'] ?? '') || trim($row['field_of_study'] ?? '')) {
        $education[] = $row;
    }
}
$stmt->close();

// مهارت‌ها
$stmt = $conn->prepare("SELECT * FROM skills WHERE user_id=? AND skill_name != '' ORDER BY skill_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
$skills = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// دوره‌ها
$stmt = $conn->prepare("SELECT *, received_date AS date FROM courses_certificates WHERE user_id=? AND course_name != '' ORDER BY course_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
$courses = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// پروژه‌ها
$stmt = $conn->prepare("SELECT * FROM project WHERE user_id=? AND title != '' ORDER BY project_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
$projects = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// زبان‌ها
$stmt = $conn->prepare("SELECT language_name, proficiency FROM languages WHERE user_id=? ORDER BY language_index");
$stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
$languages = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// پاسپورت و ویزا
$stmt = $conn->prepare("SELECT passport_number FROM passport_info WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$passport = $stmt->get_result()->fetch_assoc()['passport_number'] ?? '';
$stmt->close();

$stmt = $conn->prepare("SELECT description FROM visa_info WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$visa = $stmt->get_result()->fetch_assoc()['description'] ?? '';
$stmt->close();
?>

<!DOCTYPE html>
<html lang="<?= $is_english ? 'en' : 'fa' ?>" dir="<?= $is_english ? 'ltr' : 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= t('نمایش رزومه', 'Resume') ?> - <?= htmlspecialchars($full_name) ?></title>
    <style>
        body {background:#1e1e2f;color:#f5f6fa;font-family:<?= $is_english ? 'Arial,Helvetica,sans-serif' : 'Tahoma' ?>;padding:20px;}
        .c {max-width:1000px;margin:40px auto;background:rgba(40,40,60,0.95);padding:40px;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.7);}
        h1 {text-align:center;color:#ff9f43;font-size:34px;margin-bottom:30px;}
        .btn {padding:18px 40px;font-size:20px;border-radius:14px;text-decoration:none;display:inline-block;margin:15px;}
        .pdf {background:#27ae60;color:#fff;}
        .edit {background:#3498db;color:#fff;}
        .eng {background:#f39c12;color:#fff;}
        .print-btn {background:#27ae60;color:#fff;} /* رنگ سبز مثل دکمه قبلی PDF */
        .section {background:#2c2c40;padding:30px;margin:30px 0;border-radius:14px;}
        .section h2 {color:#ff9f43;border-bottom:2px solid #ff9f43;padding-bottom:10px;font-size:24px;margin-top:0;}
        .job,.edu,.proj,.skill-item,.course-item,.lang-item {background:#3a3a5a;padding:25px;margin:18px 0;border-radius:12px;}
        ul {padding-right:25px;}
        li {margin:10px 0;}
        .location {color:#bdc3c7;}

        /* تنظیمات پرینت — فقط محتوای رزومه چاپ بشه */
        @media print {
            body { background: white; color: black; padding: 0; margin: 0; }
            body * { visibility: hidden; }
            .c, .c * { visibility: visible; }
            .c {
                position: absolute;
                left: 0; top: 0; right: 0;
                background: white !important;
                box-shadow: none;
                border-radius: 0;
                padding: 50px;
                margin: 0;
                max-width: none;
            }
            .btn { display: none !important; }
            h1, h2 { color: #e67e22 !important; }
            a { color: #000 !important; text-decoration: underline; }
            .section { background: transparent !important; padding: 20px 0; margin: 35px 0; }
            .job, .edu, .proj, .skill-item, .course-item, .lang-item {
                background: #f9f9f9 !important;
                border: 1px solid #eee;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                padding: 20px;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
<div class="c">
    <h1><?= t('رزومه ', 'Resume of ') ?><?= htmlspecialchars($full_name) ?></h1>

    <div style="text-align:center;margin:40px 0;">
        <!-- فقط دکمه پرینت / ذخیره PDF (جای دکمه قبلی) -->
        <a href="javascript:window.print()" class="btn pdf print-btn">
            <?= t('ذخیره رزومه به صورت PDF', 'Save Resume as PDF') ?>
        </a>

        <a href="resume.php" class="btn edit"><?= t('ویرایش رزومه', 'Edit Resume') ?></a>

        <?php if(!$is_english): ?>
            <a href="https://jobtogo.ir/show_resume_en.php?lang=en" class="btn eng">English Version</a>
        <?php else: ?>
            <a href="https://jobtogo.ir/show_resume.php?lang=fa" class="btn eng">نسخه فارسی</a>
        <?php endif; ?>
    </div>

    <!-- بقیه محتوا دقیقاً مثل قبل -->
    <!-- اطلاعات فردی -->
    <div class="section">
        <h2><?= t('اطلاعات فردی', 'Personal Information') ?></h2>
        <p><strong><?= t('نام کامل:', 'Full Name:') ?></strong> <?= htmlspecialchars($full_name) ?></p>
        <?php if(!empty($user_info['email'])): ?>
            <p><strong><?= t('ایمیل:', 'Email:') ?></strong> <a href="mailto:<?= htmlspecialchars($user_info['email']) ?>" style="color:#ff9f43;"><?= htmlspecialchars($user_info['email']) ?></a></p>
        <?php endif; ?>
        <?php if(!empty($user_info['phone_number'])): ?>
            <p><strong><?= t('تلفن:', 'Phone:') ?></strong> <?= htmlspecialchars($user_info['phone_number']) ?></p>
        <?php endif; ?>
        <?php 
        $location_parts = array_filter([$user_info['city'] ?? '', $user_info['province'] ?? '', $user_info['country'] ?? '']);
        if($location_parts): ?>
            <p><strong><?= t('مکان:', 'Location:') ?></strong> <span class="location"><?= htmlspecialchars(implode(', ', $location_parts)) ?></span></p>
        <?php endif; ?>
        <?php if(!empty($personal['linkedin'])): ?>
            <p><strong>LinkedIn:</strong> <a href="<?= htmlspecialchars($personal['linkedin']) ?>" target="_blank" style="color:#ff9f43;">Link</a></p>
        <?php endif; ?>
        <?php if(!empty($personal['github'])): ?>
            <p><strong>GitHub:</strong> <a href="<?= htmlspecialchars($personal['github']) ?>" target="_blank" style="color:#ff9f43;">Link</a></p>
        <?php endif; ?>
    </div>

    <!-- بقیه بخش‌ها (خلاصه، تجربه کاری، تحصیلات، زبان‌ها، مهارت‌ها، پروژه‌ها و ...) دقیقاً مثل کد قبلیت -->
    <?php if($summary): ?>
    <div class="section"><h2><?= t('خلاصه حرفه‌ای', 'Professional Summary') ?></h2><p><?= nl2br(htmlspecialchars($summary)) ?></p></div>
    <?php endif; ?>

    <?php if($work): ?>
    <div class="section"><h2><?= t('تجربه کاری', 'Work Experience') ?></h2>
        <?php foreach($work as $w): ?>
        <div class="job">
            <h3><?= htmlspecialchars($w['job_title']) ?></h3>
            <p><strong><?= htmlspecialchars($w['company_name']) ?></strong> — <?= htmlspecialchars($w['company_city_province']) ?></p>
            <p style="color:#ff9f43;"><?= htmlspecialchars($w['start']) ?> <?= t('تا', 'to') ?> <?= htmlspecialchars($w['end'] ?: t('تاکنون', 'Present')) ?></p>
            <?php if($w['tasks']): ?><ul><?php foreach($w['tasks'] as $t): if($t): ?><li><?= htmlspecialchars($t) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ... بقیه بخش‌ها دقیقاً مثل قبل ... -->

    <?php if($education): ?>
    <div class="section"><h2><?= t('تحصیلات', 'Education') ?></h2>
        <?php foreach($education as $e): ?>
        <div class="edu">
            <h3><?= htmlspecialchars($e['university_name']) ?></h3>
            <p><?= htmlspecialchars($e['field_of_study']) ?> — <?= t('معدل:', 'GPA:') ?> <?= htmlspecialchars($e['gpa'] ?? '—') ?></p>
            <p style="color:#ff9f43;"><?= t('سال فارغ‌التحصیلی:', 'Graduated:') ?> <?= htmlspecialchars($e['graduation_year']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if($languages): ?>
    <div class="section">
        <h2><?= t('زبان‌ها', 'Languages') ?></h2>
        <div style="display:flex;flex-wrap:wrap;gap:20px;">
            <?php foreach($languages as $lang): ?>
            <div class="lang-item">
                <strong style="font-size:19px;"><?= htmlspecialchars($lang['language_name']) ?></strong><br>
                <span style="color:#ff9f43;font-size:16px;">
                    <?= $is_english ? ['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced','native'=>'Native'][$lang['proficiency']] : ['beginner'=>'مبتدی','intermediate'=>'متوسط','advanced'=>'حرفه‌ای','native'=>'بومی'][$lang['proficiency']] ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($skills): ?>
    <div class="section"><h2><?= t('مهارت‌ها', 'Skills') ?></h2>
        <div style="display:flex;flex-wrap:wrap;gap:20px;">
            <?php foreach($skills as $s): ?>
            <div class="skill-item">
                <strong><?= htmlspecialchars($s['skill_name']) ?></strong><br>
                <small style="color:#ff9f43;">
                    <?= $is_english ? ucfirst($s['skill_type']) : ($s['skill_type']=='hard'?'سخت':'نرم') ?> • 
                    <?= $is_english ? ucfirst($s['skill_level']) : ['beginner'=>'مبتدی','intermediate'=>'متوسط','advanced'=>'حرفه‌ای'][$s['skill_level']] ?>
                </small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($courses): ?>
    <div class="section"><h2><?= t('دوره‌ها و گواهینامه‌ها', 'Courses & Certificates') ?></h2>
        <?php foreach($courses as $c): ?>
        <div class="course-item"><h4><?= htmlspecialchars($c['course_name']) ?></h4><p style="color:#ff9f43;"><?= htmlspecialchars($c['date']) ?></p></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if($projects): ?>
    <div class="section"><h2><?= t('پروژه‌ها', 'Projects') ?></h2>
        <?php foreach($projects as $p): ?>
        <div class="proj">
            <h3><?= htmlspecialchars($p['title']) ?></h3>
            <p><em><?= $is_english ? ucfirst($p['type']) : ['project'=>'شخصی','freelance'=>'فریلنس','open-source'=>'اوپن‌سورس','university'=>'دانشگاهی'][$p['type']] ?></em></p>
            <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if($passport || $visa): ?>
    <div class="section"><h2><?= t('اطلاعات مهاجرت', 'Immigration Info') ?></h2>
        <?php if($passport): ?><p><strong><?= t('شماره پاسپورت:', 'Passport No:') ?></strong> <?= htmlspecialchars($passport) ?></p><?php endif; ?>
        <?php if($visa): ?><p><strong><?= t('وضعیت ویزا:', 'Visa Status:') ?></strong> <?= nl2br(htmlspecialchars($visa)) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>