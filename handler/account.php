<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    /* Виклик методу для зміни паролю */
    require_once('../class/authentication.php'); /* Підключення до класу аутентифікації */
    $result = $authentication->changePassword($_SESSION['login'], $currentPassword, $newPassword, $confirmPassword);
    if ($result['success']) {
        echo '<p style="color: green;">Пароль успішно змінено!</p>';
    } else {
        echo '<p style="color: red;">' . $result['message'] . '</p>';
    }
}