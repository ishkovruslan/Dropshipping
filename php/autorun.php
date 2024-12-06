<?php
// Перевіряємо, чи поточна хвилина кратна 15
$currentMinute = (int) date('i'); // Отримуємо хвилини у форматі числа
if ($currentMinute % 15 == 0) { // Кожні 15 хвилин при переході будь-куди та будь-кого оновлюємо алерти по низькій кількості товарів. При наявності помірного трафіку навнтаження буде майже відсутнє, а сторінка завжди оновлена
    require_once('mysql.php'); // Підключення до бази
    $conditions = ['count <=' => 5]; // Отримання товарів із кількістю менше 5
    $orderBy = ['count' => 'ASC']; // Сортування за зростанням кількості
    $lowStockProducts = $db->readWithSort('products', ['id', 'product_name', 'count'], $conditions, $orderBy);

    // Отримання записів із таблиці alerts за останній тиждень
    $oneWeekAgo = date('Y-m-d', strtotime('-1 week'));
    $existingAlerts = $db->readWithSort(
        'alerts',
        ['id', 'description', 'date'],
        ['date >=' => $oneWeekAgo],
        []
    );

    // Створення асоціативного масиву існуючих сповіщень з прив'язкою до ID продукту
    $alertsMap = [];
    foreach ($existingAlerts as $alert) {
        // Отримуємо ID товару з опису, якщо він є
        if (preg_match('/id:(\d+)$/', $alert['description'], $matches)) {
            $productId = $matches[1];
            $alertsMap[$productId] = ['id' => $alert['id'], 'description' => $alert['description']];
        }
    }

    // Обробка товарів із низьким запасом
    foreach ($lowStockProducts as $product) {
        if ($product['count'] == 0) {
            $operation = 'Відсутність';
            $description = $product['product_name'] . ' - відсутність на складі - id:' . $product['id'];
        } else {
            $operation = 'Обмежена кількість';
            $description = $product['product_name'] . ' в наявності лише: ' . $product['count'] . ' - id:' . $product['id'];
        }

        if (isset($alertsMap[$product['id']])) {
            // Якщо сповіщення існує, оновлюємо його
            $alertId = $alertsMap[$product['id']]['id'];
            $db->update('alerts', ['operation' => $operation, 'description' => $description, 'date' => date('Y-m-d')], ['id' => $alertId]);
        } else {
            // Якщо запису немає, додаємо його
            $columns = ['operation', 'description', 'date'];
            $values = [$operation, $description, date('Y-m-d')];
            $types = 'sss';
            $db->write('alerts', $columns, $values, $types);
        }

        // Видаляємо оброблений ID зі списку існуючих
        unset($alertsMap[$product['id']]);
    }

    // Видалення записів, які більше не відповідають критеріям
    if (!empty($alertsMap)) {
        foreach ($alertsMap as $productId => $alert) {
            $db->remove('alerts', ['id'], [$alert['id']]);
        }
    }
}
?>