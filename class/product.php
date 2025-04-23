<?php
class Product
{ /* Клас взаємодії з товарами */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($category, $name, $count, $price, $image, $characteristics)
    { /* Створити товар */
        $columns = ['category', 'product_name', 'count', 'price', 'uploadPath', 'characteristics'];
        if (!is_array($characteristics)) {
            $characteristics = [$characteristics];
        }
        if (is_array($image)) {
            $image = implode(",", $image);
        }
        $values = [$category, $name, $count, $price, $image, implode(",", $characteristics)];
        $types = 'ssiiss';
        $this->db->write('products', $columns, $values, $types);
    }

    public function getCategories()
    { /* Отримати категорії */
        return $this->db->read('categories', ['category_name', 'specifications']);
    }
}

$product = new Product($db);
