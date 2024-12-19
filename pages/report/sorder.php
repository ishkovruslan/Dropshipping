<div class="orders"> <!-- Вивід одного замовлення -->
    <h1>Замовлення за номером</h1>
    <form method="GET" action="">
        <input type="hidden" name="table" value="sorder" />
        <input type="text" name="id" placeholder="Номер замовлення"
            value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>" />
        <input type="submit" value="Пошук" />
    </form>

    <?php $searchFilters = [
        'id' => isset($_GET['id']) ? trim($_GET['id']) : '',
    ];
    if (!empty($searchFilters['id'])) {
        $currentUserLogin = $_SESSION['login'];
        $orders = orders($db, $currentUserLogin, $searchFilters);
        $orders = array_filter($orders, function ($order) use ($searchFilters) {
            return (string) $order['id'] === $searchFilters['id'];
        });
        if (empty($orders)) {
            echo "<p>Замовлення не знайдено.</p>";
        } else {
            $order = reset($orders);
            echo '<h2>Замовлення №' . $order['id'] . ' користувача ' . $order['login'] . ' за ' . date('Y-m-d H:i:s', $order['record_time'] / 1000) . '</h2>';
            echo '<table>';
            echo '<tr>
                    <th>Кількість</th>
                    <th>Назва</th>
                    <th>Мінімальна ціна</th>
                    <th>Роздрібна ціна</th>
                    <th>Прибуток з штуки</th>
                    <th>Прибуток з позиції</th>
                </tr>';
            $products = explode(',', $order['products_list']);
            $quantities = explode(',', $order['products_number']);
            $prices = explode(',', $order['products_price']);
            $realizationPrices = explode(',', $order['products_realization']);
            $productDetails = order($db, $products, $quantities, $prices, $realizationPrices);
            $profit = 0;
            foreach ($productDetails as $detail) {
                echo '<tr>';
                echo '<td>' . $detail['quantity'] . '</td>';
                echo '<td>' . $detail['name'] . '</td>';
                echo '<td>' . $detail['price'] . '</td>';
                echo '<td>' . $detail['realization'] . '</td>';
                echo '<td>' . $detail['delta'] . '</td>';
                echo '<td>' . $detail['total'] . '</td>';
                echo '</tr>';
                $profit += $detail['total'];
            }
            echo '</table>';
            echo '<h3>Отриманий прибуток: ' . $profit . '</h3>';
            echo '<h2>Данні отримувача</h2>';
            echo '<table>';
            echo '<tr>
                <th>ПІБ</th>
                <th width="140px">Телефон</th>
                <th>Email</th>
                <th width="200px">Місто</th>
                <th width="140px">Поштовий оператор</th>
                <th width="140px">Номер відділення</th>
              </tr>';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($order['phone']) . '</td>';
            echo '<td>' . htmlspecialchars($order['email']) . '</td>';
            echo '<td>' . htmlspecialchars($order['city']) . '</td>';
            echo '<td>' . htmlspecialchars($order['post']) . '</td>';
            echo '<td>' . htmlspecialchars($order['post_number']) . '</td>';
            echo '</tr>';
            echo '</table>';
        }
    } else {
        echo "<p>Введіть номер замовлення для пошуку.</p>";
    } ?>
</div>