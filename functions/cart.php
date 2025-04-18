<?php /* Функції обробки замовлень */
function _processOrder($db, array $input, bool $isApi = false)
{
    /* Обов'язкові поля */
    $required = ['full_name', 'phone', 'email', 'post_type', 'city', 'post_number'];
    if ($isApi) {
        $required[] = 'cart';
    }
    foreach ($required as $f) {
        if (!isset($input[$f]) || empty($input[$f])) {
            return ['error' => "Помилка: Поле $f є обов'язковим."];
        }
    }

    /* Дані споживача */
    $consumer = [
        'full_name' => $input['full_name'],
        'phone' => $input['phone'],
        'email' => $input['email'],
        'post' => $input['post_type'],
        'city' => $input['city'],
        'post_number' => $input['post_number'],
    ];
    $exists = $db->read('consumer', ['*'], ['full_name' => $consumer['full_name']]);
    if ($exists) {
        $db->update('consumer', $consumer, ['id' => $exists[0]['id']]);
    } else {
        $db->write('consumer', array_keys($consumer), array_values($consumer), str_repeat('s', count($consumer)));
    }

    /* Кошик і логін користувача */
    $cart = $input['cart'] ?? ($_SESSION['cart'] ?? []);
    $login = $input['login'] ?? ($_SESSION['login'] ?? '');

    /* Підрахунок кількості товарів */
    $countOverride = $input['productsCount'] ?? null;
    $productsCount = $countOverride !== null ? (int) $countOverride : count($cart);

    $ids = array_keys($cart);
    $quant = array_map(fn($i) => $i['quantity'], $cart);
    $realiz = array_map(fn($i) => $i['realization_price'], $cart);

    /* Мінімальна ціна товарів */
    $prices = [];
    foreach ($ids as $pid) {
        $r = $db->read('products', ['price'], ['id' => $pid]);
        if (!empty($r) && isset($r[0]['price'])) {
            $prices[] = $r[0]['price'];
        } else {
            $prices[] = $cart[$pid]['low_price'] ?? 0;
        }
    }

    /* Формуємо дані для таблиці orders */
    $orderData = [
        'login' => $login,
        'record_time' => (int) (microtime(true) * 1000),
        'products_count' => $productsCount,
        'products_list' => implode(',', $ids),
        'products_number' => implode(',', $quant),
        'products_realization' => implode(',', $realiz),
        'products_price' => implode(',', $prices),
    ] + $consumer;

    /* Типи для bind: всі рядки */
    $types = str_repeat('s', count($orderData));

    /* Запис у orders */
    if (!$db->write('orders', array_keys($orderData), array_values($orderData), $types)) {
        /* Оновлюємо залишки */
        foreach ($cart as $pid => $item) {
            $res = $db->read('products', ['count'], ['id' => $pid]);
            if (!empty($res)) {
                $new = max(0, (int) $res[0]['count'] - $item['quantity']);
                $db->update('products', ['count' => $new], ['id' => $pid]);
            }
        }
        return ['success' => true];
    }

    return ['error' => 'Помилка: Не вдалося додати замовлення. Зверніться до адміністратора'];
}

/* Обробка WEB замовлень */
function createOrder($db)
{
    $input = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'post_type' => $_POST['post_type'] ?? '',
        'city' => $_POST['city'] ?? '',
        'post_number' => $_POST['post_number'] ?? '',
        'cart' => $_SESSION['cart'] ?? [],
        'login' => $_SESSION['login'] ?? '',
    ];

    $res = _processOrder($db, $input, false);
    if (isset($res['error'])) {
        exit($res['error']);
    }

    /* Дії після успішного замовлення */
    unset($_SESSION['cart']);
    header('Location: ../index.php');
}

/* Обробка API замовлень */
function createOrderAPI($db, $orderData)
{
    $res = _processOrder($db, $orderData, true);
    return $res;
}
