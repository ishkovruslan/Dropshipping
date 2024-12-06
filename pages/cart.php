<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Бібліотека має бути підключеною перед скриптом -->
<?php
ob_start(); // Початок буферизації виводу
require_once('header.php');

// Увімкнення відображення помилок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Перевірка наявності активного сеансу користувача
if (!isset($_SESSION['login'])) {
    exit('Помилка: Ви повинні увійти до системи, щоб здійснити замовлення.');
}

// Перевірка, чи є товари в кошику
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("location: ../index.php");
} else {
    ?>
    <h2>Ваш кошик</h2>
    <form method='POST' action='cart.php'>
        <table>
            <tr>
                <th>Назва товару</th>
                <th>Кількість</th>
                <th>Ціна</th>
                <th>Власна ціна</th> <!-- Ціна, яку вказав користувач -->
                <th>Сума</th>
            </tr>
            <?php
            $totalPrice = 0; // Ініціалізація загальної суми
            foreach ($_SESSION['cart'] as $id => $item) {
                $result = $db->read('products', ['*'], ['id' => $id]);

                if (count($result)) {
                    $row = $result[0];
                    $itemName = htmlspecialchars($row["product_name"]);
                    $lowprice = htmlspecialchars($row["price"]);
                    $endprice = htmlspecialchars($item['price']);

                    // Обчислення загальної суми для товару
                    $itemTotal = $endprice * $item['quantity']; // Переконайтеся, що $item['quantity'] існує
                    $totalPrice += $itemTotal; // Додаємо до загальної суми
        
                    echo "<tr>
                    <td>$itemName</td>
                    <td>
                        <input type='number' name='quantity[$id]' value='{$item['quantity']}' min='1' max='{$row['count']}'>
                        <button type='submit' name='remove[$id]'>Видалити</button>
                    </td>
                    <td>$lowprice</td>
                    <td><input type='number' name='new_price[$id]' value='$endprice' min='$lowprice' step='1'></td>
                    <td>$itemTotal</td>
                </tr>";
                }
            }
            ?>
            <tr>
                <td colspan='4'>Загальна сума:</td>
                <td><?php echo $totalPrice; ?></td>
            </tr>
        </table>
        <button type='submit'>Оновити кошик</button> <!-- Залиште кнопку для оновлення кількості та видалення товарів -->
    </form>

    <h3>Оформити замовлення</h3>
    <form method="POST" action="cart.php">
        <label>Пошук споживача: <input type="text" id="searchConsumer" placeholder="Search by full name" />
            <div id="searchResults"></div>
            <div id="consumerDetails"></div>
            <datalist id="suggestions"></datalist>
            <label>ПІБ: <input type="text" id="full_name" name="full_name" required
                    pattern="^([А-Я][а-я]{1,} ){2}[А-Я][а-я]{1,}$"
                    title="ПІБ має містити 3 слова, кожне з яких починається з великої літери та має мінімум 2 символи"></label><br>
            <label>Телефон: <input type="text" id="phone" name="phone" required pattern="^\d{8,15}$"
                    title="Телефон має містити від 8 до 15 цифр"></label><br>
            <label>Електронна пошта: <input type="email" id="email" name="email" required
                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                    title="Введіть коректну електронну пошту"></label><br>
            <label>Поштовий оператор: <input type="text" id="post_type" name="post_type" required
                    pattern="^[А-Яа-яA-Za-z\s]+$"
                    title="Поштовий оператор може містити лише літери українського та англійського алфавіту"></label><br>
            <label>Місто: <input type="text" id="city" name="city" required pattern="^[А-Я][а-яA-Za-z\s]+$"
                    title="Місто може містити лише літери українського та англійського алфавіту"></label><br>
            <label>Номер відділення: <input type="text" id="post_number" name="post_number" required pattern="^\d+$"
                    title="Номер відділення має містити лише цифри"></label><br>
            <button type="submit" name="submit_order">Зберегти замовлення</button>
    </form>
    <?php
}

// Обробка запитів POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Оновлення кількості товарів
    if (isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $id => $newQuantity) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] = max(0, (int) $newQuantity);
                if ($_SESSION['cart'][$id]['quantity'] === 0) {
                    unset($_SESSION['cart'][$id]);
                }
            }
        }
    }

    // Оновлення цін
    if (isset($_POST['new_price'])) {
        foreach ($_POST['new_price'] as $id => $newPrice) {
            if (isset($_SESSION['cart'][$id])) {
                // Оновлюємо ціну, якщо нова ціна більша за 0
                if ($newPrice > 0) {
                    $_SESSION['cart'][$id]['price'] = (float) $newPrice;
                }
            }
        }
    }

    // Видалення товару
    if (isset($_POST['remove'])) {
        foreach ($_POST['remove'] as $id => $value) {
            unset($_SESSION['cart'][$id]);
        }
        header("Location: cart.php");
        exit();
    }

    // Відправлення замовлення
    if (isset($_POST['submit_order'])) {
        error_log('Форма замовлення відправлена');
        $outOfStockItems = [];
        foreach ($_SESSION['cart'] as $id => $item) {
            $result = $db->read('products', ['*'], ['id' => $id]);
            if (!empty($result)) {
                $product = $result[0];
                if ($item['quantity'] > $product['count']) {
                    $outOfStockItems[$id] = [
                        'name' => $product['product_name'],
                        'available' => $product['count'],
                        'requested' => $item['quantity']
                    ];
                }
            }
        }

        // Якщо товарів недостатньо
        if (!empty($outOfStockItems)) {
            $message = "На жаль, деякі товари недоступні:<br>";
            foreach ($outOfStockItems as $item) {
                $message .= "{$item['name']}: Запитано {$item['requested']}, Доступно {$item['available']}<br>";
            }
            exit($message);
        }

        // Перевірка обов'язкових полів
        $requiredFields = ['full_name', 'phone', 'email', 'post_type', 'city', 'post_number'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                exit("Помилка: Поле $field є обов'язковим.");
            }
        }

        $consumerData = [
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'post' => $_POST['post_type'],
            'city' => $_POST['city'],
            'post_number' => $_POST['post_number']
        ];

        // Додати або оновити споживача
        $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $consumerData['full_name']]);
        if ($existingConsumer) {
            $db->update('consumer', $consumerData, ['id' => $existingConsumer[0]['id']]);
        } else {
            $db->write('consumer', array_keys($consumerData), array_values($consumerData), 'ssssss');
        }

        // Підготовка даних для замовлення
        $productsCount = count($_SESSION['cart']);
        $productsList = implode(",", array_keys($_SESSION['cart']));
        $productsQuantities = implode(",", array_column($_SESSION['cart'], 'quantity'));
        $productsPrices = implode(",", array_column($_SESSION['cart'], 'price'));
        $newPrices = $_POST['new_price'] ?? [];
        $productsRealization = implode(",", array_map(function ($id, $post) {
            return $post['new_price'][$id] ?? $_SESSION['cart'][$id]['price'];
        }, array_keys($_SESSION['cart']), array_fill(0, count($_SESSION['cart']), $_POST)));
        
        $orderData = [
            'login' => $_SESSION['login'],
            'record_time' => (int) (microtime(true) * 1000),
            'products_count' => $productsCount,
            'products_list' => $productsList,
            'products_number' => $productsQuantities,
            'products_realization' => $productsRealization,
            'products_price' => $productsPrices
        ] + $consumerData;

        // Запис замовлення в базу
        if ($db->write('orders', array_keys($orderData), array_values($orderData), 'sssssssssssss')) {
            unset($_SESSION['cart']);
            error_log('Замовлення збережено');
            header("location: ../index.php");
            exit();
        } else {
            exit('Помилка: Не вдалося додати замовлення.');
        }
    }
}
require_once('../php/footer.php');
ob_end_flush(); // Відправка буферизованого виводу на браузер
?>