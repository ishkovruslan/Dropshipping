<?php
require_once 'php/access.php';

// Функція для шифрування повідомлення
function encrypt($message, $key)
{
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

// Функція для дешифрування повідомлення
function decrypt($encryptedMessage, $key)
{
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

// Функція для декодування характеристик продукту
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

function operation($operation, $decryptedQuery)
{
    global $db;
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
            return $products;
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
            return $products;
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
            return $products;
            break;

        case 'readCategories':
            return $db->readAll('categories');
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported operation']);
            exit;
    }
}