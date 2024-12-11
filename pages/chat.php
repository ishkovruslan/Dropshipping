<?php
require_once('header.php');
require_once('../php/chat.php');
$accessControl->checkAccess(1);

$adminLogin = 'Kharkiv'; // Замініть на логін адміністратора
$currentUser = $_SESSION['login'];
$userLevel = $accessControl->getUserLevel($currentUser);

// Лише адміністратор може використовувати аргументи в посиланні
if ($userLevel >= 2) {
    $targetUser = $_GET['user'] ?? null;
} else {
    $targetUser = $adminLogin;
}

// Перевірка: чи користувач намагається написати комусь, крім адміністратора
if ($userLevel < 2 && $targetUser !== $adminLogin) {
    die("Недостатньо прав для написання цьому користувачу.");
}

// Подвійна перевірка цільового логіна
if (!$db->checkUserExists($targetUser)) {
    die("Цільовий користувач не знайдений.");
}

// Отримання повідомлень між поточним користувачем і адресатом
$messages = $db->readMessages($currentUser, $targetUser);

// Розшифрування повідомлень
foreach ($messages as &$message) {
    $key = getEncryptionKey($db, $message['sender'], $message['receiver']);
    $key = generateXORKey($key, $message['source_time']);
    $message['message'] = decryptMessage($message['message'], $key);
}
?>

<div class="chat-interface">
    <h1>Чат з <?php echo htmlspecialchars($targetUser); ?></h1>
    <div class="messages">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender'] === $currentUser ? 'sent' : 'received'; ?>">
                <!-- Додавання імені відправника перед текстом повідомлення -->
                <strong><?php echo getSenderName($message['sender'], $accessControl, $adminLogin); ?>:</strong>
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
<?php require_once('../php/footer.php'); ?>
