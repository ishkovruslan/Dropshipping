<?php /* Сторінка керування обліковим записом */
require_once('header.php'); /* Навігаційне меню */
require_once('../handler/account.php'); /* Навігаційне меню */
$accessControl->checkAccess(1); /* Перевірка рівня */

if (!$accessControl->isMostFrequentIp($_SESSION['login'], $_SERVER['REMOTE_ADDR'])) { /* Доступ має лише основний ip */
    echo '<p style="color: red;">Сторінка доступна лише з вашої найчастішої IP-адреси.</p>';
    exit;
}

require_once('../class/remoteAccess.php'); /* Підключення до класу аутентифікації */
$key = $remoteAccess->manageRemoteAccess("WEB", $_SESSION['login']);
?>

<h1>Керування обліковим записом</h1>
<div>
    <p>Керування віддаленим доступом</p>
    <p>Унікальний ключ: <?php echo $key; ?></p>
    <form method="post">
        <button type="submit" name="generate_key">Згенерувати новий ключ</button>
    </form>
</div>

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
require_once('footer.php');
