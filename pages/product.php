<?php
session_start();
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
    } else {
        echo "Товар не знайдено";
    }
} else {
    echo "Невірний запит";
}

require_once('../php/footer.php');
?>