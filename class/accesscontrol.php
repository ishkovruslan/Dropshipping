<?php
class AccessControl
{ /* Клас керування доступом користувача */
    private $db;

    private $roles;

    public function __construct($db)
    {
        $roles = [ /* Числове значення ролей */
            'user' => 0,
            'seller' => 1,
            'administrator' => 2
        ];
        $this->db = $db;
        $this->roles = $roles;
    }

    public function getUserRole($login)
    { /* Роль користувача */
        $conditions = ['login' => $login];
        $result = $this->db->read('userlist', ['role'], $conditions);
        return isset($result[0]['role']) ? $result[0]['role'] : 'unauthorized';
    }

    public function getUserLevel($login)
    { /* Рівень доступу користувача */
        $role = $this->getUserRole($login);
        return isset($this->roles[$role]) ? $this->roles[$role] : -1;
    }

    public function checkAccess($minRequiredRole)
    { /* Перевірка доступу */
        if (!isset($_SESSION['login'])) {
            logAction($this->db, 'Неавторизований доступ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Спроба відкрити сторінку без авторизації ' . $_SESSION['login']);
            header("location: ../index.php");
            exit;
        }

        $currentIp = $_SERVER['REMOTE_ADDR'];
        if ($this->isBlocked($_SESSION['login'], $currentIp)) {
            header("Location: ../functions/logout.php");
            exit;
        }

        $role = $this->getUserRole($_SESSION['login']);
        if (!isset($this->roles[$role]) || $this->roles[$role] < $minRequiredRole) {
            logAction($this->db, 'Неавторизований доступ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Наявний рівень ' . $this->roles[$role] . ' Необхідний рівень ' . $minRequiredRole);
            header("location: ../index.php");
            exit;
        }
    }

    public function isMostFrequentIp($login, $currentIp)
    { /* Пошук найчастішої адреси */
        $result = $this->db->read(
            'log',
            ['source_ip'],
            ['login' => $login]
        );

        if (empty($result)) {
            return false;
        }

        $ipCounts = [];
        foreach ($result as $row) {
            $ip = $row['source_ip'];
            if (!isset($ipCounts[$ip])) {
                $ipCounts[$ip] = 0;
            }
            $ipCounts[$ip]++;
        }

        arsort($ipCounts); /* Сортування усіх адрес користувача */
        $mostFrequentIp = key($ipCounts); /* Перша адреса є найпопулярнішою */

        return $mostFrequentIp === $currentIp;
    }

    public function isBlocked($login, $ip)
    { /* Перевірка блокування за логіном */
        $conditions = [
            'block_id' => $login,
            'block_type' => 'login'
        ];
        $result = $this->db->read('blacklist', ['id', 'date'], $conditions);

        if (!empty($result)) {
            $blockDate = $result[0]['date'];
            if (strtotime($blockDate . ' 23:59:59') >= time()) {
                return true; /* Логін заблоковано */
            }
        }

        /* Перевірка блокування за IP-адресою */
        $conditions = [
            'block_id' => $ip,
            'block_type' => 'ip'
        ];
        $result = $this->db->read('blacklist', ['id', 'date'], $conditions);

        if (!empty($result)) {
            $blockDate = $result[0]['date'];
            if (strtotime($blockDate . ' 23:59:59') >= time()) {
                return true; /* Логін заблоковано */
            }
        }
        return false; /* Блокувань немає */
    }
}

$accessControl = new AccessControl($db);
