<div class="category"> <!-- Таблиця взаємодії з категоріями -->
    <h1>Список категорій</h1>
    <table>
        <tr>
            <th>Назва</th>
            <th>Кількість пропозицій</th>
            <th>Специфікації</th>
        </tr>
        <?php $categoriesData = $db->readAll('categories');
        $productsData = $db->readAll('products');
        foreach ($categoriesData as $category):
            $count = countAccessibleProductsByCategory($category['category_name'], $productsData);
            ?>
            <tr>
                <td>
                    <a href="management.php?category=<?php echo $category['category_name']; ?>">
                        <?php echo $category['category_name']; ?>
                    </a>
                </td>
                <td>
                    <?php if ($accessControl->getUserLevel($_SESSION['login']) == 2 && $count == 0) { ?><!-- Редагування по натисканню на зображення -->
                        <a href="#" class="clickable-count"
                            onclick="openEditCategoryModal('<?php echo $category['id']; ?>', '<?php echo addslashes($category['category_name']); ?>', '<?php echo addslashes($category['specifications']); ?>'); return false;">
                            <?php echo $count; ?>
                        </a>
                    <?php } elseif ($accessControl->getUserLevel($_SESSION['login']) == 1 || $count > 0) { ?><!-- Звичайне зображення -->
                        <?php echo $count; ?>
                    <?php } ?>
                </td>
                <td>
                    <?php $specifications = explode(',', $category['specifications']);
                    foreach ($specifications as $spec):
                        if ($spec != ""): ?>
                            <br><?php echo htmlspecialchars($spec); ?></b>
                        <?php endif;
                    endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div id="editCategoryModal" class="modal">
        <div class="modal-content"><!-- Модальний контент керування записом -->
            <span class="close" onclick="closeEditCategoryModal()">&times;</span>
            <form id="editCategoryForm" method="post" action="../functions/crud.php" enctype="multipart/form-data">
                <input type="hidden" name="entity" value="categories">
                <input type="hidden" name="id" id="category_id">
                <label for="category_name">Назва категорії:</label>
                <input type="text" name="category_name" id="category_name" placeholder="Назва категорії">
                <label for="category_specifications">Специфікації:</label>
                <textarea type="text" name="category_specifications" id="category_specifications"
                    placeholder="Специфікації"></textarea>
                <button type="submit">Зберегти</button>
            </form><!-- Модальний контент видалення запису -->
            <form id="deleteCategoryForm" method="post" action="../functions/crud.php"
                onsubmit="return confirm('Ви впевнені, що хочете видалити цю категорію?');" style="margin-top: 10px;">
                <input type="hidden" name="entity" value="categories">
                <input type="hidden" name="id" id="delete_category_id">
                <input type="hidden" name="delete" value="1">
                <button type="submit" style="background-color: red; color: white;">Видалити</button>
            </form>
        </div>
    </div>
</div>
