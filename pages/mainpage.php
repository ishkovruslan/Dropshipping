<?php
require_once('header.php'); /* Навігаційне меню */
$columns = ['uploadPath', 'news_title', 'news_description', 'start_date', 'end_date'];
$newsData = array_unique($db->searchLike('news', $columns, 'news_title', ''), SORT_REGULAR);
?>

<div><!-- Текстове наповнення для скріншотів -->
    <p>
        Бакалаврська робота студента 545-а групи Ішкова Руслана Вікторовича.
    </p>
    <p>
        Робота присвячена розробці системи управління дропшипінгом з наданням API для дрібних магазинів.
    </p>
    <p>
        Метою роботи є розробка сайту з використанням HTML, CSS, JavaScript. Функціонал якого імітує роботу дропшипінгу
        з можливостям керування обліковими засобами, категоріями, товарами та наданням можливоті взаємодії через API.
    </p>
    <p>
        Джерелом даних є адміністратор який має мету розмістити товар на сайті, а також продавці які розміщують товар на
        своїх майданчиках та передають сформовані замовлення.
    </p>
    <p>
        Результатом роботи сайт який надає зручні можливості дропшопінга для продавців.
    </p>
</div>

<?php /* Діагностика: перевірка, чи є новини */
if (empty($newsData)) {
    echo "<p>Немає новин.</p>";
} else {
    $filteredNewsData = [];

    if (empty($newsData)) {
        echo "<p>Немає актуальних новин на сьогодні.</p>";
    } else {
        echo '<table>';
        foreach ($newsData as $news) {
            echo '<tr>';
            echo '<td width="20%"><img src="../images/news/' . htmlspecialchars($news['uploadPath']) . '"></td>';
            echo '<td width="20%">' . htmlspecialchars($news['news_title']) . '</td>';
            echo '<td>' . htmlspecialchars($news['news_description']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

require_once('../php/footer.php');
?>