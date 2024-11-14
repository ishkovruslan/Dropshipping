<?php
require_once('header.php'); // Верхня частина сайту
$accessControl->checkAccess(2); // Доступ лише у адміністраторів
require_once('../php/crud.php'); // Необхідні функції
// Якщо не вибрано жодної таблиці, показати форму вибору
if (!isset($_GET['table']) && !isset($_GET['category'])) {
    ?>
    <div class="table-selection">
        <h1>Виберіть таблицю для перегляду:</h1>
        <ul>
            <li><a href="?table=users">Користувачі</a></li>
            <li><a href="?table=news">Новини</a></li>
            <li><a href="?table=categories">Категорії</a></li>
            <li><a href="?table=log">Перегляд змін</a></li> <!-- Новий пункт меню -->
        </ul>
    </div>
    <?php
} else {
    // Завантаження таблиці користувачів, новин, категорій або товарів за обраною категорією
    $table = $_GET['table'] ?? null;
    $selectedCategory = $_GET['category'] ?? null;
    // Відображення вибраної таблиці
    switch ($table) {
        case 'users':
            $userList = new UserList($db);
            $userList->loadUsersFromDB();
            $roles = ["user" => "Користувач", "seller" => "Продавець"];
            require_once('management/userlist.php');
            break;
        case 'log':
            $logs = $db->readAll('log');
            require_once('management/log.php');
            break;
        case 'news':
            $newsData = $db->readAll('news');
            require_once('management/news.php');
            break;
        case 'categories':
            $categoriesData = $db->readAll('categories');
            $productsData = $db->readAll('products');
            require_once('management/category.php');
            break;
    }
    if ($selectedCategory) {
        $categoriesData = $db->readAll('categories');
        $productsData = $db->readAll('products');
        $filteredProducts = $selectedCategory ? array_filter($productsData, fn($product) => $product['category'] === $selectedCategory) : $productsData;
        require_once('management/products.php');
    }
}
require_once('../php/footer.php'); ?>