<?php /* Обробка API запитів інформації */
function operation($operation, $decryptedQuery)
{
    global $db;
    switch ($operation) {
        case 'readAllProducts': /* Усі товари */
            $products = $db->readAll('products');
            $categories = $db->readAll('categories');
            $categoryMap = [];
            foreach ($categories as $category) {
                $categoryMap[$category['category_name']] = $category;
            }

            /* Фільтруємо товари з нульовою кількістю */
            $filtered = [];
            foreach ($products as $product) {
                if ((int) $product['count'] === 0) {
                    continue;
                }
                if (isset($categoryMap[$product['category']])) {
                    $product = decodeCharacteristics($product, $categoryMap[$product['category']]);
                }
                $filtered[] = $product;
            }
            return $filtered;

        case 'readProduct': /* Товар за ідентифікатором */
            if (!isset($decryptedQuery['conditions'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Conditions required for readProduct']);
                exit;
            }
            $products = $db->readWithSort('products', ['*'], $decryptedQuery['conditions']);

            /* Перевіряємо наявність та кількість продукту */
            if (empty($products) || (int) $products[0]['count'] === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Product not found or out of stock']);
                exit;
            }

            $categories = $db->readAll('categories');
            $categoryMap = [];
            foreach ($categories as $category) {
                $categoryMap[$category['category_name']] = $category;
            }
            $result = [];
            foreach ($products as $product) {
                if (isset($categoryMap[$product['category']])) {
                    $result[] = decodeCharacteristics($product, $categoryMap[$product['category']]);
                } else {
                    $result[] = $product;
                }
            }
            return $result;

        case 'readCategoryProducts': /* Товари певної категорії */
            if (!isset($decryptedQuery['category'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Category required for readCategoryProducts']);
                exit;
            }
            $products = $db->readWithSort(
                'products',
                ['*'],
                ['category' => $decryptedQuery['category']]
            );
            $category = $db->read('categories', ['*'], ['category_name' => $decryptedQuery['category']]);

            $filtered = [];
            if (!empty($category)) {
                foreach ($products as $product) {
                    if ((int) $product['count'] === 0) {
                        continue;
                    }
                    $filtered[] = decodeCharacteristics($product, $category[0]);
                }
            }
            return $filtered;

        case 'readCategories': /* Характеристики категорій */
            return $db->readAll('categories');

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported operation']);
            exit;
    }
}

/* Функція для декодування характеристик продукту */
function decodeCharacteristics($product, $category)
{
    unset($product['uploadPath']); /* Видаляємо поле uploadPath */

    /* Розшифровуємо характеристики */
    $characteristics = explode(',', $product['characteristics']);
    $specifications = explode(',', $category['specifications']);

    $decodedCharacteristics = [];
    foreach ($specifications as $index => $spec) {
        $decodedCharacteristics[$spec] = $characteristics[$index] ?? null;
    }
    unset($product['characteristics']); /* Видаляємо поле characteristics */

    $product['decoded_characteristics'] = $decodedCharacteristics;
    return $product;
}
