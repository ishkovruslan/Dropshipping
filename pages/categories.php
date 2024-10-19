<?php
session_start(); // Початок сесії
require_once('header.php'); // Верхня частина сайту
require_once('../php/mysql.php'); // Підключення до бази даних
require_once('../php/output.php'); // Модуль виведення категорій, списку товарів, товарів
?>

<?php $categories->displayCategories(); ?>

<?php require_once('../php/footer.php'); ?>