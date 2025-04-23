<?php /* Сторінка створення товарів */
require_once('header.php'); /* Навігаційне меню */
$accessControl->checkAccess(2); /* Доступ лише у адміністраторів */
require_once('../functions/mysql.php'); /* Підключення до БД */
require_once('../class/category.php'); /* Модуль функцій */
require_once('../class/product.php'); /* Модуль функцій */

$categories = $product->getCategories();
?>

<h1>Створення нового товару</h1>
<form id="productForm" action="../functions/crud.php" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="category">Категорія:</label>
        <select name="category" id="category" required>
            <option value="">Оберіть категорію</option>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $row): ?>
                    <option value="<?= htmlspecialchars($row['category_name']) ?>">
                        <?= htmlspecialchars($row['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="name">Назва товару:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="count">Кількість:</label>
        <input type="number" id="count" name="count" min="1" required>
    </div>
    <div class="form-group">
        <label for="price">Ціна:</label>
        <input type="number" id="price" name="price" min="0" step="0.01" required>
    </div>
    <div id="characteristics" class="form-group"></div>
    <div class="form-group">
        <label for="uploadPath">Зображення товару (16:9):</label>
        <input type="file" id="uploadPath" name="uploadPath" accept="image/*" required>
    </div>
    <button type="submit" name="create_product">Створити товар</button>
</form>

<script>
    const categories = <?= json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<?php require_once('footer.php');
