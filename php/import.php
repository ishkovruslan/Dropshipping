<?php /* Імпорт товарів з .csv файлів, відновлення тестових зображень */
/* Код є відносно ізольованим, та використовується лише при ініціалізації/роботі з тестовими записами */
/* Оригінальні тестові записи містять 7 облікових записів (1/3/3), по 2 новини на минулий/поточний/наступний місяць відносно дати здачі курсового, 4 різноманітні категорії по 25 товарів кожна */
$servername = "localhost:3306";
$username = "root";
$password = "Kharkiv2024";
$dbname = "bacalavr";

$conn = new mysqli($servername, $username, $password, $dbname);

/* Перевірка з'єднання */
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function logAction($db, $operation, $login, $sourceIp, $sourceType, $sourceResult) {
    $sourceTime = round(microtime(true) * 1000); // Час у мс
    $columns = ['operation', 'login', 'source_ip', 'source_type', 'source_result', 'source_time'];
    $values = [$operation, $login, $sourceIp, $sourceType, $sourceResult, $sourceTime];
    $types = 'ssssss';

    /* Запис інформації */
    $columns_str = implode(", ", $columns);
    $placeholders = implode(", ", array_fill(0, count($values), '?'));
    $sql = "INSERT INTO log ($columns_str) VALUES ($placeholders)";
    
    // Використовуємо $db для підготовки запиту
    $stmt = $db->prepare($sql); 
    if ($stmt === false) {
        die("Error preparing statement: " . $db->error);
    }
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute() === false) {
        die("Error executing statement: " . $stmt->error);
    }
    $stmt->close();
}
/* Імпорт з .csv в БД */
function insertDataFromCSV($conn, $filename, $tablename)
{
    /* Перевірка наявності записів */
    $countQuery = "SELECT COUNT(*) as count FROM $tablename";
    $result = $conn->query($countQuery);
    $row = $result->fetch_assoc();
    $rowCount = $row['count'];
    /* Перевірка повторного запуску */
    if ($rowCount > 0) {
        return;
    }
    $file = fopen("../data/$filename", "r");
    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        /* Тип за замовчуванням */
        $types = "";
        /* Обробка специфікацій для категорій та продуктів */
        if ($filename == "categories.csv") {
            $data[1] = implode(",", array_slice($data, 1));
            $data = array_slice($data, 0, 2);/* Все що йде після 1 значення сприймаємо як один масив */
            $types = "ssi"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
            $data[] = NULL; /* Додаємо NULL в кінець масиву даних для стовпця id */
        } elseif ($filename == "products.csv") {
            $data[5] = implode(",", array_slice($data, 5));
            $data = array_slice($data, 0, 6);/* Все що йде після 5 значення сприймаємо як 1 масив */
            $types = "ssiissi"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
            $data[] = NULL; /* Додаємо NULL в кінець масиву даних для стовпця id */
        } elseif ($filename == "userlist.csv") {
            $types = "ssii"; /* Типи даних для кожного стовпця (s для рядка, i для цілого числа) */
        }
        /* echo $tablename; */
        logAction($conn, 'Заповнення таблиці ' . $tablename, "Owner", $_SERVER['REMOTE_ADDR'], 'WEB', "Ініціалізація");
        /* Заповнення бази */
        $placeholders = implode(",", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO $tablename VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        /* Якщо тип встановлено -> дотримуватись типу */
        if (!empty($types)) {
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

$conn->close();/* Закриваємо з'єднання */

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
    /* Формування шляху до файлів */
    $sourceFile = $sourceDir . $file;
    /* Перевірка наявності файлів */
    foreach ($targetDirs as $targetDir) {
        /* Формування шляху до файлів */
        $targetFile = $targetDir . $file;
        /* Перевірка наявності файлу */
        if (file_exists($targetFile)) {
        } else {/* Копіювання файлу */
            copy($sourceFile, $targetFile);
        }
    }
}
/* Відправити на основну сторінку */
header("Location: ../pages/mainpage.php");
?>