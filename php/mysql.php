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
        {/* Читання з сортуванням */
            $columnString = implode(',', $columns);
            $sql = "SELECT $columnString FROM $tablename";
            if (!empty($conditions)) {
                $conditionStrings = [];
                foreach ($conditions as $column => $value) {
                    $conditionStrings[] = strpos($column, '>=') !== false || strpos($column, '<=') !== false ? "$column ?" : "$column = ?";
                }
                $sql .= " WHERE " . implode(' AND ', $conditionStrings);
            }
            if (!empty($orderBy)) {
                $orderStrings = [];
                foreach ($orderBy as $column => $direction) {
                    $orderStrings[] = "$column $direction";
                }
                $sql .= " ORDER BY " . implode(', ', $orderStrings);
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
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $this->connection->error);
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
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new mysqli_sql_exception($this->connection->error);
            }
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            if ($stmt->error) {
                throw new mysqli_sql_exception($stmt->error);
            }
            $stmt->close();
        }
    }

    $servername = "localhost:3306";
    $username = "root";
    $password = "Kharkiv2024";
    $dbname = "bacalavr";

    $db = new Database($servername, $username, $password, $dbname);
}
?>