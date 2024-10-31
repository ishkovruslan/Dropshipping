<?php
require_once('header.php');
// Перевірка, чи є товари в кошику
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Обробка оновлення цін
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_price'])) {
        foreach ($_POST['new_price'] as $id => $newPrice) {
            // Перевірка, чи товар є в кошику
            if (isset($_SESSION['cart'][$id])) {
                // Оновлення endprice в сесії
                $_SESSION['cart'][$id]['price'] = max($newPrice, $_SESSION['cart'][$id]['price']); // Зберігаємо нову ціну, якщо вона більша за lowprice
            }
        }
    }   ?>
    <h2>Ваш кошик</h2>
    <form method='POST' action='cart.php'>
    <table>
    <tr><th>Назва товару</th><th>Кількість</th><th>Ціна</th><th>Власна ціна</th><th>Сума</th></tr>
    <?php
    $totalPrice = 0; // Загальна сума
    // Перебір товарів у кошику
    foreach ($_SESSION['cart'] as $id => $item) {
        // Отримання даних товару з бази
        $result = $db->read('products', ['*'], ['id' => $id]);

        if (count($result)) {
            $row = $result[0];
            $itemName = htmlspecialchars($row["product_name"]);
            $lowprice = htmlspecialchars($row["price"]);
            $endprice = htmlspecialchars($item['price']); // Використовуємо endprice з сесії
            echo "<tr>
                    <td>$itemName</td>
                    <td>{$item['quantity']}</td>
                    <td>$lowprice</td>
                    <td><input type='number' name='new_price[$id]' value='$endprice' min='$lowprice' step='1'></td>";
                    $itemTotal = $endprice * $item['quantity']; // Сума для цього товару
            echo "  <td>$itemTotal</td>
                  </tr>";
            $totalPrice += $itemTotal; // Додавання до загальної суми
        }
    }?>
    <tr><td><button type='submit'>Оновити ціни</button></td><td colspan='3'>Загальна сума:</td><td><?php echo $totalPrice ?></td></tr>
    </table>
    </form><?php
} 
else {
    echo "<p>Ваш кошик порожній.</p>";
}
require_once('../php/footer.php');
?>
