<?php
$currentMinute = (int) date('i'); // Отримуємо хвилини у форматі числа
$currentSecond = (int) date('s'); // Отримуємо секунди у форматі числа
if ($currentMinute % 15 == 0) { // Кожні 15 хвилин при переході будь-куди та будь-кого оновлюємо алерти по низькій кількості товарів. 
    require_once('mysql.php'); // Підключення до бази
    require_once('autorun/alerts.php');
}
if ($currentSecond % 5 == 0) { // Перевірка на зловживання доступом кожну хвилину
    require_once('mysql.php'); // Підключення до бази
    require_once('autorun/blacklist.php');
}
?>