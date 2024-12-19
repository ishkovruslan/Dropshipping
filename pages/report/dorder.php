<div class="orders"> <!-- Вивід замовлення за день -->
    <h1>Замовлення за датою</h1>
    <form method="GET" action="">
        <input type="hidden" name="table" value="dorder" />
        <input type="date" name="date"
            value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" />
        <input type="submit" value="Пошук" />
    </form>

    <?php $searchFilters = [
        'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
    ];
    if (!empty($searchFilters['date'])) {
        $currentUserLogin = $_SESSION['login'];
        $orders = orders($db, $currentUserLogin, $searchFilters);
        $orders = array_filter($orders, function ($order) use ($searchFilters) {
            return date('Y-m-d', $order['record_time'] / 1000) === $searchFilters['date'];
        });
        if (empty($orders)) {
            echo "<p>Замовлення не знайдено.</p>";
        } else {
            $ordersByUsers = [];
            foreach ($orders as $order) {
                $login = $order['login'];
                if (!isset($ordersByUsers[$login])) {
                    $ordersByUsers[$login] = [];
                }
                $ordersByUsers[$login][] = $order;
            }
            ksort($ordersByUsers);
            foreach ($ordersByUsers as $login => $userOrders) {
                echo '<h2>Замовлення ' . htmlspecialchars($login) . ' за ' . htmlspecialchars($searchFilters['date']) . '</h2>';
                echo '<table>';
                echo '<tr>
                <th width="70px">№</th>
                <th width="140px">Час замовлення</th>
                <th>Список товарів</th>
                <th width="220px">Прибуток за замовлення</th>
              </tr>';
                $dailyProfit = 0;
                foreach ($userOrders as $order) {
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
                    echo '</td>';
                    echo '<td>' . htmlspecialchars($dailyProfit) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '<h3>Прибуток ' . htmlspecialchars($login) . ' за ' . htmlspecialchars($searchFilters['date']) . ': ' . $dailyProfit . '</h3>';
            }
        }
    } else {
        echo "<p>Введіть дату замовлення для пошуку.</p>";
    } ?>
</div>