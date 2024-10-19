<?php /* Верхня частина сайту */
require_once ('../php/access.php'); /* Перевірка рівня доступу та ролей*/
?>

<!DOCTYPE html>
<html lang="ukr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Курсова робота</title>
    <link rel="stylesheet" type="text/css" href="../styles/global/style.css">
    <!-- Автоматичні стилі з врахування назви сторінки та ролі користувача -->
    <?php
    /* Визначення ролі користувача */
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if (isset($_SESSION['login'])) {    /* Якщо користувач авторизований -> отримати роль */
        $role = $accessControl->getUserRole($_SESSION['login']);
        if ($accessControl->getUserLevel($_SESSION['login']) == -1) {
            $role = 'user'; /* Якщо у користувача немає ролі - видати права користувача */
        }
    } else {
        $role = 'user'; /* За замовчуванням, якщо сесія або ключ 'login' відсутні */
    }
    require_once ('../php/theme.php'); /* Робота з темами */
    ?>
    <link rel="stylesheet" type="text/css" href="../styles/role/<?= htmlspecialchars($role); ?>.css">
    <link rel="stylesheet" type="text/css" href="../styles/pages/<?= htmlspecialchars($current_page); ?>.css">
    <link rel="stylesheet" type="text/css" href="../styles/theme/<?= htmlspecialchars($theme); ?>.css">
    <link rel="stylesheet" type="text/css" href="../styles/global/scaling.css">
</head>

<body>
    <header>
        <img src="../images/global/Top.png">
    </header>
    <div class="PC">
        <img src="../images/global/PC.png">
        <a href="../index.php"> <?php echo date("Y-m-d"); ?></a>
    </div>
    <div class="left"> <!-- Навігаційне меню -->
        <p class="line">
            <img src="../images/global/SharpLine.png">
            <a href="../index.php">Головна</a>
        </p>
        <p class="line">
            <img src="../images/global/SharpLine.png">
            <a href="categories.php">Категорії</a>
        </p>
        <?php
        /* Перевірка авторизації користувача */
        if (isset($_SESSION['loggedin']) === true) {
            /* Якщо це адміністратор - надати доступ до створення новин та категорій*/
            if ($accessControl->getUserLevel($_SESSION['login']) == 2) {
                ?>
                <p class="line">
                    <img src="../images/global/SharpLine.png">
                    <a href="newnews.php">Додати новину</a>
                </p>
                <p class="line">
                    <img src="../images/global/SharpLine.png">
                    <a href="newcategory.php">Створити категорію</a>
                </p>
            <?php } ?>
            <?php
            /* Якщо користувач має роль адміністратора або продавця - надати доступ до сторінки керування та створення товарів */
            if ($accessControl->getUserLevel($_SESSION['login']) >= 1) {
                ?>
                <p class="line">
                    <img src="../images/global/SharpLine.png">
                    <a href="management.php">Сторінка керування</a>
                </p>
                <p class="line">
                    <img src="../images/global/SharpLine.png">
                    <a href="newproduct.php">Створити товар</a>
                </p>
            <?php } ?>
            <p class="line"> <!-- Усі авторизовані користувачі мають можливість вийти з облікового запису -->
                <img src="../images/global/SoftLine.png">
                <a href="../php/logout.php">Вийти</a>
            </p>
        <?php } else { /* Якщо користувач не авторизований - запропонувати показати кнопки авторизації та реєстрації */ ?>
            <p class="line">
                <img src="../images/global/SharpLine.png">
                <a href="authorization.php">Авторизація</a>
            </p>
            <p class="line">
                <img src="../images/global/SoftLine.png">
                <a href="registration.php">Реєстрація</a>
            </p>
        <?php } ?>
    </div>
</body>
<main>
    <div class="news-block"> <!-- Новинний блок -->
        <div>
            <p><img src="../images/global/GreyLine.png"></p>
            <p class="news-text">Новини</p>
        </div>
        <div class="events">
            <?php require_once '../php/newsblock.php'; ?> <!-- Вставляємо блоки новин -->
        </div>
        <button id="themeButton">Змінити тему</button>

        <script>
            // Функція для оновлення URL з параметрами запиту та новою темою
            function updateUrlWithTheme(newTheme) {
                var urlParams = new URLSearchParams(window.location.search);
                // Видаляємо всі параметри теми з URL
                urlParams.delete('theme');
                // Додаємо новий параметр теми
                urlParams.set('theme', newTheme);
                // Оновлюємо адресу сторінки з новим параметром теми та параметрами запиту
                window.location.search = urlParams.toString();
            }
            // Обробник подій для кнопки зміни теми
            var themeButton = document.getElementById('themeButton');
            themeButton.addEventListener('click', function () {
                var currentTheme = "<?php echo $theme; ?>";
                var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                updateUrlWithTheme(newTheme);
            });
        </script>
    </div>