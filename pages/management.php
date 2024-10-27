<?php
session_start();
require_once('header.php'); /* Верхня частина сайту */
$accessControl->checkAccess(2); /* Доступ лише у адміністраторів*/
require_once('../php/crud.php');  /* Необхідні функції */

$userList = new UserList($db);
$userList->loadUsersFromDB();

/* Повні таблиці новин/категорій/товарів */
$newsData = $db->readAll('news');
$categoriesData = $db->readAll('categories');
$productsData = $db->readAll('products');
/* Обробник для виводу продуктів */
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$filteredProducts = $selectedCategory ? array_filter($productsData, function ($product) use ($selectedCategory) {
    return $product['category'] == $selectedCategory;
}) : $productsData;
$roles = array("user" => "Користувач", "seller" => "Продавець"); ?>

<div class="userlist"><!-- Таблиця керування користувачами -->
    <h1>Список користувачів</h1>
    <table>
        <tr>
            <th>Логін</th>
            <th width="20%">Роль</th>
            <th width="40%">Керування користувачем</th>
        </tr>
        <?php foreach ($userList->getUsers() as $user): /* Перебираємо всіх користувачів */
            if ($user->getRole() !== 'administrator'): ?> <!-- Пропускаємо адміністраторів -->
                <tr>
                    <td><?php echo $user->getLogin(); ?></td>
                    <td><?php echo $roles[$user->getRole()]; ?></td>
                    <td><!-- Меню керування користувачем -->
                        <form method="post">
                            <input type="hidden" name="login" value="<?php echo $user->getLogin(); ?>">
                            <select name="new_role" onchange="updateButtonText(this)">
                                <option value="">Редагування</option>
                                <?php foreach ($roles as $rkey => $rtit) {
                                    if ($rkey != $user->getRole()) {
                                        echo '<option value="' . $rkey . '">' . $rtit . '</option>';
                                    }
                                }
                                ?>
                                <option value="changekey">Зміна ключа</option>
                                <option value="delete">Видалення</option>
                            </select>
                            <button type="submit" name="submit_action"
                                id="submit-button-<?php echo $user->getLogin(); ?>">Змінити</button>
                        </form>
                    </td>
                </tr>
            <?php endif;
        endforeach; ?>
    </table>
</div>
<div class="news"><!-- Таблиця взаємодії з новинами -->
    <h1>Список новин</h1>
    <table>
        <tr>
            <th width="20%">Зображення</th>
            <th width="20%">Назва</th>
            <th>Опис</th>
            <th width="12.5%">Початкова дата</th>
            <th width="12.5%">Кінцева дата</th>
        </tr>
        <?php foreach ($newsData as $news): ?>
            <tr>
                <td><!-- Редагування по натисканню на зображення -->
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
        <div class="modal-content"><!-- Модальний контент керування записом -->
            <span class="close" onclick="closeEditModal()">&times;</span>
            <form id="editForm" method="post" action="../php/crud.php" enctype="multipart/form-data">
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
            </form><!-- Модальний контент видалення запису -->
            <form id="deleteNewsForm" method="post" action="../php/crud.php"
                onsubmit="return confirm('Ви впевнені, що хочете видалити цю новину?');" style="margin-top: 10px;">
                <input type="hidden" name="entity" value="news">
                <input type="hidden" name="id" id="delete_news_id">
                <input type="hidden" name="delete" value="1">
                <button type="submit" style="background-color: red; color: white;">Видалити</button>
            </form>
        </div>
    </div>
</div>
<div class="category"><!-- Таблиця взаємодії з категоріями -->
    <h1>Список категорій</h1>
    <table>
        <tr>
            <th>Назва</th>
            <th>Кількість пропозицій</th>
            <th>Специфікації</th>
        </tr>
        <?php foreach ($categoriesData as $category): ?>
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
<?php if ($selectedCategory != "") { ?><!-- Якщо категорія обрана -> вивести цей товар -->
    <div class="products"><!-- Таблицяя взаємодії з товарами певної категорії -->
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
            <?php foreach ($filteredProducts as $product): ?>
                <tr><!-- Редагування по натисканню на зображення -->
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
                        <?php
                        $characteristics = explode(',', $product['characteristics']);
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
            <div class="modal-content"><!-- Модальний контент керування записом -->
                <span class="close" onclick="closeEditProductModal()">&times;</span>
                <form id="editProductForm" method="post" action="../php/crud.php" enctype="multipart/form-data">
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
                </form><!-- Модальний контент видалення запису -->
                <form id="deleteProductForm" method="post" action="../php/crud.php"
                    onsubmit="return confirm('Ви впевнені, що хочете видалити цей товар?');" style="margin-top: 10px;">
                    <input type="hidden" name="entity" value="products">
                    <input type="hidden" name="id" id="delete_product_id">
                    <input type="hidden" name="delete" value="1">
                    <button type="submit" style="background-color: red; color: white;">Видалити</button>
                </form>
            </div>
        </div>
    </div>
<?php }

require_once('../php/footer.php'); ?>