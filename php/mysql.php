<?php
if (!class_exists('Database')) {/* Запобіжник від подвійного використання */
    class Database
    {/* Клас взаємодії з БД */
        public $conn;

        public function __construct($servername, $username, $password, $dbname)
        {
            $this->conn = new mysqli($servername, $username, $password, $dbname);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
            $this->connection = $this->conn; // Додано для ініціалізації властивості connection
        }

        public function read($tablename, $columns, $conditions = [])
        {/* Читання за певних умов, в більшості випадків по рядках */
            $columnString = implode(',', $columns);
            $sql = "SELECT $columnString FROM $tablename";
            if (!empty($conditions)) {
                $conditionStrings = [];
                foreach ($conditions as $column => $value) {
                    $conditionStrings[] = "$column = ?";
                }
                $sql .= " WHERE " . implode(' AND ', $conditionStrings);
            }

            $stmt = $this->conn->prepare($sql);

            if (!empty($conditions)) {
                $types = str_repeat("s", count($conditions));
                $stmt->bind_param($types, ...array_values($conditions));
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;
        }

        public function readAll($tablename)
        {/* Читання всієї таблиці */
            $sql = "SELECT * FROM $tablename";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;
        }

        public function readWithSort($tablename, $columns, $conditions = [], $orderBy = [])
        {
            // Create a string for selected columns
            $columnString = implode(',', $columns);
            $sql = "SELECT $columnString FROM $tablename";

            // Process conditions
            $conditionStrings = [];
            $params = [];
            if (!empty($conditions)) {
                foreach ($conditions as $column => $value) {
                    if (stripos($column, 'LIKE') !== false || stripos($column, '>=') !== false || stripos($column, '<=') !== false) {
                        $conditionStrings[] = "$column ?";
                    } else {
                        $conditionStrings[] = "$column = ?";
                    }
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(" AND ", $conditionStrings);
            }

            // Process order by clause
            if (!empty($orderBy)) {
                $orderStrings = [];
                foreach ($orderBy as $column => $direction) {
                    $orderStrings[] = "$column $direction";
                }
                $sql .= " ORDER BY " . implode(', ', $orderStrings);
            }

            // Prepare statement
            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }

            // Execute query
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];

            // Fetch data
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }

            return $data;
        }

        public function update($table, $data, $conditions)
        {/* Оновлення запису */
            $setStrings = [];
            $conditionStrings = [];
            $values = [];
            foreach ($data as $column => $value) {
                $setStrings[] = "$column = ?";
                $values[] = $value;
            }
            foreach ($conditions as $column => $value) {
                $conditionStrings[] = "$column = ?";
                $values[] = $value;
            }
            $sql = "UPDATE $table SET " . implode(', ', $setStrings) . " WHERE " . implode(' AND ', $conditionStrings);
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $this->conn->error);
            }
            $types = str_repeat("s", count($values));
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute() === false) {
                die("Error executing statement: " . $stmt->error);
            }
            $stmt->close();
        }

        public function write($table, $columns, $values, $types)
        {/* Запис інформації */
            $columns_str = implode(", ", $columns);
            $placeholders = implode(", ", array_fill(0, count($values), '?'));
            $sql = "INSERT INTO $table ($columns_str) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $this->conn->error);
            }
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute() === false) {
                die("Error executing statement: " . $stmt->error);
            }
            $stmt->close();
        }

        public function remove($table, $columns, $values)
        {/* Видалення запису */
            $conditions = [];
            $types = '';
            foreach ($columns as $column) {
                $conditions[] = "$column = ?";
                $types .= is_int($values[array_search($column, $columns)]) ? 'i' : 's';
            }
            $sql = "DELETE FROM $table WHERE " . implode(' AND ', $conditions);
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new mysqli_sql_exception($this->conn->error);
            }
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            if ($stmt->error) {
                throw new mysqli_sql_exception($stmt->error);
            }
            $stmt->close();
        }

        public function searchLike($tablename, $columns, $searchColumn, $searchValue, $limit = 10)
        {
            $columnString = implode(',', $columns);
            $sql = "SELECT $columnString FROM $tablename WHERE $searchColumn LIKE ? LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $this->conn->error);
            }

            $searchValue = "%" . $searchValue . "%";
            $stmt->bind_param('si', $searchValue, $limit); // 'si' - s для рядка, i для цілого числа
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $stmt->close();
            return $data;
        }

        public function readMessagesForRole($currentUser, $targetUser, $userLevel)
        {
            $isAdmin = $userLevel >= 2;
            if ($isAdmin) {
                // Всі повідомлення до/від адміністратора повинні виглядати з іменем "administrator"
                $sql = "SELECT 
                    CASE WHEN sender = 'administrator' THEN 'administrator' ELSE sender END AS sender,
                    CASE WHEN receiver = 'administrator' THEN 'administrator' ELSE receiver END AS receiver,
                    message, 
                    source_time 
                FROM messages 
                WHERE receiver = 'administrator' OR sender = 'administrator' 
                ORDER BY source_time ASC";
                $stmt = $this->conn->prepare($sql);
            } else {
                // Для звичайного користувача
                $sql = "SELECT sender, receiver, message, source_time 
                FROM messages 
                WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?) 
                ORDER BY source_time ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssss", $currentUser, $targetUser, $targetUser, $currentUser);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            return $messages;
        }

        public function getUserRegistrationTime($login)
        {
            $sql = "SELECT registration_time FROM userlist WHERE login = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            return $userData['registration_time'] ?? 0;
        }

        public function getMessagesForAdmin()
        {
            // Отримання списку адміністраторів
            $queryAdmins = "SELECT login FROM userlist WHERE role = 'administrator'";
            $resultAdmins = $this->conn->query($queryAdmins);

            if (!$resultAdmins) {
                throw new Exception("Помилка отримання списку адміністраторів: " . $this->conn->error);
            }

            $adminLogins = $resultAdmins->fetch_all(MYSQLI_ASSOC);

            // Створення масиву логінів адміністраторів
            $adminLoginsArray = array_map(function ($admin) {
                return $admin['login'];
            }, $adminLogins);

            // Формування списку місць для плейсхолдерів у запиті
            $placeholders = implode(',', array_fill(0, count($adminLoginsArray), '?'));

            // Основний запит для отримання повідомлень
            $query = "
                        SELECT 
                            m.sender AS login,
                            MAX(m.message) AS message, -- Вибір останнього повідомлення
                            MAX(m.source_time) AS last_time
                        FROM messages m
                        WHERE m.sender NOT IN ($placeholders) -- Виключення адміністраторів
                        GROUP BY m.sender
                        ORDER BY last_time DESC
                    ";

            $stmt = $this->conn->prepare($query);

            if (!$stmt) {
                throw new Exception("Помилка підготовки запиту: " . $this->conn->error);
            }

            // Прив'язка параметрів
            $stmt->bind_param(str_repeat('s', count($adminLoginsArray)), ...$adminLoginsArray);

            $stmt->execute();
            $result = $stmt->get_result();

            return $result->fetch_all(MYSQLI_ASSOC);
        }

        public function saveMessage($sender, $receiver, $message, $time)
        {
            $adminAlias = 'administrator';

            // Перевірка: якщо відправник або отримувач — адміністратор, підмінити логін
            if ($this->isAdmin($sender)) {
                $sender = $adminAlias;
            }
            if ($this->isAdmin($receiver)) {
                $receiver = $adminAlias;
            }

            $query = "
        INSERT INTO messages (sender, receiver, message, source_time)
        VALUES (?, ?, ?, ?)
    ";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Помилка підготовки запиту: " . $this->conn->error);
            }

            // Перевірка на порожні значення
            if (empty($sender) || empty($receiver)) {
                throw new Exception("Відправник або отримувач не вказаний.");
            }

            $stmt->bind_param("sssi", $sender, $receiver, $message, $time);
            return $stmt->execute();
        }

        // Додаткова функція для перевірки ролі адміністратора
        private function isAdmin($login)
        {
            $sql = "SELECT role FROM userlist WHERE login = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            return isset($userData['role']) && $userData['role'] === 'administrator';
        }

    }

    $servername = "localhost:3306";
    $username = "root";
    $password = "Kharkiv2024";
    $dbname = "bacalavr";

    $db = new Database($servername, $username, $password, $dbname);
}
function logAction($db, $operation, $login, $sourceIp, $sourceType, $sourceResult)
{
    $sourceTime = round(microtime(true) * 1000); // Час у мс
    $columns = ['operation', 'login', 'source_ip', 'source_type', 'source_result', 'source_time'];
    $values = [$operation, $login, $sourceIp, $sourceType, $sourceResult, $sourceTime];
    $types = 'ssssss';

    $db->write('log', $columns, $values, $types);
}

$table = 'log';
$columns = ['*'];

$filter = $_GET['filter'] ?? null;
$conditions = $filter ? ['login' => $filter] : ['login'];
$logs = $db->read($table, $columns, $conditions);
?>