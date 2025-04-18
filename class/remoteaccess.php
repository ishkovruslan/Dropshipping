<?php
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
        /* Отримуємо дані користувача */
        $userData = $this->getUserData($type, $login);

        if (isset($userData[0]['registration_time'])) {
            $registrationTime = (int) $userData[0]['registration_time'];
            /* Генеруємо новий унікальний ключ */
            $newUniqueKey = $this->generateUniqueKey();
            /* Зберігаємо новий ключ для користувача */
            $this->setUniqueKey($type, $login, $newUniqueKey);
            /* Повертаємо "повний" ключ (з використанням xor з часом реєстрації) */
            return $this->xorKeys($registrationTime, (int) $newUniqueKey);
        }
        return "Помилка: дані користувача не знайдено!";
    }
}

$remoteAccess = new RemoteAccess($db);
