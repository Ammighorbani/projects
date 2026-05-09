<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>JobToGo - صفحه اصلی</title>
    <link rel="stylesheet" href="/styles/index.css">
</head>
<body>

<header class="header">
    <div class="logo">JobToGo</div>

    <a href="<?= $is_logged_in ? 'https://jobtogo.ir/user_panel.php' : 'https://jobtogo.ir/login.php' ?>" 
       class="login-btn">
        <?= $is_logged_in ? 'پنل کاربری' : 'ورود' ?>
    </a>
</header>

<section class="hero">
    <h1>یه فرصت تازه اون‌ور مرزها منتظرته!</h1>
    <p class="sub">
        ما در JobToGo مسیر پیدا کردن شغل خارج از کشور رو برات کاملاً ساده کردیم.
        با ارتباط مستقیم با کارفرماهای معتبر خارجی، فرصت‌های شغلی واقعی و متناسب با تخصص تو رو پیدا می‌کنیم.
    </p>

    <ul class="services">
        <li>پیدا کردن شغل مناسب رزومه و مهارت‌های شما</li>
        <li>هماهنگی ویزای کاری مستقیماً از طرف کارفرما</li>
        <li>پشتیبانی تا لحظه‌ای که قرارداد امضا بشه</li>
    </ul>

    <div class="info-box">
        ما فقط یه واسطه‌ی مطمئن هستیم  
        <br>
        بعد از تأیید رزومه‌ات توسط کارفرما، ارتباط مستقیم بین تو و کارفرما برقرار می‌شه و تمام مراحل بعدی
        (مصاحبه، قرارداد و ویزا) مستقیماً با خود کارفرما پیش می‌ره.
    </div>

    <p class="final-text">
        اگه دنبال یه شروع جدید و جدی در خارج از کشور هستی،
        <br>
        <strong>JobToGo اولین و بهترین قدمته.</strong>
    </p>

    <a href="https://jobtogo.ir" class="cta-btn">همین حالا شروع کن</a>
</section>

</body>
</html>
