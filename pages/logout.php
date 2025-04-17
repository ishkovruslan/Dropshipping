<?php
session_start(); /* Початок сессії */
/* Перевірка сессії */
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    setcookie(session_name(), '', time() - 3600, '/'); /* Зачистка сесійних куків */
    session_unset(); /* Видалення змінних сесії */
    session_destroy(); /* Закриття сесії */
}
header("Location: authorization.php"); /* Перенаправлення на сторінку авторизації */
exit;
?>
