<?php
require_once('mysql.php'); // Підключення до бази

function displayNews($db) {
    $current_date = date('Y-m-d');
    $conditions = [
        'start_date <=' => $current_date,
        'end_date >=' => $current_date
    ];
    $result = $db->readWithSort('news', ['news_title', 'uploadPath'], $conditions);
    if (count($result) > 0) {
        foreach ($result as $row) {
            echo '<div class="news-img">';
            echo '<img src="../images/news/' . htmlspecialchars($row['uploadPath']) . '">';
            echo '<p>' . htmlspecialchars($row["news_title"]) . '</p>';
            echo '</div>';
        }
    } else {
        echo "Новини відсутні";
    }
}

displayNews($db);
?>
