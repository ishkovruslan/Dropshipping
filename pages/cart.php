<?php
require_once('header.php');

// Ініціалізація сесії для контролю повторного відправлення
if (!isset($_SESSION['order_submitted'])) {
    $_SESSION['order_submitted'] = false;
}

// Перевірка, чи є товари в кошику
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Обробка оновлення цін
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_price'])) {
        foreach ($_POST['new_price'] as $id => $newPrice) {
            // Перевірка, чи товар є в кошику
            if (isset($_SESSION['cart'][$id])) {
                // Оновлення endprice в сесії
                $_SESSION['cart'][$id]['price'] = max($newPrice, $_SESSION['cart'][$id]['price']);
            }
        }
    }

    // Перевірка наявності відправленої форми замовлення
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order']) && $_SESSION['order_submitted'] === false) {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $post = $_POST['post_type'];
        $city = $_POST['city'];
        $post_number = $_POST['post_number'];

        // Збір даних про товари
        $productIds = [];
        $productQuantities = [];
        $productPrices = [];

        foreach ($_SESSION['cart'] as $id => $item) {
            $productIds[] = $id;
            $productQuantities[] = $item['quantity'];
            $productPrices[] = $item['price'];
        }

        // Підготовка даних для запису у форматі CSV
        $products_count = count($productIds);
        $products_list = implode(",", $productIds);
        $products_number = implode(",", $productQuantities);
        $products_price = implode(",", $productPrices);
        $record_time = (int) (microtime(true) * 1000);

        // Підготовка даних для запису у таблицю orders
        $columns = [
            'login',
            'record_time',
            'products_count',
            'products_list',
            'products_number',
            'products_price',
            'full_name',
            'phone',
            'email',
            'post',
            'city',
            'post_number'
        ];
        $values = [
            $_SESSION['login'],
            $record_time,
            $products_count,
            $products_list,
            $products_number,
            $products_price,
            $full_name,
            $phone,
            $email,
            $post,
            $city,
            $post_number
        ];
        $types = 'ssssssssssss';
        logAction($db, "Замовлення", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' створив нове замовлення');

        // Збереження даних у таблицю orders
        if ($db->write('orders', $columns, $values, $types)) {
            echo "<p>Ваше замовлення успішно збережено!</p>";
            // Встановлення прапорця для уникнення повторного відправлення форми
            $_SESSION['order_submitted'] = true;
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Скидання прапорця при завантаженні сторінки через GET-запит
        $_SESSION['order_submitted'] = false;
    }
    ?>
    <h2>Ваш кошик</h2>
    <form method='POST' action='cart.php'>
        <table>
            <tr>
                <th>Назва товару</th>
                <th>Кількість</th>
                <th>Ціна</th>
                <th>Власна ціна</th>
                <th>Сума</th>
            </tr>
            <?php
            $totalPrice = 0;
            foreach ($_SESSION['cart'] as $id => $item) {
                $result = $db->read('products', ['*'], ['id' => $id]);

                if (count($result)) {
                    $row = $result[0];
                    $itemName = htmlspecialchars($row["product_name"]);
                    $lowprice = htmlspecialchars($row["price"]);
                    $endprice = htmlspecialchars($item['price']);
                    echo "<tr>
                    <td>$itemName</td>
                    <td>{$item['quantity']}</td>
                    <td>$lowprice</td>
                    <td><input type='number' name='new_price[$id]' value='$endprice' min='$lowprice' step='1'></td>";
                    $itemTotal = $endprice * $item['quantity'];
                    echo "<td>$itemTotal</td>
                  </tr>";
                    $totalPrice += $itemTotal;
                }
            } ?>
            <tr>
                <td><button type='submit'>Оновити ціни</button></td>
                <td colspan='3'>Загальна сума:</td>
                <td><?php echo $totalPrice ?></td>
            </tr>
        </table>
    </form>

    <h3>Оформити замовлення</h3>
    <form method="POST" action="cart.php">
        <label>ПІБ: <input type="text" name="full_name" required pattern="^([А-ЯЁ][а-яё]{1,} ){2}[А-ЯЁ][а-яё]{1,}$"
                title="ПІБ має містити 3 слова, кожне з яких починається з великої літери та має мінімум 2 символи"></label><br>
        <label>Телефон: <input type="text" name="phone" required pattern="^\d{8,15}$"
                title="Телефон має містити від 8 до 15 цифр"></label><br>
        <label>Електронна пошта: <input type="email" name="email" required
                pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                title="Введіть коректну електронну пошту"></label><br>
        <label>Поштовий оператор: <input type="text" name="post_type" required pattern="^[А-Яа-яЁёA-Za-z\s]+$"
                title="Поштовий оператор може містити лише літери українського та англійського алфавіту"></label><br>
        <label>Місто: <input type="text" name="city" required pattern="^[А-Яа-яЁёA-Za-z\s]+$"
                title="Місто може містити лише літери українського та англійського алфавіту"></label><br>
        <label>Номер відділення: <input type="text" name="post_number" required pattern="^\d+$"
                title="Номер відділення має містити лише цифри"></label><br>
        <button type="submit" name="submit_order">Зберегти замовлення</button>
    </form>
    <?php
} else {
    echo "<p>Ваш кошик порожній.</p>";
}
require_once('../php/footer.php');
?>