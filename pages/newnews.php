<?php
session_start(); /* Початок сессії */
require_once ('header.php'); /* Верхня частина сайту */
$accessControl->checkAccess(2); /* Доступ лише у адміністраторів */
?>

<div class="main-block"><!-- Сторінка створення новин -->
    <h1>Створити нову новину</h1>
    <form action="../php/crud.php" method="post" enctype="multipart/form-data">
        <label for="news_title">Назва новини:</label><br>
        <input type="text" id="news_title" name="news_title"><br><br>
        <label for="uploadPath">Зображення новини з співвідношенням 16:9:</label><br>
        <input type="file" id="uploadPath" name="uploadPath" accept="image/*" required><br><br>
        <label for="news_description">Опис новини:</label><br>
        <textarea id="news_description" name="news_description" rows="4" cols="50"></textarea><br><br>
        <label for="start_date">Дата початку:</label><br>
        <input type="date" id="start_date" name="start_date"><br><br>
        <label for="end_date">Дата кінця:</label><br>
        <input type="date" id="end_date" name="end_date"><br><br>
        <button type="submit" name="create_news">Створити новину</button>
    </form>
</div>
<?php require_once ('../php/footer.php'); ?>