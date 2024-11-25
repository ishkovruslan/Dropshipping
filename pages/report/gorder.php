<div class="orders">
    <h1>Замовлення за датою</h1>

    <!-- Форма для пошуку -->
    <form method="GET" action="">
        <input type="hidden" name="table" value="gorder" />
        <label for="start_date">Від:</label>
        <input type="date" id="start_date" name="start_date"
            value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>" />
        <label for="end_date">До:</label>
        <input type="date" id="end_date" name="end_date"
            value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" />
        <input type="submit" value="Пошук" />
    </form>

    <?php
    // Отримуємо фільтри
    $searchFilters = [
        'start_date' => isset($_GET['start_date']) ? trim($_GET['start_date']) : '',
        'end_date' => isset($_GET['end_date']) ? trim($_GET['end_date']) : '',
    ];

    // Перевіряємо, чи задано обидва фільтри для пошуку
    if (!empty($searchFilters['start_date']) && !empty($searchFilters['end_date'])) {
        $currentUserLogin = $_SESSION['login'];
        $orders = orders($db, $currentUserLogin, $searchFilters);
        // Фільтруємо замовлення за діапазоном дат
        $orders = array_filter($orders, function ($order) use ($searchFilters) {
            $orderDate = date('Y-m-d', $order['record_time'] / 1000);
            return $orderDate >= $searchFilters['start_date'] && $orderDate <= $searchFilters['end_date'];
        });

        if (empty($orders)) {
            echo "<p>Замовлення не знайдено.</p>";
        } else {
            // Групуємо замовлення за логінами користувачів
            $ordersByUsers = [];
            foreach ($orders as $order) {
                $login = $order['login'];
                if (!isset($ordersByUsers[$login])) {
                    $ordersByUsers[$login] = [];
                }
                $ordersByUsers[$login][] = $order;
            }

            // Сортуємо користувачів за алфавітом
            ksort($ordersByUsers);
            // Виводимо замовлення по користувачах
            foreach ($ordersByUsers as $login => $userOrders) {
                echo '<h2>Замовлення ' . htmlspecialchars($login) . ' з ' . htmlspecialchars($searchFilters['start_date']) . ' по ' . htmlspecialchars($searchFilters['end_date']) . '</h2>';
                echo '<table>';
                echo '<tr>
                    <th width="70px">№</th>
                    <th width="200px">Час замовлення</th>
                    <th>Список товарів</th>
                    <th width="220px">Прибуток за замовлення</th>
                  </tr>';
                $totalProfit = 0; // Змінна для підрахунку загального прибутку
                foreach ($userOrders as $order) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($order['id']) . '</td>';
                    echo '<td>' . date('Y-m-d H:i:s', $order['record_time'] / 1000) . '</td>';
                    echo '<td>';
                    $products = explode(',', $order['products_list']);
                    $quantities = explode(',', $order['products_number']);
                    $prices = explode(',', $order['products_price']);
                    $realization = explode(',', $order['products_realization']);
                    $productDetails = order($db, $products, $quantities, $prices, $realization);
                    // Формування HTML для списку продуктів
                    $productTexts = array_column($productDetails, 'text');
                    echo implode('<br>', $productTexts);
                    foreach ($productDetails as $detail) {
                        $totalProfit += $detail['total'];
                    }
                    echo '</td>';
                    echo '<td>' . htmlspecialchars($totalProfit) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '<h3>Прибуток ' . htmlspecialchars($login) . ' з ' . htmlspecialchars($searchFilters['start_date']) . ' по ' . htmlspecialchars($searchFilters['end_date']) . ': ' . $totalProfit . '</h3>';
            }
        }
    } else {
        // Якщо фільтри порожні, повідомляємо користувача
        echo "<p>Введіть діапазон дат для пошуку.</p>";
    } ?>
</div>
