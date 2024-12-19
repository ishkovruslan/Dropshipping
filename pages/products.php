<?php
require_once('header.php'); /* Навігаційне меню */
require_once('../php/mysql.php'); /* Підключення БД */
/* Ініціалізація змінних фільтрів */
$minPrice = !empty($_GET['minPrice']) ? $_GET['minPrice'] : null;
$maxPrice = !empty($_GET['maxPrice']) ? $_GET['maxPrice'] : null;
$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'asc';
/* Налаштування умов фільтрації та сортування */
$conditions = [];
if ($minPrice !== null) {
    $conditions['price >='] = $minPrice;
}
if ($maxPrice !== null) {
    $conditions['price <='] = $maxPrice;
}
$orderBy = ['price' => ($sort === 'asc' ? 'ASC' : 'DESC')];
$result = $db->readWithSort('products', ['*'], $conditions, $orderBy); /* Отримання даних продуктів з бази */
?>

<div class="filters">
    <form method="get">
        <input type="text" name="minPrice" placeholder="Мін. вартість"
            value="<?php echo htmlspecialchars($minPrice); ?>">
        <input type="text" name="maxPrice" placeholder="Макс. вартість"
            value="<?php echo htmlspecialchars($maxPrice); ?>">
        <select name="sort">
            <option value="asc" <?php echo $sort == 'asc' ? 'selected' : ''; ?>>Від меншої до більшої ціни</option>
            <option value="desc" <?php echo $sort == 'desc' ? 'selected' : ''; ?>>Від більшої до меншої ціни</option>
        </select>
        <button type="submit">Фільтрувати</button>
        <a href="products.php" onclick="resetFilters(event)">Скинути</a>
    </form>
</div>

<div>
    <table>
        <tr>
            <th width="20%">Зображення</th>
            <th width="15%">Категорія</th>
            <th>Назва товару</th>
            <th width="10%">Кількість</th>
            <th width="7.5%">Ціна</th>
            <th width="25%">Характеристики</th>
        </tr>
        <?php if (count($result) > 0) {
            foreach ($result as $row) {
                $count = htmlspecialchars($row["count"]);
                if ($count != 0) { /* Формування HTML сторінки */
                    $id = htmlspecialchars($row["id"]);
                    $category = htmlspecialchars($row["category"]);
                    $itemName = htmlspecialchars($row["product_name"]);
                    $price = htmlspecialchars($row["price"]);
                    $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
                    /* Отримання характеристик товару */
                    $characteristics = explode(',', $row['characteristics']);
                    $specificationsResult = $db->read('categories', ['specifications'], ['category_name' => $row['category']]);
                    $specifications = explode(',', $specificationsResult[0]["specifications"]);
                    $characteristicsHTML = '';
                    foreach ($characteristics as $key => $value) { /* Формування HTML для характеристик */
                        if ($value !== "-" && $value !== "") {
                            $characteristicsHTML .= htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value) . "<br>";
                        }
                    }
                    /* Відображення продукту у рядку таблиці */
                    echo "<tr>
                            <td>
                                <img src='$imagePath' alt='$itemName' onclick=\"openEditProductModal('$id', '$imagePath', '$category', '$itemName', '$count', '$price', '" . addslashes($row['characteristics']) . "')\">
                            </td>
                            <td>$category</td>
                            <td><a href='product.php?id=$id'>$itemName</a></td>
                            <td>$count</td>
                            <td>$price</td>
                            <td>$characteristicsHTML</td>
                        </tr>";
                }
            }
        } else {
            echo "<tr><td colspan='6'>Товари відсутні</td></tr>";
        } ?>
    </table>
</div>

<?php require_once('../php/footer.php'); ?>