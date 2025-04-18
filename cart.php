<?php /* API замовлень */
header('Content-Type: application/json');
define('SOURCE_TYPE', 'API');
$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';

require_once 'functions/mysql.php';
require_once 'autorun/blacklist.php';

/* Перевірка блокування по логіну/IP */
require_once('class/authentication.php');
if ($authentication->isBlocked($login, $_SERVER['REMOTE_ADDR'])) {
    /* Якщо користувача блокується, генеруємо новий ключ для унеможливлення брутфорсу */
    $newKey = $remoteAccess->changeKey("API", $login);
}

$encryptedTime = $input['encrypted_time'] ?? '';
$encryptedQuery = $input['encrypted_query'] ?? '';

if (!$login || !$encryptedTime || !$encryptedQuery) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

/* Отримання існуючого ключа для розшифрування */
require_once('class/remoteaccess.php');
$key = $remoteAccess->manageRemoteAccess("API", $login);

/* Розшифровка часу запиту */
require_once 'functions/crypto.php';
$decryptedTime = (int) decrypt($encryptedTime, $key);
$microtime = microtime(true);
$currentTime = (int) ($microtime * 1e9);

/* Перевірка валідності часу запиту (поріг 2.5 секунди) */
if (abs($currentTime - $decryptedTime) > 2.5 * 1e9) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

/* Розшифровка запиту (JSON) із використанням ключа */
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
        /* Очікуємо, що об'єкт бази даних ($db) глобально доступний */
        require_once 'functions/cart.php';
        $result = createOrderAPI($db, $orderData);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported operation']);
        exit;
}

/* Шифрування результату запиту */
$encryptedResult = encrypt(json_encode($result), $key);
/* Генерація нового ключа для користувача */
$newKey = $remoteAccess->changeKey("API", $login);
$encryptedNewKey = rtrim(encrypt((string) $newKey, $key), '=');

http_response_code(200);
echo json_encode([
    'encrypted_result' => $encryptedResult,
    'new_key' => $encryptedNewKey
]);
