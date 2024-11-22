<div class="category"><!-- Таблиця взаємодії з категоріями -->
    <h1>Список категорій</h1>
    <table>
        <tr>
            <th>Назва</th>
            <th>Кількість пропозицій</th>
            <th>Специфікації</th>
        </tr>
        <?php
        $categoriesData = $db->readAll('categories');
        $productsData = $db->readAll('products');
        foreach ($categoriesData as $category): ?>
            <tr>
                <td>
                    <a href="management.php?category=<?php echo $category['category_name']; ?>">
                        <?php echo $category['category_name']; ?>
                    </a>
                </td>
                <td><!-- По натисканню на категорію відкривається таблиця продуктів цієї категорії -->
                    <?php echo countAccessibleProductsByCategory($category['category_name'], $productsData); ?>
                </td>
                <td>
                    <?php
                    $specifications = explode(',', $category['specifications']);
                    foreach ($specifications as $spec):
                        if ($spec != ""): ?>
                            <br><?php echo htmlspecialchars($spec); ?></b>
                        <?php endif;
                    endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>