<?php /* Сторінка чатів */
require_once('header.php'); /* Навігаційне меню */
$accessControl->checkAccess(1); /* Спілкуватись можуть лише продавці та адміністратори */
$currentUser = $_SESSION['login'] ?? null;
$userLevel = $accessControl->getUserLevel($currentUser);

/* Лише адміністратор може використовувати аргументи в посиланні */
if ($userLevel >= 2) {
    $targetUser = $_GET['user'] ?? null;
} else {
    $targetUser = 'administrator';
}

/* Перевірка: чи користувач намагається написати комусь, крім адміністратора */
if ($userLevel < 2 && $targetUser !== 'administrator') {
    die("Недостатньо прав для написання цьому користувачу.");
}

/* Отримання повідомлень між поточним користувачем і адресатом */
$messages = $db->readMessagesForRole($currentUser, $targetUser, $userLevel);

/* Розшифрування повідомлень */
require_once('../functions/crypto.php'); /* Функції для роботи чату */
require_once('../functions/chat.php'); /* Функції для роботи чату */
foreach ($messages as &$message) {
    $keyOwner = $message['receiver'] === 'administrator' ? $message['sender'] : $message['receiver'];
    $key = getEncryptionKey($db, $message['sender'], $keyOwner);
    $key = XORKey($key, $message['source_time']);
    $message['message'] = decrypt($message['message'], $key);
}
unset($message);
?>

<div class="chat-interface"> <!-- Вивід повідомлень -->
    <h1>Чат з <?php echo htmlspecialchars($targetUser === 'administrator' ? 'Адміністратором' : $targetUser); ?></h1>
    <div class="messages">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender'] === $currentUser ? 'sent' : 'received'; ?>">
                <strong><?php echo getSenderName($message['sender']); ?>:</strong>
                <span><?php echo htmlspecialchars($message['message']); ?></span>
                <time><?php echo date("Y-m-d H:i:s", substr($message['source_time'], 0, -3)); ?></time>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="post" class="message-form">
        <textarea name="message" required></textarea>
        <button type="submit">Відправити</button>
    </form>
</div>
<?php require_once('footer.php');
