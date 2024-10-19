<?php
session_start(); // Початок сесії
require_once('header.php'); // Верхня частина сайту
require_once('../php/mysql.php'); // Підключення до БД
require_once('../php/output.php'); // Підключення до БД

if ($id) {/* Якщо є id -> відобразити сторінку */
    $product->displayProduct($id);
} else {
    echo "Невірний запит";
}

require_once('../php/footer.php');
?>
