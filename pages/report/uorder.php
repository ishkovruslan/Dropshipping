<?php $accessControl->checkAccess(2); /* Доступ лише у адміністраторів */ ?> <!-- Замовлення по користувачам -->
<div class="orders">
    <h1>Замовлення за користувачем</h1>
    <form method="GET" action="">
        <input type="hidden" name="table" value="uorder" />
        <select name="login">
            <option value="">Оберіть користувача</option>
            <?php $users = $db->readAll('userlist');
            foreach ($users as $user) {
                $selected = (isset($_GET['login']) && $_GET['login'] === $user['login']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($user['login']) . '" ' . $selected . '>' . htmlspecialchars($user['login']) . '</option>';
            } ?>
        </select>
        <input type="submit" value="Пошук" />
    </form>

    <?php $searchFilters = [
        'login' => isset($_GET['login']) ? trim($_GET['login']) : '',
    ];
    if (!empty($searchFilters['login'])) {
        $currentUserLogin = $_SESSION['login'];
        $orders = orders($db, $currentUserLogin, $searchFilters);
        if (empty($orders)) {
            echo "<p>" . htmlspecialchars($searchFilters['login']) . " не має замовлень.</p>";
        } else {
            $ordersByDate = [];
            foreach ($orders as $order) {
                $date = date('Y-m-d', $order['record_time'] / 1000);
                if (!isset($ordersByDate[$date])) {
                    $ordersByDate[$date] = [];
                }
                $ordersByDate[$date][] = $order;
            }
            foreach ($ordersByDate as $date => $dailyOrders) {
                echo '<h2>Замовлення ' . $order['login'] . ' за ' . htmlspecialchars($date) . '</h2>';
                echo '<table>';
                echo '<tr>
                <th width="70px">№</th>
                <th width="140px">Час замовлення</th>
                <th>Список товарів</th>
                <th width="220px">Прибуток за замовлення</th>
              </tr>';
                $dailyProfit = 0;
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
                    $productTexts = array_column($productDetails, 'text');
                    echo implode('<br>', $productTexts);
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
        echo "<p>Оберіть для пошуку.</p>";
    } ?>
</div>
