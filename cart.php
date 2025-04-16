<?php
header('Content-Type: application/json');

// Підключення бібліотек API та обробки замовлень
require_once 'php/api.php';
define('SOURCE_TYPE', 'API');
require_once 'php/autorun/blacklist.php';

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

/* http_response_code(200);
echo json_encode([
    'new_key' => $key,
    'decryptedQuery' => $decryptedQuery,
    'decryptedTime' => $decryptedTime,
    'microtime' => $microtime,
    'currentTime' => $currentTime,
    'delta' => ($decryptedTime < $currentTime ? $currentTime - $decryptedTime : $decryptedTime - $currentTime) / 1e9
]);
exit; */

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

function createOrderAPI($db, $orderData) {
    require_once 'php/mysql.php'; /* Підключення до БД */
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

    // Перевірка наявності споживача в БД за унікальним іменем (альтернативно можна використовувати email)
    $existingConsumer = $db->read('consumer', ['*'], ['full_name' => $consumerData['full_name']]);
    if ($existingConsumer) {
        $db->update('consumer', $consumerData, ['id' => $existingConsumer[0]['id']]);
    } else {
        $db->write('consumer', array_keys($consumerData), array_values($consumerData), 'ssssss');
    }

    // Обробка даних замовлення
    // Очікується, що дані кошика передаються у вигляді асоціативного масиву
    $cart = $orderData['cart'];
    $productIds = array_keys($cart);
    $productsCount = count($cart);
    $productsList = implode(",", $productIds);
    $productsNumber = implode(",", array_map(function($item) { return $item['quantity']; }, $cart));
    $productsRealization = implode(",", array_map(function($item) { return $item['realization_price']; }, $cart));
    $productsPrice = implode(",", array_map(function($item) { return $item['low_price']; }, $cart));

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

    // Запис замовлення в таблицю orders. Рядок форматування типів (наприклад, 'sssssssssssss') залежить від структури таблиці.
    if ($db->write('orders', array_keys($orderDataArray), array_values($orderDataArray), 'sssssssssssss')) {
        return ['error' => 'Помилка: Не вдалося додати замовлення. Зверніться до адміністратора'];
    }

    // Оновлення залишків товарів у БД
    foreach ($cart as $productId => $item) {
        $result = $db->read('products', ['count'], ['id' => $productId]);
        if (!empty($result)) {
            $newCount = max(0, (int)$result[0]['count'] - $item['quantity']);
            $db->update('products', ['count' => $newCount], ['id' => $productId]);
        }
    }

    return ['success' => true, 'message' => 'Замовлення успішно оформлено'];
}
?>