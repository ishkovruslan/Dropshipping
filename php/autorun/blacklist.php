<?php
$currentTime = round(microtime(true) * 1000);
$oneMinuteAgo = $currentTime - 60000;

$recentLogs = $db->readWithSort(
    'log',
    ['login', 'source_ip'],
    [
        'source_time >=' => $oneMinuteAgo,
        'source_time <=' => $currentTime
    ]
);
$loginCounts = [];
$ipCounts = [];

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

$loginsToBlock = array_keys(array_filter($loginCounts, function ($count) {
    return $count > 5;
}));

$ipsToBlock = array_keys(array_filter($ipCounts, function ($count) {
    return $count > 5;
}));

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

$existingBlacklist = $db->read(
    'blacklist',
    ['block_type', 'block_id'],
    ['date' => date('Y-m-d')]
);

$alreadyBlocked = [];
foreach ($existingBlacklist as $entry) {
    $alreadyBlocked[$entry['block_type']][$entry['block_id']] = true;
}

$date = date('Y-m-d');

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
