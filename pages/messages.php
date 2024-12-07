<?php
require_once('header.php'); 
$accessControl->checkAccess(2); // Доступ лише адміністраторам

// Отримання повідомлень для адміністратора
$adminLogin = $_SESSION['login'];
$query = "
    SELECT 
        m.sender AS login,
        m.message,
        MAX(m.source_time) AS last_time
    FROM messages m
    WHERE m.receiver = ? OR m.sender = ?
    GROUP BY m.sender
    ORDER BY last_time DESC
";
$stmt = $db->conn->prepare($query);
$stmt->bind_param("ss", $adminLogin, $adminLogin);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

?>
<div class="messages-list">
    <h1>Обмін повідомленнями</h1>
    <table>
        <thead>
            <tr>
                <th>Логін</th>
                <th>Останнє повідомлення</th>
                <th>Час</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td>
                        <a href="chat.php?user=<?php echo urlencode($message['login']); ?>">
                            <?php echo htmlspecialchars($message['login']); ?>
                        </a>
                    </td>
                    <td>
                        <a href="chat.php?user=<?php echo urlencode($message['login']); ?>">
                            <?php echo htmlspecialchars(substr($message['message'], 0, 50)); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo date("Y-m-d H:i:s", substr($message['last_time'], 0, -3)); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once('../php/footer.php'); ?>
