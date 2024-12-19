<?php
require_once('header.php'); /* Меню навігації */
$accessControl->checkAccess(1);
require_once('../php/order.php'); /* Необхідні функції */
?>

<div class="table-selection">
    <ul>
        <li><a href="?table=sorder">Окремі звіти</a></li>
        <li><a href="?table=dorder">Звіти за день</a></li>
        <?php if ($accessControl->getUserLevel($_SESSION['login']) == 2) {
            echo '<li><a href="?table=uorder">Звіти за користувачем</a></li>';
        } ?>
        <li><a href="?table=gorder">Загальний звіт</a></li>
    </ul>
</div>

<?php
/* Завантаження таблиці */
$table = $_GET['table'] ?? null;

switch ($table) {
    case 'sorder':
        require_once('report/sorder.php');
        break;
    case 'dorder':
        require_once('report/dorder.php');
        break;
    case 'uorder':
        require_once('report/uorder.php');
        break;
    case 'gorder':
        require_once('report/gorder.php');
        break;
}
require_once('../php/footer.php');
?>