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
                    <img src="../images/news/<?php echo $news['uploadPath']; ?>"
                        onclick="openEditModal('<?php echo $news['id']; ?>', '<?php echo $news['uploadPath']; ?>', '<?php echo $news['news_title']; ?>', '<?php echo $news['news_description']; ?>', '<?php echo $news['start_date']; ?>', '<?php echo $news['end_date']; ?>')">
                </td>
                <td><?php echo $news['news_title']; ?></td>
                <td><?php echo $news['news_description']; ?></td>
                <td><?php echo $news['start_date']; ?></td>
                <td><?php echo $news['end_date']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <form id="editForm" method="post" action="../functions/crud.php" enctype="multipart/form-data">
                <input type="hidden" name="entity" value="news">
                <input type="hidden" name="id" id="news_id">
                <label for="uploadPath">Зображення:</label>
                <input type="file" name="uploadPath" id="uploadPath" placeholder="Зображення">
                <label for="news_title">Заголовок:</label>
                <input type="text" name="news_title" id="news_title" placeholder="Заголовок">
                <label for="news_description">Опис:</label>
                <textarea name="news_description" id="news_description" placeholder="Опис"></textarea>
                <label for="start_date">Початок:</label>
                <input type="date" name="start_date" id="start_date" placeholder="Початок">
                <label for="end_date">Кінець:</label>
                <input type="date" name="end_date" id="end_date" placeholder="Кінець">
                <button type="submit">Зберегти</button>
            </form>
            <form id="deleteNewsForm" method="post" action="../functions/crud.php"
                onsubmit="return confirm('Ви впевнені, що хочете видалити цю новину?');" style="margin-top: 10px;">
                <input type="hidden" name="entity" value="news">
                <input type="hidden" name="id" id="delete_news_id">
                <input type="hidden" name="delete" value="1">
                <button type="submit" style="background-color: red; color: white;">Видалити</button>
            </form>
        </div>
    </div>
</div>