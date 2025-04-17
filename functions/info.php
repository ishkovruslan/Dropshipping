
<?php
require_once 'access.php';

function operation($operation, $decryptedQuery)
{
    global $db;
    switch ($operation) {
        case 'readAllProducts':
            $products = $db->readAll('products');
            $categories = $db->readAll('categories');
            $categoryMap = [];
            foreach ($categories as $category) {
                $categoryMap[$category['category_name']] = $category;
            }
            foreach ($products as &$product) {
                if (isset($categoryMap[$product['category']])) {
                    $product = decodeCharacteristics($product, $categoryMap[$product['category']]);
                }
            }
            return $products;
            break;

        case 'readProduct':
            if (!isset($decryptedQuery['conditions'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Conditions required for readProduct']);
                exit;
            }
            $products = $db->readWithSort('products', ['*'], $decryptedQuery['conditions']);
            $categories = $db->readAll('categories');
            $categoryMap = [];
            foreach ($categories as $category) {
                $categoryMap[$category['category_name']] = $category;
            }
            foreach ($products as &$product) {
                if (isset($categoryMap[$product['category']])) {
                    $product = decodeCharacteristics($product, $categoryMap[$product['category']]);
                }
            }
            return $products;
            break;

        case 'readCategoryProducts':
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
            if (!empty($category)) {
                foreach ($products as &$product) {
                    $product = decodeCharacteristics($product, $category[0]);
                }
            }
            return $products;
            break;

        case 'readCategories':
            return $db->readAll('categories');
            break;

        case 'createOrder':
            // Перевірка наявності даних замовлення у запиті
            if (!isset($decryptedQuery['orderData'])) {
                http_response_code(400);
                return ['error' => 'Order data required'];
            }
            $orderData = $decryptedQuery['orderData'];
            // Підключаємо файл з функцією обробки замовлення
            require_once 'php/cart.php';
            // Виклик функції для оформлення замовлення через API
            $result = createOrderAPI($db, $orderData);
            return $result;
            break;            

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported operation']);
            exit;
    }
}
