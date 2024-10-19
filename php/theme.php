<?php
/* Перевірка теми */
if (isset($_SESSION['theme'])) {
    /* Отримання теми з сессії */
    $theme = $_SESSION['theme'];
} else {
    /* Якщо тема відсутня -> встановити її */
    $theme = 'light';
    $_SESSION['theme'] = $theme;
}
/* Очікування запиту на зміну */
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    $_SESSION['theme'] = $theme;
}
?>