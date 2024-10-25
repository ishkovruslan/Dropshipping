<?php
session_start(); /* Початок сесії */
require_once('header.php'); /* Верхня частина сайту */
$accessControl->checkAccess(2); /* Доступ у адміністраторів */
require_once('../php/mysql.php'); /* Підключення до бази даних */
require_once('../php/crud.php'); /* Підключення до бази даних */

$product = new Product($db);
$categories = $product->getCategories();
?>

<h1>Створення нового товару</h1>
<form id="productForm" action="../php/crud.php" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="category">Категорія:</label>
        <select name="category" id="category"> <!-- Вибір категорії -->
            <option value="">Оберіть категорію</option>
            <?php
            if (count($categories) > 0) {
                foreach ($categories as $row) {
                    echo '<option value="' . htmlspecialchars($row["category_name"]) . '">' . htmlspecialchars($row["category_name"]) . '</option>';
                }
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label for="name">Назва товару:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="count">Кількість:</label>
        <input type="number" id="count" name="count" required>
    </div>
    <div class="form-group">
        <label for="price">Ціна:</label>
        <input type="number" id="price" name="price" min="0" step="1" required>
    </div>
    <div id="characteristics" class="form-group">
    </div>
    <div class="form-group">
        <label for="uploadPath">Зображення товару з співвідношенням 16:9:</label>
        <input type="file" id="uploadPath" name="uploadPath" accept="image/*" required>
    </div>
    <button type="submit" name="create_product">Створити товар</button>
</form>
<?php require_once('../php/footer.php'); ?>

<!-- Скрипт для категорій -->
<script>
    document.getElementById("category").addEventListener("change", function () {
        var selectedCategory = this.value;
        var characteristicsDiv = document.getElementById("characteristics");
        characteristicsDiv.innerHTML = "";

        var categories = <?php echo json_encode($categories); ?>;
        categories.forEach(function (category) {
            if (selectedCategory === category.category_name) {
                var specifications = category.specifications.split(",");
                specifications.forEach(function (spec, index) {
                    characteristicsDiv.innerHTML += '<div class="form-group"><label for="characteristic_' + index + '">' + spec + ':</label><input type="text" name="characteristics[' + index + ']" id="characteristic_' + index + '"></div>';
                });
            }
        });
    });
</script>