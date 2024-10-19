<?php
session_start();
require_once('header.php'); // Верхня частина сайту
require_once('../php/mysql.php'); // Підключення до бази даних
require_once('../php/output.php'); // Підключення до бази даних
?>

<div class="filters">
    <form method="get"><!-- Сортування товарів з певної категорії -->
        <input type="text" name="owner" placeholder="Пошук по продавцю"
            value="<?php echo isset($_GET['owner']) ? htmlspecialchars($_GET['owner']) : ''; ?>">
        <input type="hidden" name="category"
            value="<?php echo isset($_GET['category']) ? htmlspecialchars($_GET['category']) : ''; ?>">
        <input type="text" name="minPrice" placeholder="Мін. вартість"
            value="<?php echo isset($_GET['minPrice']) ? htmlspecialchars($_GET['minPrice']) : ''; ?>">
        <input type="text" name="maxPrice" placeholder="Макс. вартість"
            value="<?php echo isset($_GET['maxPrice']) ? htmlspecialchars($_GET['maxPrice']) : ''; ?>">
        <select name="sort"> <!-- Сортування за ціною -->
            <option value="asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : ''; ?>>Від
                меншої до більшої ціни</option>
            <option value="desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : ''; ?>>Від
                більшої до меншої ціни</option>
        </select>
        <button type="submit">Фільтрувати</button>
        <a href="products.php" onclick="resetFilters(event)">Скинути</a>
    </form>
</div>
<div class="output">
    <?php $products->displayProducts($category, $owner, $minPrice, $maxPrice, $sort); ?>
</div>

<?php require_once('../php/footer.php'); ?>

<script>
    function resetFilters(event) {
        event.preventDefault();
        var category = document.querySelector('input[name="category"]').value;
        document.querySelector('input[name="owner"]').value = '';
        document.querySelector('input[name="minPrice"]').value = '';
        document.querySelector('input[name="maxPrice"]').value = '';
        document.querySelector('select[name="sort"]').value = 'asc';
        window.location.href = 'products.php?category=' + encodeURIComponent(category);
    }
</script>