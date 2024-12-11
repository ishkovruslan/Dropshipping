<?php
// Визначення ключа користувача (завжди ключ користувача, навіть якщо відправник - адміністратор)
function getEncryptionKey($db, $sender, $receiver)
{
    global $accessControl;

    // Якщо це користувач, використовуємо його ключ
    if ($accessControl->getUserLevel($_SESSION['login']) <= 1) {
        $user = $_SESSION['login']; // Відправник або отримувач
    } else {
        // Якщо адміністратор, ключ прив’язаний до користувача
        $user = ($sender !== $_SESSION['login']) ? $sender : $receiver;
    }

    // Отримання ключа (registration_time) для користувача
    return $db->getUserRegistrationTime($user);
}

// Генерація XOR-ключа для шифрування/дешифрації
function generateXORKey($registrationTime, $timestamp)
{
    return $registrationTime ^ $timestamp; // Унікальний 64-бітний ключ
}

// Шифрування повідомлення
function encryptMessage($message, $key)
{
    $keyStr = pack('J', $key); // Конвертуємо 64-бітний ключ у рядок
    $keyLen = strlen($keyStr);
    $encrypted = '';

    for ($i = 0; $i < strlen($message); $i++) {
        $messageChar = ord($message[$i]); // Поточний символ повідомлення
        $keyChar = ord($keyStr[$i % $keyLen]); // Поточний символ ключа
        $encrypted .= chr(($messageChar + $keyChar) % 256); // Алгоритм Віженера
    }

    return base64_encode($encrypted); // Закодувати в Base64
}

// Дешифрування повідомлення
function decryptMessage($encryptedMessage, $key)
{
    $encrypted = base64_decode($encryptedMessage); // Декодувати Base64
    $keyStr = pack('J', $key); // Конвертуємо 64-бітний ключ у рядок
    $keyLen = strlen($keyStr);
    $decrypted = '';

    for ($i = 0; $i < strlen($encrypted); $i++) {
        $encryptedChar = ord($encrypted[$i]); // Поточний символ зашифрованого повідомлення
        $keyChar = ord($keyStr[$i % $keyLen]); // Поточний символ ключа
        $decrypted .= chr(($encryptedChar - $keyChar + 256) % 256); // Зворотний алгоритм Віженера
    }

    return $decrypted; // Повернути розшифроване повідомлення
}

// Функція для отримання імені відправника (Адміністратор або логін користувача)
function getSenderName($sender, $accessControl, $adminLogin)
{
    if ($accessControl->getUserLevel($sender) >= 2) {
        return "Адміністратор";
    } else {
        return "$sender";  // Виведення логіну користувача
    }
}

// Відправка нового повідомлення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $sourceTime = round(microtime(true) * 1000);

    // Отримання ключа користувача
    $key = getEncryptionKey($db, $currentUser, $targetUser);
    $key = generateXORKey($key, $sourceTime);
    $encryptedMessage = encryptMessage($message, $key);

    // Збереження повідомлення
    $db->saveMessage($currentUser, $targetUser, $encryptedMessage, $sourceTime);

    header("Location: chat.php?user=" . urlencode($targetUser));
    exit;
}
?>