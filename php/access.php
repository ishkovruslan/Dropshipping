<?php /* Модуль безпеки */
require_once ('mysql.php'); // Підключення до бази

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
            header("location: ../index.php");
            exit;
        }
        $role = $this->getUserRole($_SESSION['login']);
        if (!isset($this->roles[$role]) || $this->roles[$role] < $minRequiredRole) {
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
                return array('success' => true);
            }
        }
        return array('success' => false, 'message' => "Невірний логін або пароль!");
    }

    public function isLoginUnique($login)
    {/* Унікальність логіна */
        $conditions = ['login' => $login];
        $result = $this->db->read('users', ['login'], $conditions);
        if (count($result) > 0) {
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
            $dataUserList = [$login, 'user'];
            $this->db->write('userlist', ['login', 'role'], $dataUserList, 'ss');
            $this->authenticate($login, $password);
            return array('success' => true);
        }
    }
}

$accessControl = new AccessControl($db, $roles);
$authentication = new Authentication($db);

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