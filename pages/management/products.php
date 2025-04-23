<div class="products"> <!-- Таблиця товарів обраної категорії -->
    <h1>Список товарів
        <?php echo $selectedCategory ? 'категорії "' . htmlspecialchars($selectedCategory) . '"' : ''; ?>
    </h1>
    <table>
        <tr>
            <th width="20%">Зображення</th>
            <th width="18%">Категорія</th>
            <th>Назва товару</th>
            <th>Кількість товару</th>
            <th width="10%">Ціна</th>
            <th width="25%">Характеристики</th>
        </tr>
        <?php $categoriesData = $db->readAll('categories');
        $productsData = $db->readAll('products');
        $filteredProducts = $selectedCategory ? array_filter($productsData, fn($product) => $product['category'] === $selectedCategory) : $productsData;
        foreach ($filteredProducts as $product): ?>
            <tr>
                <td><img src="../images/products/<?php echo $product['uploadPath']; ?>" alt="Товар"
                        onclick="openEditProductModal('<?php echo $product['id']; ?>', '<?php echo $product['uploadPath']; ?>', '<?php echo addslashes($product['category']); ?>', '<?php echo addslashes($product['product_name']); ?>', '<?php echo $product['count']; ?>', '<?php echo $product['price']; ?>', '<?php echo addslashes($product['characteristics']); ?>')">
                </td>
                <td>
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <?php echo $product['category']; ?>
                    </a>
                </td>
                <td><?php echo $product['product_name']; ?></td>
                <td><?php echo $product['count']; ?></td>
                <td><?php echo $product['price']; ?></td>
                <td>
                    <?php $characteristics = explode(',', $product['characteristics']);
                    $specificationsResult = $db->read('categories', ['specifications'], ['category_name' => $product['category']]);
                    $specifications = explode(',', $specificationsResult[0]["specifications"]);
                    foreach ($characteristics as $key => $value):
                        if ($value !== "-" && $value !== ""): ?>
                            <?php echo htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value); ?><br>
                        <?php endif;
                    endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditProductModal()">&times;</span>
            <form id="editProductForm" method="post" action="../functions/crud.php" enctype="multipart/form-data">
                <input type="hidden" name="entity" value="products">
                <input type="hidden" name="id" id="product_id">
                <label for="product_image">Зображення:</label>
                <input type="file" name="uploadPath" id="uploadPath" placeholder="Зображення">
                <label for="category">Категорія:</label>
                <input type="text" name="category" id="category" placeholder="Категорія">
                <label for="product_name">Назва товару:</label>
                <input type="text" name="product_name" id="product_name" placeholder="Назва товару">
                <label for="count">Кількість:</label>
                <input type="text" name="count" id="count" placeholder="Кількість">
                <label for="price">Ціна:</label>
                <input type="text" name="price" id="price" placeholder="Ціна">
                <label for="characteristics">Характеристики:</label>
                <input type="text" name="characteristics" id="characteristics" placeholder="Характеристики">
                <button type="submit">Зберегти</button>
            </form>
            <form id="deleteProductForm" method="post" action="../functions/crud.php"
                onsubmit="return confirm('Ви впевнені, що хочете видалити цей товар?');" style="margin-top: 10px;">
                <input type="hidden" name="entity" value="products">
                <input type="hidden" name="id" id="delete_product_id">
                <input type="hidden" name="delete" value="1">
                <button type="submit" style="background-color: red; color: white;">Видалити</button>
            </form>
        </div>
    </div>
</div>
