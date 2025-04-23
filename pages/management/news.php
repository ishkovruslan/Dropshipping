<div class="news"> <!-- Таблиця взаємодії з новинами -->
    <h1>Список новин</h1>
    <table>
        <tr>
            <th width="20%">Зображення</th>
            <th width="20%">Назва</th>
            <th>Опис</th>
            <th width="12.5%">Початкова дата</th>
            <th width="12.5%">Кінцева дата</th>
        </tr>
        <?php $newsData = $db->readAll('news');
        foreach ($newsData as $news): ?>
            <tr>
                <td>
                    <img src="../images/news/<?php echo $news['uploadPath']; ?>">
                </td>
                <td><?php echo $news['news_title']; ?></td>
                <td><?php echo $news['news_description']; ?></td>
                <td><?php echo $news['start_date']; ?></td>
                <td><?php echo $news['end_date']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
