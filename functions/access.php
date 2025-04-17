<?php
require_once('mysql.php'); /* Підключення до БД */

$roles = [ /* Числове значення ролей */
    'user' => 0,
    'seller' => 1,
    'administrator' => 2
];

class AccessControl
{ /* Клас керування доступом користувача */
    private $db;

    private $roles;

    public function __construct($db, $roles)
    {
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

class Authentication
{ /* Клас авторизації/реєстрації */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate($login, $password)
    { /* Авторизація */
        $currentIp = $_SERVER['REMOTE_ADDR'];

        /* Перевірка блокування */
        if ($this->isBlocked($login, $currentIp)) {
            header("Location: ../functions/logout.php");
            exit;
        }

        $conditions = ['login' => $login];
        $result = $this->db->read('users', ['login', 'password'], $conditions);
        if (count($result) === 1) {
            $user = $result[0];
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['loggedin'] = true;
                $_SESSION['login'] = $login;
                logAction($this->db, 'Авторизація', $login, $_SERVER['REMOTE_ADDR'], 'WEB', 'Успішна авторизація ' . $login);
                return array('success' => true);
            }
        }
        logAction($this->db, 'Авторизація', $login, $_SERVER['REMOTE_ADDR'], 'WEB', 'Невірний логін або пароль ' . $login);
        return array('success' => false, 'message' => "Невірний логін або пароль!");
    }

    public function isLoginUnique($login)
    { /* Унікальність логіна */
        $conditions = ['login' => $login];
        $result = $this->db->read('users', ['login'], $conditions);
        if (count($result) > 0) {
            logAction($this->db, 'Реєстрація', $login, $_SERVER['REMOTE_ADDR'], 'WEB', 'Спроба реєстрації під чужим логіном ' . $login);
            return array('unique' => false, 'message' => "Цей логін вже використовується!");
        } else {
            return array('unique' => true);
        }
    }

    public function register($login, $password)
    { /* Реєстрація */
        $currentIp = $_SERVER['REMOTE_ADDR'];

        /* Перевірка блокування */
        if ($this->isBlocked($login, $currentIp)) {
            header("Location: ../functions/logout.php");
            exit;
        }

        if (strlen($password) < 8) {
            return array('success' => false, 'message' => "Пароль повинен містити мінімум 8 символів!");
        }
        $checkLogin = $this->isLoginUnique($login);
        if (!$checkLogin['unique']) {
            return array('success' => false, 'message' => $checkLogin['message']);
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $data = [$login, $hashedPassword];
            $this->db->write('users', ['login', 'password'], $data, 'ss');
            $microtime = microtime(true);
            $timeInNanoseconds = (int) ($microtime * 1e9);
            $dataUserList = [$login, 'user', $timeInNanoseconds, 0];
            $this->db->write('userlist', ['login', 'role', 'registration_time', 'unique_key'], $dataUserList, 'ssii');
            $this->authenticate($login, $password);
            logAction($this->db, 'Реєстрація', $login, $_SERVER['REMOTE_ADDR'], 'WEB', 'Успішна реєстрація ' . $login);
            return array('success' => true);
        }
    }

    public function changePassword($login, $currentPassword, $newPassword, $confirmPassword)
    { /* Зміна паролю */
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => "Новий пароль не співпадає з підтвердженням!"];
        }

        $conditions = ['login' => $login];
        $user = $this->db->read('users', ['password'], $conditions);
        if (empty($user)) {
            return ['success' => false, 'message' => "Користувача не знайдено!"];
        }

        if (!password_verify($currentPassword, $user[0]['password'])) {
            return ['success' => false, 'message' => "Старий пароль невірний!"];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => "Новий пароль повинен містити мінімум 8 символів!"];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', ['password' => $hashedPassword], $conditions, 's');
        logAction($this->db, 'Зміна паролю', $login, $_SERVER['REMOTE_ADDR'], 'WEB', 'Пароль успішно змінено');
        return ['success' => true];
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
            if (strtotime($blockDate) >= time()) {
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
            if (strtotime($blockDate) >= time()) {
                return true; /* Адресу заблоковано */
            }
        }

        return false; /* Блокувань немає */
    }
}

class RemoteAccess
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function generateUniqueKey()
    { /* Генерація 64 бітного значення */
        return random_int(0, PHP_INT_MAX);
    }

    public function setUniqueKey($type, $login, $key)
    { /* Оновлення унікального ключа */
        $conditions = ['login' => $login];
        $data = ['unique_key' => $key];

        $this->db->update('userlist', $data, $conditions, 'i');
        if ($type == "API") {
            logAction($this->db, 'Ключ', $login, $_SERVER['REMOTE_ADDR'], $type, 'Користувач ' . $login . ' отримав новий ключ');
        } else {
            logAction($this->db, 'Ключ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], $type, 'Користувач ' . $login . ' отримав новий ключ');
        }
    }


    public function getUserData($type, $login)
    { /* Інформація про користувача */
        $conditions = ['login' => $login];
        if ($type == "WEB") {
            logAction($this->db, 'Ключ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Перегляд ключа користувача ' . $login);
        } else if ($type == "API") {
            logAction($this->db, 'Ключ', $login, $_SERVER['REMOTE_ADDR'], 'API', 'Перегляд ключа користувача ' . $login);
        } else {
            require_once('autorun/blacklist.php');
        }
        return $this->db->read('userlist', ['registration_time', 'unique_key'], $conditions);
    }

    public function xorKeys($key1, $key2)
    { /* Побітовий XOR */
        return $key1 ^ $key2;
    }

    public function manageRemoteAccess($type, $login)
    { /* Ключ для API */
        $userData = $this->getUserData($type, $login);

        if (isset($userData[0]['registration_time'])) {
            $registrationTime = (int) $userData[0]['registration_time'];
            $uniqueKey = $userData[0]['unique_key'];
            if (isset($_POST['generate_key'])) {
                $newKey = $this->generateUniqueKey();
                $this->setUniqueKey($type, $login, $newKey);
                $uniqueKey = $newKey;
            } elseif ($uniqueKey == 0) {
                return "Ключ ще не згенеровано.";
            }
            return $this->xorKeys($registrationTime, (int) $uniqueKey);
        }
        return "Помилка: дані користувача не знайдено!";
    }

    public function changeKey($type, $login)
    {
        // Отримуємо дані користувача
        $userData = $this->getUserData($type, $login);

        if (isset($userData[0]['registration_time'])) {
            $registrationTime = (int) $userData[0]['registration_time'];
            // Генеруємо новий унікальний ключ
            $newUniqueKey = $this->generateUniqueKey();
            // Зберігаємо новий ключ для користувача
            $this->setUniqueKey($type, $login, $newUniqueKey);
            // Повертаємо "повний" ключ (з використанням xor з часом реєстрації)
            return $this->xorKeys($registrationTime, (int) $newUniqueKey);
        }
        return "Помилка: дані користувача не знайдено!";
    }
}

$accessControl = new AccessControl($db, $roles);
$authentication = new Authentication($db);
$remoteAccess = new RemoteAccess($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") { /* Обробник для реєстрації/авторизації */
    if (isset($_POST['login_submit'])) {
        $login = $_POST['login'];
        $password = $_POST['password'];
        $result = $authentication->authenticate($login, $password);
        if ($result['success']) {
            header('Location: ../index.php');
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    } elseif (isset($_POST['register_submit'])) {
        $login = $_POST['login'];
        $password = $_POST['password'];
        $result = $authentication->register($login, $password);
        if ($result['success']) {
            header('Location: ../index.php');
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    }
}
?>