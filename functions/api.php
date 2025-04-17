
<?php
require_once 'access.php';

/**
 * Функція для шифрування повідомлення за допомогою AES-256-CBC.
 */
function encrypt($message, $key)
{
    $cipher = 'aes-256-cbc';
    // Генеруємо 32-байтовий ключ із використанням SHA-256
    $keyHash = hash('sha256', $key, true);
    // Отримуємо довжину вектора ініціалізації (IV)
    $ivLength = openssl_cipher_iv_length($cipher);
    // Генеруємо випадковий IV
    $iv = openssl_random_pseudo_bytes($ivLength);
    // Шифруємо повідомлення
    $encrypted = openssl_encrypt($message, $cipher, $keyHash, OPENSSL_RAW_DATA, $iv);
    // Об'єднуємо IV з зашифрованими даними та кодуємо їх Base64
    return base64_encode($iv . $encrypted);
}

/**
 * Функція для дешифрування повідомлення за допомогою AES-256-CBC.
 */
function decrypt($encryptedMessage, $key)
{
    $cipher = 'aes-256-cbc';
    $keyHash = hash('sha256', $key, true);
    $ivLength = openssl_cipher_iv_length($cipher);
    // Декодуємо Base64
    $data = base64_decode($encryptedMessage);
    // Розділяємо IV і зашифровані дані
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, $cipher, $keyHash, OPENSSL_RAW_DATA, $iv);
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
