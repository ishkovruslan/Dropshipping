<?php
/* Модуль виведення */
class Products
{
    private $db;
    private $accessControl;

    public function __construct($db, $accessControl)
    {
        $this->db = $db;
        $this->accessControl = $accessControl;
    }

    private function generateProductHTML($row) {
        // Отримання даних товару з рядка
        $id = htmlspecialchars($row["id"]);
        $category = htmlspecialchars($row["category"]);
        $itemName = htmlspecialchars($row["product_name"]);
        $count = htmlspecialchars($row["count"]);
        $price = htmlspecialchars($row["price"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        
        // Розбиття характеристик
        $characteristics = explode(',', $row['characteristics']);
        $specificationsResult = $this->db->read('categories', ['specifications'], ['category_name' => $row['category']]);
        $specifications = explode(',', $specificationsResult[0]["specifications"]);
    
        // Формування HTML з характеристиками
        $characteristicsHTML = '';
        foreach ($characteristics as $key => $value) {
            if ($value !== "-" && $value !== "") {
                $characteristicsHTML .= htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value) . "<br>";
            }
        }
    
        // Повернення HTML рядка таблиці
        return "<tr>
                    <!-- Редагування по натисканню на зображення -->
                    <td>
                        <img src='$imagePath' alt='$itemName' onclick=\"openEditProductModal('$id', '$imagePath', '$category', '$itemName', '$count', '$price', '" . addslashes($row['characteristics']) . "')\">
                    </td>
                    <td>$category</td>
                    <td>
                        <a href='product.php?id=$id'>$itemName</a>
                    </td>
                    <td>$count</td>
                    <td>$price</td>
                    <td>$characteristicsHTML</td>
                </tr>";
    }        

    public function displayProducts($minPrice, $maxPrice, $sort)
    {/* Виведення товарів */
        $defaultSort = 'asc';
        $conditions = [];
        /* Значення за замовчуванням */
        $sort = $sort ?? $defaultSort;
        if ($minPrice !== null) {
            $conditions['price >='] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions['price <='] = $maxPrice;
        }
        $orderBy = $sort !== null ? ['price' => ($sort == 'asc' ? 'ASC' : 'DESC')] : [];
        $result = $this->db->readWithSort('products', ['*'], $conditions, $orderBy);
        ?>
        <tr><table>
            <th width="20%">Зображення</th>
            <th width="15%">Категорія</th>
            <th>Назва товару</th>
            <th width="10%">Кількість</th>
            <th width="7.5%">Ціна</th>
            <th width="25%">Характеристики</th>
        </tr>
        <?php
        if (count($result) > 0) {
            foreach ($result as $row) {
                echo $this->generateProductHTML($row);
            }
            ?></table><?php
        } else {
            echo "Товари відсутні";
        }
    }
}

$roleFilter = $_GET['roleFilter'] ?? 'anyone';
$minPrice = !empty($_GET['minPrice']) ? $_GET['minPrice'] : null;
$maxPrice = !empty($_GET['maxPrice']) ? $_GET['maxPrice'] : null;
$sort = !empty($_GET['sort']) ? $_GET['sort'] : null;

$accessControl = new AccessControl($db, $roles);
$products = new Products($db, $accessControl);

class Product
{
    private $db;
    private $accessControl;

    public function __construct($db, $accessControl)
    {
        $this->db = $db;
        $this->accessControl = $accessControl;
    }

    private function generateProductDetailHTML($row, $specifications)
    {/* Створення сторінки товару */
        $itemName = htmlspecialchars($row["product_name"]);
        $price = htmlspecialchars($row["price"]);
        $count = htmlspecialchars($row["count"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        $characteristics = explode(',', $row["characteristics"]);
        $html = "<div class='main-block'>
                    <div class='product-container'>
                        <img src='$imagePath' alt='$itemName' class='product-image'>
                        <div class='product-details'>
                            <h2>$itemName</h2>
                            <p>Кількість: $count</p>
                            <p>Ціна: $price</p>
                            <h3>Характеристики:</h3>
                            <ul>";
        foreach ($characteristics as $key => $value) {
            if ($value !== "-" && $value !== "") {
                $html .= "<li>" . htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value) . "</li>";
            }
        }
        $html .= "          </ul>";
        $html .= "      </div>
                    </div>
                </div>";

        return $html;
    }

    public function displayProduct($id)
    {/* Вивід товарів */
        $result = $this->db->read('products', ['*'], ['id' => $id]);

        if (count($result) > 0) {
            $row = $result[0];
            $category = $row["category"];
            $specificationsResult = $this->db->read('categories', ['specifications'], ['category_name' => $category]);
            $specifications = explode(',', $specificationsResult[0]["specifications"]);

            echo $this->generateProductDetailHTML($row, $specifications);
        } else {
            echo "Товар не знайдено";
        }
    }
}

$id = $_GET['id'] ?? null;

$product = new Product($db, $accessControl);
?>