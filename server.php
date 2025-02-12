<?php
header('Content-Type: application/json');

require_once 'php/server.php';
define('SOURCE_TYPE', 'API');
require_once 'php/autorun/blacklist.php';

$input = json_decode(file_get_contents('php://input'), true);

$login = $input['login'] ?? '';

if($authentication->isBlocked($login, $_SERVER['REMOTE_ADDR'])){ 
    // Блокування логіну шляхом зміни ключа
    // Головна мета унеможливити брутфорс ключа
    $newKey = $remoteAccess->changeKey("API", $login);
}

$encryptedTime = $input['encrypted_time'] ?? '';
$encryptedQuery = $input['encrypted_query'] ?? '';

if (!$login || !$encryptedTime || !$encryptedQuery) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Отримуємо "старий" ключ для поточного запиту
$key = $remoteAccess->manageRemoteAccess("API", $login);

// Розшифровуємо час запиту
$decryptedTime = (int) decrypt($encryptedTime, $key);
$microtime = microtime(true);
$currentTime = (int) ($microtime * 1e9);

if (abs($currentTime - $decryptedTime) > 2.5 * 1000000000) { // Секунди * 9 знаків
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Розшифровуємо запит (параметр encrypted_query шифрувався часом, який клієнт передавав)
$decryptedQuery = json_decode(decrypt($encryptedQuery, $key * $decryptedTime), true);

if (!isset($decryptedQuery['operation'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid query']);
    exit;
}

$operation = $decryptedQuery['operation'];
$result = operation($operation, $decryptedQuery);

// Шифруємо результат за допомогою "старого" ключа
$encryptedResult = encrypt(json_encode($result), $key * $decryptedTime);

// Генеруємо новий ключ для користувача
$newKey = $remoteAccess->changeKey("API", $login);

// Перетворюємо новий ключ в рядок і шифруємо його за допомогою "старого" ключа
$encryptedNewKey = rtrim(encrypt((string)$newKey, $key * $decryptedTime), '=');

http_response_code(200);
echo json_encode([
    'encrypted_result' => $encryptedResult,
    'new_key' => $encryptedNewKey
]);
?>
