<?php
session_start();

// حذف تمام سشن‌ها
session_unset();

// نابود کردن سشن
session_destroy();

// ریدایرکت به صفحه لاگین (یا جایی که خواستی)
header("Location: login.php?logout=1");
exit();
?>
