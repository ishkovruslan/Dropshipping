<?php /* Сторінка товару */
require_once('header.php'); /* Навігаційне меню */
require_once('../functions/mysql.php'); /* Підключення БД */

$id = $_GET['id'] ?? null; /* Отримання id товару з параметра URL */

if ($id) { /* Виводимо інформації в разі наявності ідентифікатора */
    $result = $db->read('products', ['*'], ['id' => $id]);

    if (count($result)) {
        $row = $result[0];
        $itemName = htmlspecialchars($row["product_name"]);
        $price = htmlspecialchars($row["price"]);
        $count = htmlspecialchars($row["count"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        $characteristics = explode(',', $row["characteristics"]);
        /* Отримання специфікацій для категорії товару */
        $category = $row["category"];
        $specificationsResult = $db->read('categories', ['specifications'], ['category_name' => $category]);
        $specifications = explode(',', $specificationsResult[0]["specifications"]);

        /* Генерація HTML для відображення товару */
        echo "<div class='main-block'>
                <div class='product-container'>
                    <img src='$imagePath' alt='$itemName' class='product-image'>
                    <div class='product-details'>
                        <h2>$itemName</h2>
                        <p>Кількість: $count</p>
                        <p>Ціна: $price</p>
                        <h3>Характеристики:</h3>
                        <ul>";
        /* Відображення характеристик товару */
        foreach ($characteristics as $key => $value) {
            if ($value !== "-" && $value !== "") {
                echo "<li>" . htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value) . "</li>";
            }
        }
        echo "      </ul>
                    </div>
                </div>
            </div>";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) { /* Додавання товару до сесії */
            $quantity = $_POST['quantity'];

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = []; /* Ініціалізація масиву сесії, якщо він ще не існує */
            }

            $currentInCart = $_SESSION['cart'][$id]['quantity'] ?? 0; /* Кількість наявних товарів в кошику */
            $availableQuantity = $count - $currentInCart; /* Доступна кількість для додавання */
            if ($quantity > $availableQuantity) {
                echo "<p>На жаль, ви намагаєтеся замовити $quantity одиниць, але в наявності лише $availableQuantity, враховуючи ваш кошик. Будь ласка, скоригуйте кількість.</p>";
                $quantity = $availableQuantity; /* Пропонуємо додати доступну кількість */
            }

            if ($quantity > 0) {
                if (isset($_SESSION['cart'][$id])) {
                    $_SESSION['cart'][$id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$id] = [
                        'id' => $id,
                        'product_name' => $itemName,
                        'quantity' => $quantity,
                        'low_price' => $row['price'], /* Мінімальна ціна з бази */
                        'realization_price' => $row['price'] /* Початкова ціна користувача */
                    ];
                }
                echo "<p>Товар додано до кошика!</p>";
                header("location: product.php?id=" . $id);
            } else {
                echo "<p>Кількість товару перевищує наявний залишок. Товар не додано до кошика.</p>";
            }
        }

        if (isset($_SESSION['loggedin']) === true && $accessControl->getUserLevel($_SESSION['login']) >= 1) {
            /* Форма для додавання товару до кошика */
            echo "<form method='POST'>
                    <label for='quantity'>Кількість:</label>
                    <input type='number' name='quantity' value='1' min='1' max='$count'>
                    <button type='submit' name='add_to_cart'>Додати до кошика</button>
                  </form>";
        }
    } else {
        echo "Товар не знайдено";
    }
} else {
    echo "Невірний запит";
}

require_once('footer.php');
