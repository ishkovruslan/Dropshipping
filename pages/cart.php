<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Бібліотека має бути підключеною перед скриптом -->
<?php
require_once('header.php');

// Увімкнення відображення помилок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
        // Логування надходження форми
        error_log('Форма замовлення відправлена');

        // Перевірка наявності обов'язкових полів
        $requiredFields = ['full_name', 'phone', 'email', 'post_type', 'city', 'post_number'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                error_log("Поле $field порожнє");
                exit("Помилка: Поле $field є обов'язковим.");
            }
        }

        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $post = $_POST['post_type'];
        $city = $_POST['city'];
        $post_number = $_POST['post_number'];

        // Перевірка наявності споживача
        $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $full_name]);
        if (count($existingConsumer) > 0) {
            $consumerId = $existingConsumer[0]['id'];
            $db->update('consumer', [
                'phone' => $phone,
                'email' => $email,
                'post' => $post,
                'city' => $city,
                'post_number' => $post_number
            ], ['id' => $consumerId]);
        } else {
            $columns = ['full_name', 'phone', 'email', 'post', 'city', 'post_number'];
            $values = [$full_name, $phone, $email, $post, $city, $post_number];
            $types = 'ssssss';

            if (!$db->write('consumer', $columns, $values, $types)) {
                error_log('Помилка запису споживача');
                exit('Помилка: Не вдалося додати споживача.');
            }
        }

        // Збір даних про товари
        $productQuantities = [];
        $productPrices = [];
        $productRealizations = []; // Додано новий масив для цін, які вказав користувач

        foreach ($_SESSION['cart'] as $id => $item) {
            $productIds[] = $id;
            $productQuantities[] = $item['quantity'];
            $productPrices[] = $item['price'];
            $productRealizations[] = isset($_POST['new_price'][$id]) ? $_POST['new_price'][$id] : $item['price']; // Отримання ціни, яку вказав користувач
        }

        // Підготовка даних для запису у форматі CSV
        $products_count = count($productIds);
        $products_list = implode(",", $productIds);
        $products_number = implode(",", $productQuantities);
        $products_price = implode(",", $productPrices);
        $products_realization = implode(",", $productRealizations); // Додано новий рядок для реалізації цін
        $record_time = (int) (microtime(true) * 1000);

        // Підготовка даних для запису у таблицю orders
        $columns = [
            'login',
            'record_time',
            'products_count',
            'products_list',
            'products_number',
            'products_realization', // Додано новий стовпець
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
            $products_realization, // Додано новий рядок
            $products_price,
            $full_name,
            $phone,
            $email,
            $post,
            $city,
            $post_number
        ];
        $types = 'sssssssssssss';
        logAction($db, "Замовлення", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' створив нове замовлення');

        // Збереження даних у таблицю orders
        if ($db->write('orders', $columns, $values, $types)) {
            error_log('Помилка запису в таблицю orders');
            exit('Помилка: Не вдалося додати замовлення.');
        } else {
            echo "<p>Ваше замовлення успішно збережено!</p>";
            unset($_SESSION['cart']); // Очистка кошика
            error_log('Замовлення збережено, кошик очищено');
            header("location: ../index.php");
        }
    }

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
            }
            ?>
            <tr>
                <td><button type='submit'>Оновити ціни</button></td>
                <td colspan='3'>Загальна сума:</td>
                <td><?php echo $totalPrice ?></td>
            </tr>
        </table>
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
} else {
    header("location: ../index.php");
}
require_once('../php/footer.php');
?>