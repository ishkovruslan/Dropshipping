<?php
header('Content-Type: application/json');

// Підключення бібліотек API та обробки замовлень
require_once 'functions/api.php';
define('SOURCE_TYPE', 'API');
require_once 'autorun/blacklist.php';

// Зчитування JSON-вхідних даних
$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';

// Перевірка блокування по логіну/IP
if ($authentication->isBlocked($login, $_SERVER['REMOTE_ADDR'])) {
    // Якщо користувача блокується, генеруємо новий ключ для унеможливлення брутфорсу
    $newKey = $remoteAccess->changeKey("API", $login);
}

$encryptedTime = $input['encrypted_time'] ?? '';
$encryptedQuery = $input['encrypted_query'] ?? '';

if (!$login || !$encryptedTime || !$encryptedQuery) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Отримання існуючого ключа для розшифрування
$key = $remoteAccess->manageRemoteAccess("API", $login);

// Розшифровка часу запиту
$decryptedTime = (int) decrypt($encryptedTime, $key);
$microtime = microtime(true);
$currentTime = (int) ($microtime * 1e9);

// Перевірка валідності часу запиту (поріг 2.5 секунд)
if (abs($currentTime - $decryptedTime) > 2.5 * 1e9) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Розшифровка запиту (JSON) із використанням ключа
$decryptedQuery = json_decode(decrypt($encryptedQuery, $key), true);

if (!isset($decryptedQuery['operation'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid query']);
    exit;
}

$operation = $decryptedQuery['operation'];

switch ($operation) {
    case 'createOrder':
        if (!isset($decryptedQuery['orderData'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Order data required']);
            exit;
        }
        $orderData = $decryptedQuery['orderData'];
        // Очікуємо, що об'єкт бази даних ($db) глобально доступний
        $result = createOrderAPI($db, $orderData);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported operation']);
        exit;
}

// Шифрування результату запиту
$encryptedResult = encrypt(json_encode($result), $key);
// Генерація нового ключа для користувача
$newKey = $remoteAccess->changeKey("API", $login);
$encryptedNewKey = rtrim(encrypt((string)$newKey, $key), '=');

http_response_code(200);
echo json_encode([
    'encrypted_result' => $encryptedResult,
    'new_key' => $encryptedNewKey
]);

/**
 * Функція оформлення замовлення через API (адаптовано для декількох товарів)
 */
function createOrderAPI($db, $orderData) {
    require_once 'functions/mysql.php'; /* Підключення до БД */
    // Перевірка обов'язкових полів
    $requiredFields = ['full_name', 'phone', 'email', 'post_type', 'city', 'post_number', 'cart'];
    foreach ($requiredFields as $field) {
        if (!isset($orderData[$field]) || empty($orderData[$field])) {
            return ['error' => "Помилка: Поле $field є обов'язковим."];
        }
    }

    // Формування даних споживача
    $consumerData = [
        'full_name'   => $orderData['full_name'],
        'phone'       => $orderData['phone'],
        'email'       => $orderData['email'],
        'post'        => $orderData['post_type'],
        'city'        => $orderData['city'],
        'post_number' => $orderData['post_number']
    ];

    // Перевірка наявності споживача в БД за унікальним ім'ям (можна використовувати і email)
    $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $consumerData['full_name']]);
    if ($existingConsumer) {
        $db->update('consumer', $consumerData, ['id' => $existingConsumer[0]['id']]);
    } else {
        $db->write('consumer', array_keys($consumerData), array_values($consumerData), 'ssssss');
    }

    // Обробка даних замовлення для декількох товарів
    $cart = $orderData['cart'];
    // Якщо клієнт передав поле productsCount, використовуємо його, інакше обчислюємо як кількість рядків у кошику
    $productsCount = isset($orderData['productsCount']) ? (int)$orderData['productsCount'] : count($cart);
    $productIds = array_keys($cart);
    $productsList = implode(",", $productIds);
    $productsNumber = implode(",", array_map(function($item) { return $item['quantity']; }, $cart));
    $productsRealization = implode(",", array_map(function($item) { return $item['realization_price']; }, $cart));
    
    // Отримання актуальної мінімальної ціни для кожного товару з бази даних
    $prices = [];
    foreach ($productIds as $pid) {
        $result = $db->read('products', ['price'], ['id' => $pid]);
        if (!empty($result)) {
            $prices[] = $result[0]['price'];
        } else {
            // Якщо запис не знайдено, використовуємо значення з кошика як резервне
            $prices[] = $cart[$pid]['low_price'];
        }
    }
    $productsPrice = implode(",", $prices);

    // Формування масиву даних замовлення
    $orderDataArray = [
        'login'                => isset($orderData['login']) ? $orderData['login'] : '',
        'record_time'          => (int)(microtime(true) * 1000),
        'products_count'       => $productsCount,
        'products_list'        => $productsList,
        'products_number'      => $productsNumber,
        'products_realization' => $productsRealization,
        'products_price'       => $productsPrice,
        'full_name'            => $consumerData['full_name'],
        'phone'                => $consumerData['phone'],
        'email'                => $consumerData['email'],
        'post'                 => $consumerData['post'],
        'city'                 => $consumerData['city'],
        'post_number'          => $consumerData['post_number']
    ];

    // Запис замовлення в таблицю orders. Рядок форматування типів ('sssssssssssss') повинен відповідати структурі таблиці.
    if (!$db->write('orders', array_keys($orderDataArray), array_values($orderDataArray), 'sssssssssssss')) {
        // Оновлення залишків товарів у БД
        foreach ($cart as $productId => $item) {
            $result = $db->read('products', ['count'], ['id' => $productId]);
            if (!empty($result)) {
                $newCount = max(0, (int)$result[0]['count'] - $item['quantity']);
                $db->update('products', ['count' => $newCount], ['id' => $productId]);
            }
        }
    } else {
        return ['error' => 'Помилка: Не вдалося додати замовлення. Зверніться до адміністратора'];
    }

    return ['success' => true, 'message' => 'Замовлення успішно оформлено'];
}
?>
