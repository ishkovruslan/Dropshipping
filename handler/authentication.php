<?php /* Обробник аутентифікації */
if ($_SERVER["REQUEST_METHOD"] == "POST") { /* Обробник для реєстрації/авторизації */
    require_once('../class/authentication.php'); /* Підключення до класу аутентифікації */
    if (isset($_POST['login_submit'])) {
        $login = $_POST['login'];
        $password = $_POST['password'];
        $result = $authentication->authenticate($login, $password);
        if ($result['success']) {
            header('Location: ../index.php');
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    } elseif (isset($_POST['register_submit'])) {
        $login = $_POST['login'];
        $password = $_POST['password'];
        $result = $authentication->register($login, $password);
        if ($result['success']) {
            header('Location: ../index.php');
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    }
}
