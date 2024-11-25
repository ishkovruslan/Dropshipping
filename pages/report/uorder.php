<?php $accessControl->checkAccess(2); // Доступ лише у адміністраторів ?>
<div class="orders">
    <h1>Замовлення за користувачем</h1>

    <!-- Форма для вибору користувача -->
    <form method="GET" action="">
        <input type="hidden" name="table" value="uorder" />
        <select name="login">
            <option value="">Оберіть користувача</option>
            <?php
            // Отримуємо список користувачів з таблиці userlist
            $users = $db->readAll('userlist');

            foreach ($users as $user) {
                $selected = (isset($_GET['login']) && $_GET['login'] === $user['login']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($user['login']) . '" ' . $selected . '>' . htmlspecialchars($user['login']) . '</option>';
            }
            ?>
        </select>
        <input type="submit" value="Пошук" />
    </form>

    <?php
// Отримуємо фільтр
$searchFilters = [
    'login' => isset($_GET['login']) ? trim($_GET['login']) : '',
];

// Перевіряємо, чи заданий фільтр для пошуку
if (!empty($searchFilters['login'])) {
    // Використовуємо функцію getOrders для отримання замовлень
    $currentUserLogin = $_SESSION['login'];
    $orders = orders($db, $currentUserLogin, $searchFilters);
    if (empty($orders)) {
        echo "<p>" . htmlspecialchars($searchFilters['login']) . " не має замовлень.</p>";
    } else {
        // Створюємо асоціативний масив для зберігання замовлень за датами
        $ordersByDate = [];
        foreach ($orders as $order) {
            $date = date('Y-m-d', $order['record_time'] / 1000);
            if (!isset($ordersByDate[$date])) {
                $ordersByDate[$date] = [];
            }
            $ordersByDate[$date][] = $order;
        }
        // Виводимо замовлення по днях
        foreach ($ordersByDate as $date => $dailyOrders) {
            echo '<h2>Замовлення ' . $order['login'] . ' за ' . htmlspecialchars($date) . '</h2>';
            echo '<table>';
            echo '<tr>
                <th width="70px">№</th>
                <th width="140px">Час замовлення</th>
                <th>Список товарів</th>
                <th width="220px">Прибуток за замовлення</th>
              </tr>';
            $dailyProfit = 0; // Змінна для підрахунку прибутку за день
            foreach ($dailyOrders as $order) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($order['id']) . '</td>';
                echo '<td>' . date('H:i:s', $order['record_time'] / 1000) . '</td>';
                echo '<td>';
                $products = explode(',', $order['products_list']);
                $quantities = explode(',', $order['products_number']);
                $prices = explode(',', $order['products_price']);
                $realization = explode(',', $order['products_realization']);
                $productDetails = order($db, $products, $quantities, $prices, $realization);
                // Формування HTML для списку продуктів
                $productTexts = array_column($productDetails, 'text');
                echo implode('<br>', $productTexts);
                // Підрахунок прибутку за день
                foreach ($productDetails as $detail) {
                    $dailyProfit += $detail['total'];
                }
                echo '<td>' . $dailyProfit . '</td>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<h3>Прибуток за ' . date('Y-m-d', $order['record_time'] / 1000) . ': ' . $dailyProfit . '</h3>';
        }
    }
} else {
    // Якщо фільтр порожній, повідомляємо користувача
    echo "<p>Оберіть для пошуку.</p>";
} ?>
</div>