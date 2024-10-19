<?php
/* Модуль виведення */
class Categories
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function generateCategoryHTML($row)
    {/* Створення сторінки категорій */
        $name = htmlspecialchars($row["category_name"]);
        $imagePath = '../images/categories/' . htmlspecialchars($row["uploadPath"]);
        $description = htmlspecialchars($row["category_description"]);
        return "<a href='products.php?category=" . urlencode($name) . "'>
                    <div class='category-box'>
                        <img src='$imagePath' alt='$name' class='category-image'>
                        <h3>$name</h3>
                        <p>$description</p>
                    </div>
                </a>";
    }

    public function displayCategories()
    {/* Виведення категорій */
        $columns = ['category_name', 'uploadPath', 'category_description'];
        $result = $this->db->read('categories', $columns);
        if (count($result) > 0) {
            foreach ($result as $row) {
                echo $this->generateCategoryHTML($row);
            }
        } else {
            echo "Категорії відсутні";
        }
    }
}

$categories = new Categories($db);

class Products
{
    private $db;
    private $accessControl;

    public function __construct($db, $accessControl)
    {
        $this->db = $db;
        $this->accessControl = $accessControl;
    }

    private function generateProductHTML($row)
    {/* Створення сторінки товарів */
        $id = htmlspecialchars($row["id"]);
        $owner = htmlspecialchars($row["login"]);
        $itemName = htmlspecialchars($row["product_name"]);
        $price = htmlspecialchars($row["price"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        if ($this->accessControl->getUserLevel($owner) == 2) {
            $owner = "Магазин";
        }
        return "<a href='product.php?id=$id'>
                    <div class='product-box'>
                        <img src='$imagePath' alt='$itemName' class='product-image'>
                        <h3>$itemName</h3>
                        <p>Власник: $owner</p>
                        <p>Ціна: $price</p>
                    </div>
                </a>";
    }

    public function displayProducts($category, $owner, $minPrice, $maxPrice, $sort)
    {/* Виведення товарів */
        $defaultCategory = 'defaultCategory';
        $defaultSort = 'asc';
        $conditions = [];
        /* Значення за замовчуванням */
        $category = $category ?? $defaultCategory;
        $sort = $sort ?? $defaultSort;
        if ($category !== null) {
            $conditions['category'] = $category;
        }
        if ($owner !== null) {
            $conditions['login'] = $owner;
        }
        if ($minPrice !== null) {
            $conditions['price >='] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions['price <='] = $maxPrice;
        }
        $orderBy = $sort !== null ? ['price' => ($sort == 'asc' ? 'ASC' : 'DESC')] : [];
        $result = $this->db->readWithSort('products', ['*'], $conditions, $orderBy);
        if (count($result) > 0) {
            foreach ($result as $row) {
                echo $this->generateProductHTML($row);
            }
        } else {
            echo "Товари відсутні";
        }
    }
}

$category = $_GET['category'] ?? 'defaultCategory';
$roleFilter = $_GET['roleFilter'] ?? 'anyone';
$minPrice = !empty($_GET['minPrice']) ? $_GET['minPrice'] : null;
$maxPrice = !empty($_GET['maxPrice']) ? $_GET['maxPrice'] : null;
$sort = !empty($_GET['sort']) ? $_GET['sort'] : null;
$owner = !empty($_GET['owner']) ? $_GET['owner'] : null;

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
        $owner = htmlspecialchars($row["login"]);
        $itemName = htmlspecialchars($row["product_name"]);
        $price = htmlspecialchars($row["price"]);
        $imagePath = '../images/products/' . htmlspecialchars($row["uploadPath"]);
        $characteristics = explode(',', $row["characteristics"]);
        if ($this->accessControl->getUserLevel($owner) == 2) {
            $owner = "Магазин";
        }
        $html = "<div class='main-block'>
                    <div class='product-container'>
                        <img src='$imagePath' alt='$itemName' class='product-image'>
                        <div class='product-details'>
                            <h2>$itemName</h2>
                            <p>Продавець: $owner</p>
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