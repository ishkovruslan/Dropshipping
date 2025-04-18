<div class="log"> <!-- Таблиця взаємодії з записами дій -->
    <div class="table-selection">
        <h1>Виберіть таблицю для перегляду:</h1>
        <ul>
            <li><a href="?table=log&operation=Неавторизований доступ">Неавторизований доступ</a></li>
            <li><a href="?table=log&operation=Авторизація">Авторизація</a></li>
            <li><a href="?table=log&operation=Реєстрація">Реєстрація</a></li>
            <li><a href="?table=log&operation=Зміна ролі">Зміна ролі</a></li>
            <li><a href="?table=log&operation=Ключ">Ключ</a></li>
            <li><a href="?table=log&source_type=WEB">WEB</a></li>
            <li><a href="?table=log&source_type=API">API</a></li>
        </ul>
        <ul>
            <li><a href="?table=log">Перегляд всіх дій</a></li>
            <li><a href="?table=log&role=user">Дії всіх користувачів</a></li>
            <li><a href="?table=log&role=seller">Дії всіх продавців</a></li>
            <li><a href="?table=log&role=administrator">Дії всіх адміністраторів</a></li>
        </ul>
    </div>
    <h1>Перегляд дій</h1>
    <table>
        <tr>
            <th>Операція</th>
            <th>Користувач</th>
            <th>IP</th>
            <th>Тип джерела</th>
            <th>Результат</th>
            <th>Час</th>
        </tr>
        <?php $logs = $db->readAll('log');
        function getFilteredLogs($logs)
        {
            usort($logs, function ($a, $b) {
                return $b['source_time'] <=> $a['source_time'];
            });
            $last24HoursLogs = array_filter($logs, function ($log) {
                $logTime = substr($log['source_time'], 0, -3);
                return $logTime >= strtotime('-24 hours');
            });
            if (count($last24HoursLogs) > 50) {
                return $last24HoursLogs;
            }
            return array_slice($logs, 0, 50);
        }
        $operation = $_GET['operation'] ?? null;
        $source_type = $_GET['source_type'] ?? null;
        $role = $_GET['role'] ?? null;
        $user = $_GET['user'] ?? null;
        $ip = $_GET['ip'] ?? null;
        $date = $_GET['date'] ?? null;
        $usersByRole = [];
        if ($role) {
            $users = $db->readAll('userlist');
            $usersByRole = array_column(array_filter($users, fn($u) => $u['role'] === $role), 'login');
        }
        if (!$operation && !$source_type && !$role && !$user && !$ip && !$date) {
            $filteredLogs = getFilteredLogs($logs);
        } else {
            $filteredLogs = array_filter($logs, function ($log) use ($operation, $source_type, $user, $role, $usersByRole, $ip, $date) {
                $matchesOperation = $operation ? $log['operation'] === $operation : true;
                $matchesSourceType = $source_type ? $log['source_type'] === $source_type : true;
                $matchesUser = $user ? $log['login'] === $user : true;
                $matchesRole = $role ? in_array($log['login'], $usersByRole) : true;
                $matchesIP = $ip ? $log['source_ip'] === $ip : true;
                $matchesDate = $date ? date("Y-m-d", substr($log['source_time'], 0, -3)) === $date : true;
                return $matchesOperation && $matchesSourceType && $matchesUser && $matchesRole && $matchesIP && $matchesDate;
            });
            $filteredLogs = getFilteredLogs($filteredLogs);
        }
        foreach ($filteredLogs as $log): ?>
            <tr>
                <td><a
                        href="?table=log&operation=<?php echo urlencode($log['operation']); ?>"><?php echo $log['operation']; ?></a>
                </td>
                <td><a href="?table=log&user=<?php echo urlencode($log['login']); ?>"><?php echo $log['login']; ?></a></td>
                <td><a href="?table=log&ip=<?php echo urlencode($log['source_ip']); ?>"><?php echo $log['source_ip']; ?></a>
                </td>
                <td><a
                        href="?table=log&source_type=<?php echo urlencode($log['source_type']); ?>"><?php echo $log['source_type']; ?></a>
                </td>
                <td><?php echo $log['source_result']; ?></td>
                <td><a
                        href="?table=log&date=<?php echo date("Y-m-d", substr($log['source_time'], 0, -3)); ?>"><?php echo date("Y-m-d H:i:s", substr($log['source_time'], 0, -3)); ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
