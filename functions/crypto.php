<?php /* Криптографічні функції */
function encrypt($message, $key)
{
    $cipher = 'aes-256-cbc';
    /* Генеруємо 32-байтовий ключ із використанням SHA-256 */
    $keyHash = hash('sha256', $key, true);
    /* Отримуємо довжину вектора ініціалізації (IV) */
    $ivLength = openssl_cipher_iv_length($cipher);
    /* Генеруємо випадковий IV */
    $iv = openssl_random_pseudo_bytes($ivLength);
    /* Шифруємо повідомлення */
    $encrypted = openssl_encrypt($message, $cipher, $keyHash, OPENSSL_RAW_DATA, $iv);
    /* Об'єднуємо IV з зашифрованими даними та кодуємо їх Base64 */
    return base64_encode($iv . $encrypted);
}

function decrypt($encryptedMessage, $key)
{
    $cipher = 'aes-256-cbc';
    $keyHash = hash('sha256', $key, true);
    $ivLength = openssl_cipher_iv_length($cipher);
    /* Декодуємо Base64 */
    $data = base64_decode($encryptedMessage);
    /* Розділяємо IV і зашифровані дані */
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, $cipher, $keyHash, OPENSSL_RAW_DATA, $iv);
}
