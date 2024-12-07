<?php
require_once('header.php');
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
$query = "SELECT COUNT(*) AS count FROM userlist WHERE login = ?";
$stmt = $db->conn->prepare($query);
$stmt->bind_param("s", $targetUser);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData['count']) {
    die("Цільовий користувач не знайдений.");
}

// Отримання повідомлень між поточним користувачем і адресатом
$query = "
    SELECT 
        sender, receiver, message, source_time
    FROM messages
    WHERE 
        (sender = ? AND receiver = ?)
        OR (sender = ? AND receiver = ?)
    ORDER BY source_time ASC
";
$stmt = $db->conn->prepare($query);
$stmt->bind_param("ssss", $currentUser, $targetUser, $targetUser, $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

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
    $query = "SELECT registration_time FROM userlist WHERE login = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    return $userData['registration_time'] ?? 0;
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

// Розшифрування повідомлень
foreach ($messages as &$message) {
    $key = getEncryptionKey($db, $message['sender'], $message['receiver']);
    $key = generateXORKey($key, $message['source_time']);
    $message['message'] = decryptMessage($message['message'], $key);
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
    $query = "INSERT INTO messages (sender, receiver, message, source_time) VALUES (?, ?, ?, ?)";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("sssi", $currentUser, $targetUser, $encryptedMessage, $sourceTime);
    $stmt->execute();

    header("Location: chat.php?user=" . urlencode($targetUser));
    exit;
}
?>

<div class="chat-interface">
    <h1>Чат з <?php echo htmlspecialchars($targetUser); ?></h1>
    <div class="messages">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender'] === $currentUser ? 'sent' : 'received'; ?>">
                <!-- Додавання імені відправника перед текстом повідомлення -->
                <strong><?php echo getSenderName($message['sender'], $accessControl, $adminLogin); ?>:</strong>
                <span><?php echo htmlspecialchars($message['message']) . '<br>'; ?></span>
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