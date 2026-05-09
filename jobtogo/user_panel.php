<?php
session_start();

// چک کردن لاگین
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل کاربری</title>
    <link rel="stylesheet" href="/styles/user_panel.css">

</head>
<body>
    <div class="circles">
        <div class="circle-1"></div>
        <div class="circle-2"></div>
    </div>
    <div class="container">
        <div class="panel">
            <h1>پنل کاربری</h1>
            <a class="card" href="https://jobtogo.ir/show_resume.php">نمایش رزومه فارسی</a>
            <a class="card" href="https://jobtogo.ir/show_resume_en.php/?lang=en">نمایش رزومه انگلیسی</a>
            <a class="card" href="https://jobtogo.ir/resume.php">تغییر / ساخت رزومه فارسی</a>
            <a class="card" href="https://jobtogo.ir/resume_en.php">تغییر / ساخت رزومه انگلیسی</a>
            <a class="card" href="https://jobtogo.ir/forget_password.php">تغییر رمز عبور</a>
            <a class="card logout" href="https://jobtogo.ir/logout.php">خروج از حساب</a>
        </div>
    </div>
</body>
</html>