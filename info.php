<?php /* Інформаційне API */
header('Content-Type: application/json');
define('SOURCE_TYPE', 'API');
$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';

require_once('functions/mysql.php'); /* Підключення до БД */
require_once 'autorun/blacklist.php';
require_once('class/authentication.php');
if ($authentication->isBlocked($login, $_SERVER['REMOTE_ADDR'])) {
    /* Блокування логіну шляхом зміни ключа */
    /* Головна мета унеможливити брутфорс ключа */
    $newKey = $remoteAccess->changeKey("API", $login);
}

$encryptedTime = $input['encrypted_time'] ?? '';
$encryptedQuery = $input['encrypted_query'] ?? '';

if (!$login || !$encryptedTime || !$encryptedQuery) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

/* Отримуємо "старий" ключ для поточного запиту */
require_once('class/remoteaccess.php');
$key = $remoteAccess->manageRemoteAccess("API", $login);

/* Розшифровуємо час запиту */
require_once 'functions/crypto.php';
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

if (abs($currentTime - $decryptedTime) > 2.5 * 1000000000) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

/* Розшифровуємо запит (параметр encrypted_query шифрувався часом, який клієнт передавав) */
$decryptedQuery = json_decode(decrypt($encryptedQuery, $key), true);

if (!isset($decryptedQuery['operation'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid query']);
    exit;
}

$operation = $decryptedQuery['operation'];
require_once 'functions/info.php';
$result = operation($operation, $decryptedQuery);

/* Шифруємо результат за допомогою "старого" ключа */
$encryptedResult = encrypt(json_encode($result), $key);

/* Генеруємо новий ключ для користувача */
$newKey = $remoteAccess->changeKey("API", $login);

/* Перетворюємо новий ключ в рядок і шифруємо його за допомогою "старого" ключа */
$encryptedNewKey = rtrim(encrypt((string) $newKey, $key), '=');

http_response_code(200);
echo json_encode([
    'encrypted_result' => $encryptedResult,
    'new_key' => $encryptedNewKey
]);
