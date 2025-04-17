<?php
function getEncryptionKey($db, $sender, $receiver)
{ /* Отримуємо власника ключа */
    global $accessControl;

    $currentUser = $_SESSION['login'];

    if ($accessControl->getUserLevel($currentUser) >= 2) {
        $user = $receiver !== 'administrator' ? $receiver : $sender;
    } else {
        $user = $currentUser;
    }

    return $db->getUserRegistrationTime($user);
}

function XORKey($registrationTime, $timestamp)
{ /* Отримуємо необхідний ключ */
    return $registrationTime ^ $timestamp;
}

function encryptMessage($message, $key)
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

function decryptMessage($encryptedMessage, $key)
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

function getSenderName($sender)
{ /* Вивід імені відправника */
    return $sender === 'administrator' ? 'Адміністратор' : $sender;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $sourceTime = round(microtime(true) * 1000);

    $currentUser = $_SESSION['login'] ?? null;
    $targetUser = $_GET['user'] ?? "administrator";

    $key = getEncryptionKey($db, $currentUser, $targetUser);
    $key = XORKey($key, $sourceTime);
    $encryptedMessage = encryptMessage($message, $key);

    $db->saveMessage($currentUser, $targetUser, $encryptedMessage, $sourceTime);

    require_once('access.php');
    if ($accessControl->getUserLevel($currentUser) >= 2) {
        logAction($db, 'Повідомлення', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Адміністратор ' . $_SESSION['login'] . ' відправив повідомлення користувачу ' . $targetUser);
    }
    header("Location: chat.php?user=" . urlencode($targetUser));
    exit;
}
?>