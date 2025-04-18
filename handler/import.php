<?php /* Імпорт товарів з .csv файлів, відновлення тестових зображень */
/* Код є відносно ізольованим, та використовується лише при ініціалізації/роботі з тестовими записами */
$servername = "localhost:3306";
$username = "root";
$password = "Kharkiv2024";
$dbname = "bacalavr";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) { /* Перевірка з'єднання */
    die("Connection failed: " . $conn->connect_error);
}

function insertDataFromCSV($conn, $filename, $tablename)
{ /* Імпорт з .csv в БД */
    $countQuery = "SELECT COUNT(*) as count FROM $tablename";
    $result = $conn->query($countQuery);
    $row = $result->fetch_assoc();
    $rowCount = $row['count'];

    if ($rowCount > 0) {
        return;
    }

    $file = fopen("../data/$filename", "r");
    while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
        $types = ""; /* Тип за замовчуванням */
        /* Обробка специфікацій для категорій та продуктів */
        if ($filename == "categories.csv") {
            $data[1] = implode(",", array_slice($data, 1));
            $data = array_slice($data, 0, 2); /* Все що йде після 1 значення сприймаємо як один масив */
            $types = "ssi"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
            $data[] = NULL; /* Додаємо NULL в кінець масиву даних для стовпця id */
        } elseif ($filename == "products.csv") {
            $data[5] = implode(",", array_slice($data, 5));
            $data = array_slice($data, 0, 6); /* Все що йде після 5 значення сприймаємо як 1 масив */
            $types = "ssiissi"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
            $data[] = NULL; /* Додаємо NULL в кінець масиву даних для стовпця id */
        } elseif ($filename == "userlist.csv") {
            $types = "ssii"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
        } elseif ($filename == "orders.csv" /*  && $data[9 + (int)$data[2] * 4] < 10 */) {
            /* Парсинг замовлень */
            $login = $data[0];
            $time = $data[1];
            $recordCount = (int) $data[2];
            $group1 = implode(",", array_slice($data, 3, $recordCount));
            $group2 = implode(",", array_slice($data, 3 + $recordCount, $recordCount));
            $group3 = implode(",", array_slice($data, 3 + $recordCount * 2, $recordCount));
            $group4 = implode(",", array_slice($data, 3 + $recordCount * 3, $recordCount));
            $fullName = $data[3 + $recordCount * 4];
            $phoneNumber = $data[4 + $recordCount * 4];
            $email = $data[5 + $recordCount * 4];
            $postalService = $data[6 + $recordCount * 4];
            $city = $data[7 + $recordCount * 4];
            $branch = $data[8 + $recordCount * 4];
            $id = $data[9 + $recordCount * 4];

            /* Формування масиву */
            $data = [
                $login,
                $time,
                $recordCount,
                $group1,
                $group2,
                $group3,
                $group4,
                $fullName,
                $phoneNumber,
                $email,
                $postalService,
                $city,
                $branch,
                $id
            ];
            $types = "sisssssssssssi"; /* Типи даних: s - string, i - integer */
        }
        /* echo $tablename; */

        /* Заповнення бази */
        $placeholders = implode(",", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO $tablename VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        if (!empty($types)) { /* Якщо тип встановлено -> дотримуватись типу */
            $stmt->bind_param($types, ...$data);
        } else { /* В інакшому випадку усе має формат String */
            $stmt->bind_param(str_repeat("s", count($data)), ...$data);
        }
        if (!$stmt->execute()) {
            echo "Error: " . $stmt->error;
        }
    }
    fclose($file);
}

/* Виклик для кожного .csv файлу */
insertDataFromCSV($conn, "users.csv", "users");
insertDataFromCSV($conn, "userlist.csv", "userlist");
insertDataFromCSV($conn, "news.csv", "news");
insertDataFromCSV($conn, "categories.csv", "categories");
insertDataFromCSV($conn, "products.csv", "products");
insertDataFromCSV($conn, "consumer.csv", "consumer");
insertDataFromCSV($conn, "orders.csv", "orders");

$conn->close(); /* Закриваємо з'єднання */

/* Резервна директорія з RGB + White зображеннями */
$sourceDir = '../images/reserve/';
$targetDirs = [
    '../images/news/',
    '../images/products/'
];

$files = scandir($sourceDir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }
    $sourceFile = $sourceDir . $file; /* Формування шляху до файлів */
    foreach ($targetDirs as $targetDir) { /* Перевірка наявності файлів */
        $targetFile = $targetDir . $file; /* Формування шляху до файлів */
        if (file_exists($targetFile)) { /* Перевірка наявності файлу */
        } else {/* Копіювання файлу */
            copy($sourceFile, $targetFile);
        }
    }
}
/* Відправити на основну сторінку */
header("Location: ../index.php");
