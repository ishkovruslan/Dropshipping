<?php
require_once('header.php');

// Перевірка, чи є товари в кошику
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("location: ../index.php");
    exit();
}

// Обробка запитів POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart']) && !empty($_POST['new_price']) && !empty($_POST['quantity'])) {
        foreach ($_POST['new_price'] as $id => $newPrice) {
            if (isset($_SESSION['cart'][$id])) {
                $newQuantity = (int) $_POST['quantity'][$id];
                $newPrice = (float) $newPrice;

                // Оновлення даних у сесії
                $_SESSION['cart'][$id]['realization_price'] = $newPrice;
                $_SESSION['cart'][$id]['quantity'] = $newQuantity;
            }
        }
    }

    if (isset($_POST['submit_order'])) {
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

        $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $consumerData['full_name']]);
        if ($existingConsumer) {
            $db->update('consumer', $consumerData, ['id' => $existingConsumer[0]['id']]);
        } else {
            $db->write('consumer', array_keys($consumerData), array_values($consumerData), 'ssssss');
        }

        $orderData = [
            'login' => $_SESSION['login'],
            'record_time' => (int) (microtime(true) * 1000),
            'products_count' => count($_SESSION['cart']),
            'products_list' => implode(",", array_keys($_SESSION['cart'])),
            'products_number' => implode(",", array_column($_SESSION['cart'], 'quantity')),
            'products_realization' => implode(",", array_column($_SESSION['cart'], 'realization_price')),
            'products_price' => implode(",", array_column($_SESSION['cart'], 'low_price'))
        ] + $consumerData;

        if (!$db->write('orders', array_keys($orderData), array_values($orderData), 'sssssssssssss')) {
            foreach ($_SESSION['cart'] as $id => $item) {
                $result = $db->read('products', ['count'], ['id' => $id]);
                if (!empty($result)) {
                    $newCount = max(0, (int) $result[0]['count'] - $item['quantity']);
                    $db->update('products', ['count' => $newCount], ['id' => $id]);
                }
            }
            unset($_SESSION['cart']);
            header("location: ../index.php");
        } else {
            exit('Помилка: Не вдалося додати замовлення.');
        }
    }
}

?>
<h2>Ваш кошик</h2>
<form method='POST' action='cart.php'>
    <table border="1" cellspacing="0" cellpadding="5">
        <tr>
            <th>Назва товару</th>
            <th>Кількість</th>
            <th>Мінімальна ціна</th>
            <th>Ціна користувача</th>
            <th>Прибуток (за позицію)</th>
        </tr>
        <?php
        foreach ($_SESSION['cart'] as $id => $item) {
            $result = $db->read('products', ['*'], ['id' => $id]);

            if (count($result)) {
                $row = $result[0];
                $itemName = htmlspecialchars($row['product_name']);
                $lowPrice = (float) $item['low_price']; // Мінімальна ціна з сесії
                $userPrice = (float) $item['realization_price']; // Ціна користувача
                $quantity = (int) $item['quantity'];

                // Обчислення прибутку за позицію
                $profitPerItem = ($userPrice - $lowPrice) * $quantity;

                echo "<tr>
                    <td>{$itemName}</td>
                    <td>
                        <input type='number' name='quantity[{$id}]' value='{$quantity}' min='1' max='{$row['count']}' required>
                    </td>
                    <td>{$lowPrice}</td>
                    <td>
                        <input type='number' name='new_price[{$id}]' value='{$userPrice}' min='{$lowPrice}' step='1' required>
                    </td>
                    <td>{$profitPerItem}</td>
                </tr>";
            }
        }

        function recalculateCart()
        {
            $totalProfit = 0;
            foreach ($_SESSION['cart'] as $item) {
                $profitPerItem = ($item['realization_price'] - $item['low_price']) * $item['quantity'];
                $totalProfit += $profitPerItem;
            }
            return $totalProfit;
        }

        // Викликати після оновлення:
        $totalProfit = recalculateCart();
        ?>
        <tr>
            <td colspan="4"><strong>Загальний прибуток:</strong></td>
            <td><strong><?php echo $totalProfit; ?></strong></td>
        </tr>
    </table>
    <button type='submit' name='update_cart'>Оновити кошик</button>
</form>

<h3>Оформити замовлення</h3>
<form method="POST" action="cart.php">
    <label>Пошук споживача: <input type="text" id="searchConsumer" placeholder="Search by full name" />
        <div id="searchResults"></div>
        <div id="consumerDetails"></div>
        <datalist id="suggestions"></datalist>
        <label>ПІБ: <input type="text" id="full_name" name="full_name" required
                pattern="^([А-ЯҐЄІЇ][а-яґєії]{1,} ){2}[А-ЯҐЄІЇ][а-яґєії]{1,}$"
                title="ПІБ має містити 3 слова, кожне з яких починається з великої літери та має мінімум 2 символи"></label><br>
        <label>Телефон: <input type="text" id="phone" name="phone" required pattern="^\d{8,15}$"
                title="Телефон має містити від 8 до 15 цифр"></label><br>
        <label>Електронна пошта: <input type="email" id="email" name="email" required
                pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                title="Введіть коректну електронну пошту"></label><br>
        <label>Поштовий оператор: <input type="text" id="post_type" name="post_type" required
                pattern="^[А-Яа-яA-Za-z\s]+$"
                title="Поштовий оператор може містити лише літери українського та англійського алфавіту"></label><br>
        <label>Місто: <input type="text" id="city" name="city" required
                pattern="^[А-ЯІЄЇҐ][а-яієїґA-Za-z\s\-]*([\-][А-ЯІЄЇҐ][а-яієїґA-Za-z]*)*$"
                title="Місто може містити лише літери українського та англійського алфавіту"></label><br>
        <label>Номер відділення: <input type="text" id="post_number" name="post_number" required pattern="^\d+$"
                title="Номер відділення має містити лише цифри"></label><br>
        <button type="submit" name="submit_order">Зберегти замовлення</button>
</form>

<?php
require_once('../php/footer.php');
?>
