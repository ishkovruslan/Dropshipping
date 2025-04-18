<?php /* Сторінка повідомлень */
require_once('header.php'); /* Навігаційне меню */
$accessControl->checkAccess(2); /* Доступ лише у адміністраторів */
$messages = $db->getMessagesForAdmin(); /* Повідомлення від адміністраторів */
?>

<div class="messages-list">
    <h1>Обмін повідомленнями</h1>
    <table>
        <thead>
            <tr>
                <th>Логін</th>
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
                        <?php echo date("Y-m-d H:i:s", substr($message['last_time'], 0, -3)); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once('footer.php');
