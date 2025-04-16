<?php
require_once 'mysql.php'; /* Підключення до БД */

/* Функція оформлення замовлення */
function createOrder($db) {
    // Перевірка обов’язкових полів
    $requiredFields = ['full_name', 'phone', 'email', 'post_type', 'city', 'post_number'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            exit("Помилка: Поле $field є обов'язковим.");
        }
    }

    // Формування даних споживача
    $consumerData = [
        'full_name'   => $_POST['full_name'],
        'phone'       => $_POST['phone'],
        'email'       => $_POST['email'],
        'post'        => $_POST['post_type'],
        'city'        => $_POST['city'],
        'post_number' => $_POST['post_number']
    ];

    // Запис або оновлення даних споживача в таблиці
    $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $consumerData['full_name']]);
    if ($existingConsumer) {
        $db->update('consumer', $consumerData, ['id' => $existingConsumer[0]['id']]);
    } else {
        $db->write('consumer', array_keys($consumerData), array_values($consumerData), 'ssssss');
    }

    // Формування даних замовлення
    $orderData = [
        'login'                => $_SESSION['login'],
        'record_time'          => (int)(microtime(true) * 1000),
        'products_count'       => count($_SESSION['cart']),
        'products_list'        => implode(",", array_keys($_SESSION['cart'])),
        'products_number'      => implode(",", array_column($_SESSION['cart'], 'quantity')),
        'products_realization' => implode(",", array_column($_SESSION['cart'], 'realization_price')),
        'products_price'       => implode(",", array_column($_SESSION['cart'], 'low_price'))
    ] + $consumerData;

    // Запис замовлення в таблицю
    if (!$db->write('orders', array_keys($orderData), array_values($orderData), 'sssssssssssss')) {
        // Оновлення кількості товарів
        foreach ($_SESSION['cart'] as $id => $item) {
            $result = $db->read('products', ['count'], ['id' => $id]);
            if (!empty($result)) {
                $newCount = max(0, (int)$result[0]['count'] - $item['quantity']);
                $db->update('products', ['count' => $newCount], ['id' => $id]);
            }
        }
        unset($_SESSION['cart']); // Очищення кошика
        header("location: ../index.php"); // Перехід на головну сторінку
    } else {
        exit('Помилка: Не вдалося додати замовлення. Зверніться до адміністратора');
    }
}

/* Існуючий код для обробки інших дій */
$action = $_GET['action'] ?? null;

if ($action === 'search') {
    $search = $_GET['query'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    $results = $db->searchLike('consumer', ['id', 'full_name'], 'full_name', $search, $limit);
    echo json_encode($results);
} elseif ($action === 'details') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $results = $db->read('consumer', ['*'], ['id' => $id]);
        echo json_encode($results[0] ?? []);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}