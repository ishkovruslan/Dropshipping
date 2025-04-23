<?php
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

$authentication = new Authentication($db);
