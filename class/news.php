<?php
class News
{ /* Клас взаємодії з новинами */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($title, $image, $description, $startDate, $endDate)
    { /* Створити новину */
        $columns = ['news_title', 'uploadPath', 'news_description', 'start_date', 'end_date'];
        $values = [$title, $image, $description, $startDate, $endDate];
        $types = 'sssss';
        $this->db->write('news', $columns, $values, $types);
    }
}

$news = new News($db);
