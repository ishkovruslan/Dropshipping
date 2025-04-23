<?php
class User
{ /* Клас взаємодія з користувачами */
    private $login;
    private $role;
    private $db;

    public function __construct($login, $role, $db)
    {
        $this->login = $login;
        $this->role = $role;
        $this->db = $db;
    }

    public function changeRole($newRole)
    { /* Зміна ролі */
        $data = ['role' => $newRole];
        $conditions = ['login' => $this->login];
        $this->db->update('userlist', $data, $conditions);
        $this->role = $newRole;
        logAction($this->db, 'Зміна ролі', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' змінив роль користувачу ' . $conditions['login'] . ' на ' . $newRole);
    }

    public function deleteUser()
    { /* Видалення користувача */
        $this->db->remove('userlist', ['login'], [$this->login]);
        $this->db->remove('users', ['login'], [$this->login]);
        logAction($this->db, 'Видалення користувача', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' видалив користувача ' . $this->login);
    }

    public function getLogin()
    { /* Отримання логіну */
        return $this->login;
    }

    public function getRole()
    { /* Отримання ролі */
        return $this->role;
    }
}
