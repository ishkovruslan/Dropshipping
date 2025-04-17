<?php /* Сторінка сповіщень */
    $conditions = ['count <=' => 5];
    $orderBy = ['count' => 'ASC'];
    $lowStockProducts = $db->readWithSort('products', ['id', 'product_name', 'count'], $conditions, $orderBy);
    $oneWeekAgo = date('Y-m-d', strtotime('-1 week'));
    $existingAlerts = $db->readWithSort(
        'alerts',
        ['id', 'description', 'date'],
        ['date >=' => $oneWeekAgo],
        []
    );
    $alertsMap = [];
    foreach ($existingAlerts as $alert) {
        if (preg_match('/id:(\d+)$/', $alert['description'], $matches)) {
            $productId = $matches[1];
            $alertsMap[$productId] = ['id' => $alert['id'], 'description' => $alert['description']];
        }
    }
    foreach ($lowStockProducts as $product) {
        if ($product['count'] == 0) {
            $operation = 'Відсутність';
            $description = $product['product_name'] . ' - відсутність на складі - id:' . $product['id'];
        } else {
            $operation = 'Обмежена кількість';
            $description = $product['product_name'] . ' в наявності лише: ' . $product['count'] . ' - id:' . $product['id'];
        }
        if (isset($alertsMap[$product['id']])) {
            $alertId = $alertsMap[$product['id']]['id'];
            $db->update('alerts', ['operation' => $operation, 'description' => $description, 'date' => date('Y-m-d')], ['id' => $alertId]);
        } else {
            $columns = ['operation', 'description', 'date'];
            $values = [$operation, $description, date('Y-m-d')];
            $types = 'sss';
            $db->write('alerts', $columns, $values, $types);
        }
        unset($alertsMap[$product['id']]);
    }
    if (!empty($alertsMap)) {
        foreach ($alertsMap as $productId => $alert) {
            $db->remove('alerts', ['id'], [$alert['id']]);
        }
    }
?>