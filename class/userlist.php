<?php
class UserList
{ /* Клас взаємодії з користувачами */
    private $users = [];
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function loadUsersFromDB()
    { /* Завантаження користувачів з БД */
        $this->users = [];
        $result = $this->db->read('userlist', ['login', 'role']);
        foreach ($result as $row) {
            require_once('user.php');
            $this->users[] = new User($row['login'], $row['role'], $this->db);
        }
    }

    public function getUserByLogin($login)
    { /* Пошук користувача за логіном */
        foreach ($this->users as $user) {
            if ($user->getLogin() == $login) {
                return $user;
            }
        }
        return null;
    }

    public function getUsers()
    { /* Отримуємо користувачів */
        return $this->users;
    }
}

$userList = new UserList($db);
