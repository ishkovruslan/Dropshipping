<?php
session_start(); /* Початок сессії */
require_once('../functions/mysql.php'); /* З'єднання з БД */
require_once('../class/accesscontrol.php'); /* Перевірка  */
$current_page = basename($_SERVER['PHP_SELF'], '.php'); /* Інформація про поточну сторінку */
?>

<!DOCTYPE html>
<html lang="ukr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бакалаврська робота</title>
    <link rel="stylesheet" type="text/css" href="../styles/global/style.css">
    <?php require_once('../handler/theme.php'); ?> <!-- Робота з темами -->
    <!-- Автоматичні стилі з врахування назви сторінки -->
    <link rel="stylesheet" type="text/css" href="../styles/pages/<?= htmlspecialchars($current_page); ?>.css">
    <link rel="stylesheet" type="text/css" href="../styles/theme/<?= htmlspecialchars($theme); ?>.css">
    <script src="../scripts/pages/<?= htmlspecialchars($current_page); ?>.js"></script>
</head>

<body>
    <header>
        <div>
            <p>
                <a href="../index.php">Головна</a>
                <script src="../scripts/header.js"></script>
            </p>
            <p>
                <a href="products.php">Товари</a>
            </p>
            <?php
            if (isset($_SESSION['loggedin']) === true) { /* Перевірка авторизації користувача */
                if ($accessControl->getUserLevel($_SESSION['login']) >= 1) { /* Якщо користувач має роль адміністратора або продавця - надати доступ до сторінки керування */
                    ?>
                    <p>
                        <a href="report">Звіти</a>
                    </p>
                    <p>
                        <a href="alerts.php">Сповіщення</a>
                    </p>
                    <p>
                        <a href="account.php">Керування обліковим записом</a>
                    </p>
                    <?php if ($accessControl->getUserLevel($_SESSION['login']) == 1) { ?>
                        <!-- Виключно продавці можуть зв'язуватись з авдміністраторами -->
                        <p>
                            <a href="chat.php">Зв'язок з адміністратором</a>
                        </p>
                    <?php }
                    if (!empty($_SESSION['cart'])) { ?>
                        <p>
                            <a href="cart.php">Кошик</a>
                        </p>
                        <?php
                    }
                } ?>
                <p> <!-- Усі авторизовані користувачі мають можливість вийти з облікового запису -->
                    <a href="logout.php">Вийти</a>
                </p>
            <?php } else { ?>
                <!-- Якщо користувач не авторизований - запропонувати показати кнопки авторизації та реєстрації -->
                <p>
                    <a href="authorization.php">Авторизація</a>
                </p>
                <p>
                    <a href="registration.php">Реєстрація</a>
                </p>
            <?php } ?>
            <p> <!-- Керування темою -->
                <button id="themeButton" data-theme="<?php echo $theme; ?>">Змінити тему</button>
                <script src="../scripts/header.js"></script>
            </p>
        </div>
        <?php
        if (isset($_SESSION['loggedin']) === true && $accessControl->getUserLevel($_SESSION['login']) == 2) { ?>
            <!-- Адміністратор має додаткове навігаційне меню -->
            <div>
                <p>
                    <a href="newnews.php">Додати новину</a>
                </p>
                <p>
                    <a href="newcategory.php">Створити категорію</a>
                </p>
                <p>
                    <a href="newproduct.php">Створити товар</a>
                </p>
                <p>
                    <a href="management.php">Сторінка керування</a>
                </p>
                <p>
                    <a href="messages.php">Зв'язок з користувачами</a>
                </p>
            </div>
        <?php } ?>
    </header>
</body>
<main> <!-- Відкриття контейнеру main для наповнення -->
    