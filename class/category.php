<?php
class Category
{ /* Клас взаємодії з категоріями */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($name, $specifications)
    {/* Створити категорію */
        $columns = ['category_name', 'specifications'];
        $values = [$name, implode(",", $specifications)];
        $types = 'ss';
        $this->db->write('categories', $columns, $values, $types);
    }
}

$category = new Category($db);
