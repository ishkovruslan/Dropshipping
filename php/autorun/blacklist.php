<?php
// Отримуємо поточний час в мілісекундах
$currentTime = round(microtime(true) * 1000);
$oneMinuteAgo = $currentTime - 60000;

// Читаємо логи лише для source_type 'WEB'
$recentLogs = $db->readWithSort(
    'log',
    ['login', 'source_ip'],
    [
        'source_time >=' => $oneMinuteAgo,
        'source_time <=' => $currentTime,
        'source_type'    => 'WEB'
    ]
);

$loginCounts = [];
$ipCounts = [];

// Рахуємо кількість записів для кожного логіну та IP
foreach ($recentLogs as $log) {
    if (!isset($loginCounts[$log['login']])) {
        $loginCounts[$log['login']] = 0;
    }
    $loginCounts[$log['login']]++;

    if (!isset($ipCounts[$log['source_ip']])) {
        $ipCounts[$log['source_ip']] = 0;
    }
    $ipCounts[$log['source_ip']]++;
}

// Якщо кількість записів перевищує 5, додаємо до списку блокувань
$loginsToBlock = array_keys(array_filter($loginCounts, function ($count) {
    return $count > 5;
}));

$ipsToBlock = array_keys(array_filter($ipCounts, function ($count) {
    return $count > 5;
}));

// Якщо один з параметрів вже знаходиться у списку для блокування,
// додаємо відповідний інший параметр (логін або IP)
foreach ($recentLogs as $log) {
    if (in_array($log['login'], $loginsToBlock)) {
        $ipsToBlock[] = $log['source_ip'];
    }
    if (in_array($log['source_ip'], $ipsToBlock)) {
        $loginsToBlock[] = $log['login'];
    }
}

$loginsToBlock = array_unique($loginsToBlock);
$ipsToBlock = array_unique($ipsToBlock);

// Встановлюємо дату блокування до наступного дня
$date = date('Y-m-d', strtotime('+1 day'));

// Читаємо вже існуючі записи у чорному списку для заданої дати
$existingBlacklist = $db->read(
    'blacklist',
    ['block_type', 'block_id'],
    ['date' => $date]
);

$alreadyBlocked = [];
foreach ($existingBlacklist as $entry) {
    $alreadyBlocked[$entry['block_type']][$entry['block_id']] = true;
}

// Додаємо нові блокування за логіном, якщо вони ще не внесені
foreach ($loginsToBlock as $login) {
    if (empty($alreadyBlocked['login'][$login])) {
        $db->write(
            'blacklist',
            ['block_type', 'block_id', 'date'],
            ['login', $login, $date],
            'sss'
        );
    }
}

// Додаємо нові блокування за IP, якщо вони ще не внесені
foreach ($ipsToBlock as $ip) {
    if (empty($alreadyBlocked['ip'][$ip])) {
        $db->write(
            'blacklist',
            ['block_type', 'block_id', 'date'],
            ['ip', $ip, $date],
            'sss'
        );
    }
}
?>
