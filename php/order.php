<?php
// Функція для отримання замовлень з урахуванням прав доступу
function orders($db, $userLogin, $filters = [])
{
    $userData = $db->read('userlist', ['role'], ['login' => $userLogin]);
    if (empty($userData)) {
        return []; // Користувача не знайдено
    }

    $role = $userData[0]['role'];
    $ordersData = $db->readAll('orders');

    $filteredOrders = array_filter($ordersData, function ($order) use ($role, $userLogin, $filters) {
        $matchesId = empty($filters['id']) || strpos((string) $order['id'], $filters['id']) !== false;
        $matchesDate = empty($filters['date']) || strpos(date('Y-m-d', $order['record_time'] / 1000), $filters['date']) !== false;
        $matchesLogin = empty($filters['login']) || $order['login'] === $filters['login'];
        $hasAccess = ($role === 'administrator') || $order['login'] === $userLogin;

        return $matchesId && $matchesDate && $matchesLogin && $hasAccess;
    });

    return $filteredOrders;
}

function order($db, $products, $quantities, $prices, $realizations)
{
    $output = [];
    for ($i = 0; $i < count($products); $i++) {
        $productId = $products[$i] ?? 0;
        $quantity = $quantities[$i] ?? 0;
        $price = $prices[$i] ?? 0;
        $realization= $realizations[$i] ?? 0;

        // Отримання назви товару з бази даних
        $productData = $db->read('products', ['product_name'], ['id' => $productId]);
        $productName = !empty($productData) ? $productData[0]['product_name'] : "Невідомий товар";

        // Обчислення прибутку
        $delta = $realization - $price;
        $total = $delta * $quantity;

        $output[] = [
            'text' => "{$quantity} x {$productName}",
            'quantity' => $quantity,
            'name' => $productName,
            'price' => $price,
            'realization' => $realization,
            'delta' => $delta,
            'total' => $total
        ];
    }
    return $output;
}
?>
