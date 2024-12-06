<?php
require_once('header.php'); /* Верхня частина сайту */
$accessControl->checkAccess(1); /* Доступ лише у адміністраторів */
$key = $remoteAccess->manageRemoteAccess($_SESSION['login']);
?>

<h1>Керування обліковим записом</h1>
<div>
    <p>Керування віддаленим доступом</p>
    <p>Унікальний ключ: <?php echo $key; ?></p>
    <form method="post">
        <!-- Кнопка для генерації ключа -->
        <button type="submit" name="generate_key">Згенерувати новий ключ</button>
    </form>
</div>

<?php
if (!$accessControl->isMostFrequentIp($_SESSION['login'], $_SERVER['REMOTE_ADDR'])) {
    echo '<p style="color: red;">Зміна паролю доступна лише з вашої найчастішої IP-адреси.</p>';
    exit;
}
?>

<h1>Зміна паролю</h1>
<form method="post">
    <label for="current_password">Старий пароль:</label>
    <input type="password" id="current_password" name="current_password" required>
    
    <label for="new_password">Новий пароль:</label>
    <input type="password" id="new_password" name="new_password" required>
    
    <label for="confirm_password">Підтвердіть новий пароль:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>
    
    <button type="submit" name="change_password">Змінити пароль</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Виклик методу для зміни паролю
    $result = $authentication->changePassword($_SESSION['login'], $currentPassword, $newPassword, $confirmPassword);
    if ($result['success']) {
        echo '<p style="color: green;">Пароль успішно змінено!</p>';
    } else {
        echo '<p style="color: red;">' . $result['message'] . '</p>';
    }
}
?>

<?php require_once('../php/footer.php'); ?>