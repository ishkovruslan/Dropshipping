<?php
session_start(); /* Початок сессії */
/* Перевірка сессії */
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Зачистка сесійних куків
    setcookie(session_name(), '', time() - 3600, '/');
    // Видалення змінних сесії
    session_unset();
    // Закриття сесії
    session_destroy();
}
// Перенаправлення на сторінку авторизації
header("Location: ../pages/authorization.php");
exit;
?>
