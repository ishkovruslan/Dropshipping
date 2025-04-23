<?php /* Перевірка теми */
if (isset($_SESSION['theme'])) {
    $theme = $_SESSION['theme'];
} else {
    $theme = 'light';
    $_SESSION['theme'] = $theme;
}
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    $_SESSION['theme'] = $theme;
}
