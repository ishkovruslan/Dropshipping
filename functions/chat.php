<?php /* Функції чату */
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
    $encryptedMessage = encrypt($message, $key);

    $db->saveMessage($currentUser, $targetUser, $encryptedMessage, $sourceTime);

    if ($accessControl->getUserLevel($currentUser) >= 2) {
        logAction($db, 'Повідомлення', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Адміністратор ' . $_SESSION['login'] . ' відправив повідомлення користувачу ' . $targetUser);
    }
    header("Location: chat.php?user=" . urlencode($targetUser));
    exit;
}
