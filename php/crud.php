<?php
require_once ('mysql.php'); // Підключення до бази
/* Модуль керування */
class User
{/* Клас взаємодія з користувачами */
    private $login;
    private $role;
    private $db;

    public function __construct($login, $role, $db)
    {
        $this->login = $login;
        $this->role = $role;
        $this->db = $db;
    }

    public function changeRole($newRole) {
        $data = ['role' => $newRole];
        $conditions = ['login' => $this->login];
        $this->db->update('userlist', $data, $conditions);
        $this->role = $newRole;
        logAction($this->db, 'Зміна ролі', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' змінив роль користувачу ' . $conditions['login'] . ' на ' . $newRole);
    }    

    public function deleteUser() {
        $this->db->remove('userlist', ['login'], [$this->login]);
        $this->db->remove('users', ['login'], [$this->login]);
        logAction($this->db, 'Видалення користувача', $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' видалив користувача ' . $this->login);
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getRole()
    {
        return $this->role;
    }
}

class UserList
{
    private $users = [];
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function loadUsersFromDB()
    {/* Завантаження користувачів з БД */
        $this->users = []; // Очищуємо список користувачів перед завантаженням
        $result = $this->db->read('userlist', ['login', 'role']);
        foreach ($result as $row) {
            $this->users[] = new User($row['login'], $row['role'], $this->db);
        }
    }

    public function getUserByLogin($login)
    {/* Пошук користувача за логіном */
        foreach ($this->users as $user) {
            if ($user->getLogin() == $login) {
                return $user;
            }
        }
        return null;
    }

    public function getUsers()
    {
        return $this->users;
    }
}

class News
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($title, $image, $description, $startDate, $endDate)
    {/* Створити новину */
        $columns = ['news_title', 'uploadPath', 'news_description', 'start_date', 'end_date'];
        $values = [$title, $image, $description, $startDate, $endDate];
        $types = 'sssss';
        $this->db->write('news', $columns, $values, $types);
    }
}

class Category
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($name, $specifications)
    {/* Створити категорію */
        $columns = ['category_name', 'specifications'];
        $values = [$name, implode(",", $specifications)];
        $types = 'ss';
        $this->db->write('categories', $columns, $values, $types);
    }
}

class Product
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($category, $name, $count, $price, $image, $characteristics)
    {/* Створити товар */
        $columns = ['category', 'product_name', 'count', 'price', 'uploadPath', 'characteristics'];
        if (!is_array($characteristics)) {
            $characteristics = [$characteristics];
        }
        if (is_array($image)) {
            $image = implode(",", $image);
        }
        $values = [$category, $name, $count, $price, $image, implode(",", $characteristics)];
        $types = 'ssiiss';
        $this->db->write('products', $columns, $values, $types);
    }

    public function getCategories() {
        return $this->db->read('categories', ['category_name', 'specifications']);
    }
}

function updateData($table, $data, $conditions, $db) {
    try {
        $db->update($table, $data, $conditions);
        // Формування рядків для логування
        $dataString = implode(', ', array_map(function($key, $value) {
            return "$key: $value";
        }, array_keys($data), $data));
        $conditionsString = implode(', ', array_map(function($key, $value) {
            return "$key: $value";
        }, array_keys($conditions), $conditions));
        logAction($db, "Оновлення $table", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login']. ' оновив таблицю ' . $table . ' змінивши значення ' . $dataString . ', ' . $conditionsString);
        header("Location: ../pages/management.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        echo "Помилка: " . $e->getMessage();
    }
}

function deleteEntity($entity, $id, $db)
{/* Видалення категорії */
    $conditions = ['id' => $id];
    deleteEntityWithImages($entity, $conditions, $db);
    logAction($db, "Оновлення $entity", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login']. ' оновив таблицю ' . $entity . ' видаливши запис ' . $id);
    header("Location: ../pages/management.php"); // Перенаправлення назад до списку
    exit();
}

function deleteEntityWithImages($entity, $conditions, $db)
{/* Видалення разом з зображенням */
    $imagePathPrefix = "../images/$entity/";
    $imageColumn = 'uploadPath';
    if ($entity == 'categories') {
        $category_result = $db->read('categories', ['category_name'], $conditions);
        if (!empty($category_result)) {
            $category_name = $category_result[0]['category_name'];
            $productsInCategory = $db->read('products', ['uploadPath'], ['category' => $category_name]);
            foreach ($productsInCategory as $product) {
                deleteFile("../images/products/{$product['uploadPath']}");
            }
            $db->remove('products', ['category'], [$category_name]);
        }
    }
    $result = $db->read($entity, [$imageColumn], $conditions);
    if (!empty($result)) {
        deleteFile($imagePathPrefix . $result[0][$imageColumn]);
    }
    $db->remove($entity, array_keys($conditions), array_values($conditions));
}

function deleteFile($filePath)
{/* Видалення файлу */
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function handlePostRequest($db)
{/* Обробка запитів */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_POST['login'])) {
            handleUserPostRequest($db);
        } elseif (isset($_POST['entity']) && isset($_POST['id'])) {
            handleEntityPostRequest($db);
        } elseif (isset($_POST['create_news']) || isset($_POST['create_category']) || isset($_POST['create_product'])) {
            handleCreatePostRequest($db);
        }
    }
}


function handleUserPostRequest($db)
{/* POST запит для взаємодії з користувачем */
    $login = $_POST['login'];
    $userList = new UserList($db);
    $userList->loadUsersFromDB();
    $user = $userList->getUserByLogin($login);
    if ($user) {
        $remoteAccess = new RemoteAccess($db);
        if (isset($_POST['change_role']) && $_POST['new_role'] !== 'delete' && $_POST['new_role'] !== 'changekey') {
            $new_role = $_POST['new_role'];
            $user->changeRole($new_role);
            if ($new_role === 'user') {
                $remoteAccess->setUniqueKey($login, 0);
            }
        }
        elseif ($_POST['new_role'] === 'changekey') {
                $remoteAccess->setUniqueKey($login, 0);
        }
        elseif (isset($_POST['delete_user'])) {
            $user->deleteUser();
            $userList->loadUsersFromDB();
        }
    }
}

function handleEntityPostRequest($db)
{/* POST запит для взаємодії з новиною/категорією/товаром */
    $entity = $_POST['entity'];
    $id = $_POST['id'];
    if (isset($_POST['delete'])) {
        deleteEntity($entity, $id, $db);
    } else {
        $data = [];
        $uploadDir = "../images/$entity/";
        $fileKey = 'uploadPath';
        if (!empty($_FILES[$fileKey]['name'])) {
            // Видалення старого зображення перед завантаженням нового
            $conditions = ['id' => $id];
            $result = $db->read($entity, ['uploadPath'], $conditions);
            if (!empty($result)) {
                $oldImagePath = $uploadDir . $result[0]['uploadPath'];
                deleteFile($oldImagePath);
            }
            $uniqueFileName = uploadFile($fileKey, $uploadDir);
            if ($uniqueFileName) {
                $data['uploadPath'] = $uniqueFileName;
            } else {
                echo "Помилка при завантаженні файлу.";
                exit();
            }
        }
        populateDataArray($entity, $data);
        updateData($entity, $data, ['id' => $id], $db);
    }
}


function handleCreatePostRequest($db)
{/* POST запит створення новини/категорії/товару */
    if (isset($_POST['create_news'])) {
        handleCreateNewsRequest($db);
    } elseif (isset($_POST['create_category'])) {
        handleCreateCategoryRequest($db);
    } elseif (isset($_POST['create_product'])) {
        handleCreateProductRequest($db);
    }
}

function handleCreateNewsRequest($db) {
    $news = new News($db);
    $name = $_POST['news_title'];
    $description = $_POST['news_description'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $imageFileName = uploadFile('uploadPath', "../images/news/");
    $news->create($name, $imageFileName, $description, $start, $end);
    logAction($db, "Створено новину", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login']. ' створив новину ' . $name . ' з описом ' . $description . ' яка триває з ' . $start . ' до ' . $end);
    header("Location: ../pages/newnews.php");
}

function handleCreateCategoryRequest($db)
{/* POST запит створення категорії */
    $category = new Category($db);
    $name = $_POST['category_name'];
    $specifications = explode(",", $_POST['specifications']);
    $category->create($name, $specifications);
    $dataSpecifications = implode(', ', array_map(function($key, $value) {
        return "$key: $value";
    }, array_keys($specifications), $specifications));
    logAction($db, "Створено категорію", $_SESSION['login'], $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login']. ' створив категорію ' . $name . ' з наступними специфікаціями ' . $dataSpecifications);
    header("Location: ../pages/newcategory.php");
}

function handleCreateProductRequest($db)
{
    /* POST запит створення товару */
    $product = new Product($db);
    $name = $_POST['name'];
    $count = $_POST['count'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    
    // Перевірка, чи характеристики є масивом
    if (isset($_POST['characteristics']) && is_array($_POST['characteristics'])) {
        $characteristics = implode(',', $_POST['characteristics']); // Об'єднуємо масив в рядок
    } else {
        $characteristics = $_POST['characteristics'] ?? ''; // Якщо не масив, просто беремо значення
    }
    
    $imageFileName = uploadFile('uploadPath', "../images/products/");
    
    // Форматування характеристик
    $characteristicsArray = explode(',', $characteristics);
    $specificationsResult = $db->read('categories', ['specifications'], ['category_name' => $category]);
    $specifications = explode(',', $specificationsResult[0]["specifications"]);
    
    $formattedCharacteristics = [];
    foreach ($characteristicsArray as $key => $value) {
        if ($value !== "-" && $value !== "") {
            $formattedCharacteristics[] = htmlspecialchars($specifications[$key]) . ": " . htmlspecialchars($value);
        }
    }
    
    // Об'єднуємо характеристики в один рядок
    $characteristicsString = implode(", ", $formattedCharacteristics);
    
    $product->create($category, $name, $count, $price, $imageFileName, $characteristics);
    
    logAction($db, "Створено товар", $_SESSION['login'] ?? 'Administrator', $_SERVER['REMOTE_ADDR'], 'WEB', $_SESSION['login'] . ' створив в категорії ' . $category . ' товар ' . $name . ' в кількості ' . $count . ' який має вартість ' . $price . " та має наступні характеристики: " . $characteristicsString);
    
    header("Location: ../pages/newproduct.php");
}


function uploadFile($fileKey, $uploadDir)
{/* Завантаження файлу */
    $fileExtension = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $uniqueFileName = uniqid() . '.' . $fileExtension;
    $targetFilePath = $uploadDir . $uniqueFileName;
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetFilePath)) {
        return $uniqueFileName;
    }
    return false;
}

function populateDataArray($entity, &$data)
{
    switch ($entity) {
        case 'news':
            $data['news_title'] = $_POST['news_title'];
            $data['news_description'] = $_POST['news_description'];
            $data['start_date'] = $_POST['start_date'];
            $data['end_date'] = $_POST['end_date'];
            break;
        case 'products':
            $data['category'] = $_POST['category'];
            $data['product_name'] = $_POST['product_name'];
            $data['count'] = $_POST['count'];
            $data['price'] = $_POST['price'];
            $data['characteristics'] = $_POST['characteristics'];
            break;
        case 'categories':
            $data['category_name'] = $_POST['category_name'];
            $data['specifications'] = $_POST['category_specifications'];
            break;
        default:
            echo "Невідома сутність: " . htmlspecialchars($entity);
            exit();
    }
}
/* Універсальний обробник POST запитів */
handlePostRequest($db);

function countAccessibleProductsByCategory($categoryName, $productsData)
{/* Лічильник товарів */
    $count = 0;
    foreach ($productsData as $product) {
        if ($product['category'] == $categoryName) {
            $count++;
        }
    }
    return $count;
}
?>