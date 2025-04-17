<?php
$currentMinute = (int) date('i'); /* Поточна хвилина */
$currentSecond = (int) date('s'); /* Поточна секунда */
if ($currentMinute % 15 == 0) { /* Перевіряємо товари кожні 15 хвилин */
    require_once('../functions/mysql.php'); /* Підключення до БД */
    require_once('alerts.php');
}
if ($currentSecond % 5 == 0) { /* Кожні 5 секунд перевіряємо на зловживання доступом */
    require_once('../functions/mysql.php'); /* Підключення до БД */
    require_once('blacklist.php');
}
?>