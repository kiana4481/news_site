<?php
session_start();
session_unset(); // حذف تمام متغیرهای جلسه
session_destroy(); // پایان جلسه

header("Location: index.php"); // هدایت به صفحه ورود
exit();
?>
