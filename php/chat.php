<?php
// Визначення ключа користувача (завжди ключ користувача, навіть якщо відправник - адміністратор)
function getEncryptionKey($db, $sender, $receiver)
{
    global $accessControl;

    $currentUser = $_SESSION['login'];

    if ($accessControl->getUserLevel($currentUser) >= 2) {
        // Якщо користувач - адміністратор
        $user = $receiver !== 'administrator' ? $receiver : $sender;
    } else {
        // Звичайний користувач: ключ прив’язаний до поточного логіну
        $user = $currentUser;
    }

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

function getSenderName($sender, $accessControl)
{
    // Якщо відправник — адміністратор, завжди показуємо як "Адміністратор"
    return $sender === 'administrator' ? 'Адміністратор' : $sender;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $sourceTime = round(microtime(true) * 1000);

    $currentUser = $_SESSION['login'] ?? null;
    $targetUser = $_GET['user'] ?? "administrator";

    // Отримання ключа користувача
    $key = getEncryptionKey($db, $currentUser, $targetUser);
    $key = generateXORKey($key, $sourceTime);
    $encryptedMessage = encryptMessage($message, $key);

    // Підміна значення для адміністратора
if ($accessControl->getUserLevel($currentUser) >= 2) {
    $currentUser = "administrator";
}
if ($accessControl->getUserLevel($targetUser) >= 2) {
    $targetUser = "administrator";
}

// Збереження повідомлення
$db->saveMessage($currentUser, $targetUser, $encryptedMessage, $sourceTime);

    header("Location: chat.php?user=" . urlencode($targetUser));
    exit;
}
?>