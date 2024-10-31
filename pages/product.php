<?php
require_once('header.php');
require_once('../php/mysql.php');
// Отримання id товару з параметра URL
$id = $_GET['id'] ?? null;
if ($id) {
    // Отримання даних товару з бази
    $result = $db->read('products', ['*'], ['id' => $id]);
    if (count($result)) {
        $row = $result[0];
        $itemName = htmlspecialchars($row["product_name"]);
        $price = htmlspecialchars($row["price"]);
        $count = htmlspecialchars($row["count"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        $characteristics = explode(',', $row["characteristics"]);
        // Отримання специфікацій для категорії товару
        $category = $row["category"];
        $specificationsResult = $db->read('categories', ['specifications'], ['category_name' => $category]);
        $specifications = explode(',', $specificationsResult[0]["specifications"]);
        // Генерація HTML для відображення товару
        echo "<div class='main-block'>
                <div class='product-container'>
                    <img src='$imagePath' alt='$itemName' class='product-image'>
                    <div class='product-details'>
                        <h2>$itemName</h2>
                        <p>Кількість: $count</p>
                        <p>Ціна: $price</p>
                        <h3>Характеристики:</h3>
                        <ul>";
        // Відображення характеристик товару
        foreach ($characteristics as $key => $value) {
            if ($value !== "-" && $value !== "") {
                echo "<li>" . htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value) . "</li>";
            }
        }
        echo "      </ul>
                    </div>
                </div>
            </div>";
        // Додавання товару до сесії
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
            $quantity = $_POST['quantity'] ?? 1; // Кількість товару, за замовчуванням 1
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = []; // Ініціалізація масиву сесії, якщо він ще не існує
            }
            // Додавання товару до сесії
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] += $quantity; // Збільшення кількості, якщо товар вже в сесії
            } else {
                $_SESSION['cart'][$id] = ['quantity' => $quantity, 'price' => $price];
            }
            echo "<p>Товар додано до кошика!</p>";
        }
        if (isset($_SESSION['loggedin']) === true && $accessControl->getUserLevel($_SESSION['login']) >= 1) {
            // Форма для додавання товару до кошика
            echo "<form method='POST'>
                    <label for='quantity'>Кількість:</label>
                    <input type='number' name='quantity' value='1' min='1'>
                    <button type='submit' name='add_to_cart'>Додати до кошика</button>
                    </form>";
        }
    } else {
        echo "Товар не знайдено";
    }
} else {
    echo "Невірний запит";
}
require_once('../php/footer.php');
?>