<?php /* Модуль безпеки */
require_once('mysql.php'); // Підключення до бази

$roles = [/* Цифрове значення ролей */
    'user' => 0,
    'seller' => 1,
    'administrator' => 2
];

class AccessControl
{/* Клас керування доступом користувача */
    private $db;

    private $roles;

    public function __construct($db, $roles)
    {
        $this->db = $db;
        $this->roles = $roles;
    }

    public function getUserRole($login)
    {/* Роль користувача */
        $conditions = ['login' => $login];
        $result = $this->db->read('userlist', ['role'], $conditions);
        return isset($result[0]['role']) ? $result[0]['role'] : 'unauthorized';
    }

    public function getUserLevel($login)
    {/* Рівень доступу користувача */
        $role = $this->getUserRole($login);
        return isset($this->roles[$role]) ? $this->roles[$role] : -1;
    }

    public function checkAccess($minRequiredRole)
    {/* Перевірка доступу, якщо його немає відправляємо користувача на головну сторінку */
        if (!isset($_SESSION['login'])) {
            logAction($this->db, 'Неавторизований доступ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Спроба відкрити сторінку без авторизації ' . $_SESSION['login']);
            header("location: ../index.php");
            exit;
        }
        $role = $this->getUserRole($_SESSION['login']);
        if (!isset($this->roles[$role]) || $this->roles[$role] < $minRequiredRole) {
            logAction($this->db, 'Неавторизований доступ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Наявний рівень ' . $this->roles[$role] . ' Необхідний рівень ' . $minRequiredRole);
            header("location: ../index.php");
            exit;
        }
    }
}

class Authentication
{/* Клас авторизації/реєстрації */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate($login, $password)
    {/* Авторизація */
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
    {/* Унікальність логіна */
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
    {/* Реєстрація */
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
}

class RemoteAccess
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Функція генерації 64-бітного числа
    public function generateUniqueKey()
    {
        return random_int(0, PHP_INT_MAX);
    }

    // Записуємо або оновлюємо унікальний ключ користувача у базі
    public function setUniqueKey($login, $key)
    {
        $conditions = ['login' => $login];
        $data = ['unique_key' => $key];

        // Оновлюємо поле unique_key у користувача
        $this->db->update('userlist', $data, $conditions, 'i');
        logAction($this->db, 'Ключ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Користувач ' . $login . ' отримав новий ключ');
    }

    // Отримуємо registration_time і unique_key з бази
    public function getUserData($login)
    {
        $conditions = ['login' => $login];
        logAction($this->db, 'Ключ', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', 'Перегляд ключа користувача ' . $login);
        return $this->db->read('userlist', ['registration_time', 'unique_key'], $conditions);
    }

    // Функція для побітового XOR чисел
    public function xorKeys($key1, $key2)
    {
        return $key1 ^ $key2;
    }

    // Керування віддаленим доступом (інтерфейс)
    public function manageRemoteAccess($login)
    {
        // Отримуємо registration_time та unique_key з бази даних
        $userData = $this->getUserData($login);

        if (isset($userData[0]['registration_time'])) {
            $registrationTime = (int) $userData[0]['registration_time'];
            $uniqueKey = $userData[0]['unique_key'];

            // Якщо користувач натиснув на кнопку "Згенерувати ключ"
            if (isset($_POST['generate_key'])) {
                // Генеруємо новий ключ
                $newKey = $this->generateUniqueKey();
                $this->setUniqueKey($login, $newKey); // Оновлюємо ключ у базі
                $uniqueKey = $newKey; // Оновлюємо значення для відображення
            } elseif ($uniqueKey == 0) {
                // Якщо ключ не згенеровано, виводимо повідомлення, що ключ ще не згенеровано
                return "Ключ ще не згенеровано.";
            }

            // Виводимо результат XOR у десятковому вигляді, якщо ключ існує
            return $this->xorKeys($registrationTime, (int) $uniqueKey);
        }

        return "Помилка: дані користувача не знайдено!";
    }
}

$accessControl = new AccessControl($db, $roles);
$authentication = new Authentication($db);
$remoteAccess = new RemoteAccess($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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