<?php
session_start();
require_once('header.php'); // Верхня частина сайту
require_once('../php/mysql.php'); // Підключення до бази даних
require_once('../php/output.php'); // Генерація сторінки
?>

<div class="filters">
    <form method="get"><!-- Сортування товарів з певної категорії -->
        <input type="text" name="minPrice" placeholder="Мін. вартість"
            value="<?php echo isset($_GET['minPrice']) ? htmlspecialchars($_GET['minPrice']) : ''; ?>">
        <input type="text" name="maxPrice" placeholder="Макс. вартість"
            value="<?php echo isset($_GET['maxPrice']) ? htmlspecialchars($_GET['maxPrice']) : ''; ?>">
        <select name="sort"> <!-- Сортування за ціною -->
            <option value="asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : ''; ?>>
                Від меншої до більшої ціни</option>
            <option value="desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : ''; ?>>
                Від більшої до меншої ціни</option>
        </select>
        <button type="submit">Фільтрувати</button>
        <a href="products.php" onclick="resetFilters(event)">Скинути</a>
    </form>
</div>
<div>
    <?php $products->displayProducts($minPrice, $maxPrice, $sort); ?>
</div>

<?php require_once('../php/footer.php'); ?>