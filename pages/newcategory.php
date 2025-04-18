<?php /* Сторінка створення категорій */
require_once('header.php'); /* Навігаційне меню */
$accessControl->checkAccess(2); /* Доступ лише у адміністраторів */
?>

<h1>Створити нову категорію</h1>
<form action="../functions/crud.php" method="post" enctype="multipart/form-data">
    <label for="category_name">Назва категорії:</label><br>
    <input type="text" id="category_name" name="category_name"><br><br>
    <label for="specifications">Специфікації (розділені комою):</label><br>
    <textarea id="specifications" name="specifications" rows="4" cols="50"></textarea><br><br>
    <button type="submit" name="create_category">Створити категорію</button>
</form>
<?php require_once('footer.php');
