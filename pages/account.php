<?php
session_start(); /* Початок сессії */
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

<?php require_once('../php/footer.php'); ?>