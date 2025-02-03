<?php
header('Content-Type: application/json');

require_once 'php/mysql.php';
require_once 'php/access.php';

function encrypt($message, $key)
{ /* Шифрування повідомлення */
    $keyStr = pack('J', $key);
    $keyLen = strlen($keyStr);
    $encrypted = '';

    for ($i = 0; $i < strlen($message); $i++) {
        $messageChar = ord($message[$i]);
        $keyChar = ord($keyStr[$i % $keyLen]);
        $encrypted .= chr(($messageChar + $keyChar) % 256);
    }

    return base64_encode($encrypted);
}

function decrypt($encryptedMessage, $key)
{ /* Дешифрування повідомлення */
    $encrypted = base64_decode($encryptedMessage);
    $keyStr = pack('J', $key);
    $keyLen = strlen($keyStr);
    $decrypted = '';

    for ($i = 0; $i < strlen($encrypted); $i++) {
        $encryptedChar = ord($encrypted[$i]);
        $keyChar = ord($keyStr[$i % $keyLen]);
        $decrypted .= chr(($encryptedChar - $keyChar + 256) % 256);
    }

    return $decrypted;
}

$input = json_decode(file_get_contents('php://input'), true);

$login = $input['login'] ?? '';
$encryptedTime = $input['encrypted_time'] ?? '';
$encryptedQuery = $input['encrypted_query'] ?? '';

if (!$login || !$encryptedTime || !$encryptedQuery) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$key = $remoteAccess->manageRemoteAccess("API", $login);

$decryptedTime = (int)decrypt($encryptedTime, $key);
$currentTime = time();

if (abs($currentTime - $decryptedTime) > 5) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$decryptedQuery = json_decode(decrypt($encryptedQuery, $decryptedTime), true);

if (!isset($decryptedQuery['operation'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid query']);
    exit;
}

$operation = $decryptedQuery['operation'];
$result = null;

switch ($operation) {
    case 'readAllProducts':
        $products = $db->readAll('products');
        $categories = $db->readAll('categories');
        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryMap[$category['category_name']] = $category;
        }
        foreach ($products as &$product) {
            if (isset($categoryMap[$product['category']])) {
                $product = decodeCharacteristics($product, $categoryMap[$product['category']]);
            }
        }
        $result = $products;
        break;

    case 'readProduct':
        if (!isset($decryptedQuery['conditions'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Conditions required for readProduct']);
            exit;
        }
        $products = $db->readWithSort('products', ['*'], $decryptedQuery['conditions']);
        $categories = $db->readAll('categories');
        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryMap[$category['category_name']] = $category;
        }
        foreach ($products as &$product) {
            if (isset($categoryMap[$product['category']])) {
                $product = decodeCharacteristics($product, $categoryMap[$product['category']]);
            }
        }
        $result = $products;
        break;

    case 'readCategoryProducts':
        if (!isset($decryptedQuery['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category required for readCategoryProducts']);
            exit;
        }
        $products = $db->readWithSort(
            'products',
            ['*'],
            ['category' => $decryptedQuery['category']]
        );
        $category = $db->read('categories', ['*'], ['category_name' => $decryptedQuery['category']]);
        if (!empty($category)) {
            foreach ($products as &$product) {
                $product = decodeCharacteristics($product, $category[0]);
            }
        }
        $result = $products;
        break;

    case 'readCategories':
        $result = $db->readAll('categories');
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported operation']);
        exit;
}

$encryptedResult = encrypt(json_encode($result), $key);

http_response_code(200);
echo json_encode(['encrypted_result' => $encryptedResult]);
exit;

function decodeCharacteristics($product, $category)
{
    unset($product['uploadPath']); // Видаляємо поле uploadPath

    // Розшифровуємо характеристики
    $characteristics = explode(',', $product['characteristics']);
    $specifications = explode(',', $category['specifications']);

    $decodedCharacteristics = [];
    foreach ($specifications as $index => $spec) {
        $decodedCharacteristics[$spec] = $characteristics[$index] ?? null;
    }
    unset($product['characteristics']); // Видаляємо поле characteristics

    $product['decoded_characteristics'] = $decodedCharacteristics;
    return $product;
}