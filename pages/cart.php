<?php /* Сторінка замовлень */
require_once('header.php'); /* Навігаційне меню */

/* Перевірка, чи є товари в кошику */
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("location: ../index.php");
    exit();
}

/* Обробка запитів POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart']) && !empty($_POST['new_price']) && !empty($_POST['quantity'])) { /* Оновлення кошику */
        foreach ($_POST['new_price'] as $id => $newPrice) {
            if (isset($_SESSION['cart'][$id])) {
                $newQuantity = (int) $_POST['quantity'][$id];
                $newPrice = (float) $newPrice;

                /* Оновлення даних у сесії */
                $_SESSION['cart'][$id]['realization_price'] = $newPrice;
                $_SESSION['cart'][$id]['quantity'] = $newQuantity;
            }
        }
    }

    if (isset($_POST['submit_order'])) {
        require_once('../functions/cart.php'); /* Підключення файлу з функцією оформлення замовлення */
        createOrder($db); /* Виклик функції оформлення замовлення */
    }
}

?>
<h2>Ваш кошик</h2>
<form method='POST' action='cart.php'>
    <table border="1" cellspacing="0" cellpadding="5"> <!-- Формування таблиці замовлення -->
        <tr>
            <th>Назва товару</th>
            <th>Кількість</th>
            <th>Мінімальна ціна</th>
            <th>Ціна користувача</th>
            <th>Прибуток (за позицію)</th>
        </tr>
        <?php
        foreach ($_SESSION['cart'] as $id => $item) { /* Заповнення таблиці замовлень */
            $result = $db->read('products', ['*'], ['id' => $id]);

            if (count($result)) {
                $row = $result[0];
                $itemName = htmlspecialchars($row['product_name']); /* Назва позиції */
                $lowPrice = (float) $item['low_price']; /* Мінімальна ціна з сесії */
                $userPrice = (float) $item['realization_price']; /* Ціна користувача */
                $quantity = (int) $item['quantity']; /* Кількість товарів */

                /* Обчислення прибутку за позицію */
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
        $totalProfit = 0; /* Формування загального прибутку */
        foreach ($_SESSION['cart'] as $item) {
            $profitPerItem = ($item['realization_price'] - $item['low_price']) * $item['quantity'];
            $totalProfit += $profitPerItem;
        }
        ?>
        <tr>
            <td colspan="4"><strong>Загальний прибуток:</strong></td>
            <td><strong><?php echo $totalProfit; ?></strong></td>
        </tr>
    </table>
    <button type='submit' name='update_cart'>Оновити кошик</button> <!-- Запуск скрипта оновлення кошика -->
</form>

<h3>Оформити замовлення</h3> <!-- Форма вводу кінцевого користувача -->
<form method="POST" action="cart.php">
    <label>Пошук споживача: <input type="text" id="searchConsumer" placeholder="Search by full name" />
        <!-- Пошук за фрагментом ПІБ -->
        <div id="searchResults"></div> <!-- Кінцеві користувачі, які мають такі фрагменти -->
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
require_once('footer.php');
