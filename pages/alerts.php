<?php
require_once('header.php'); // Верхня частина сайту
$accessControl->checkAccess(1);

// Отримуємо рівень доступу користувача
$userLevel = $accessControl->getUserLevel($_SESSION['login']);

// Отримуємо параметри фільтрації зі змінної GET
$stateFilter = $_GET['state'] ?? null;
$searchValue = $_GET['search'] ?? '';

// Вибрані колонки для запиту
$columns = ['operation', 'description', 'date'];

// Формуємо умови для запиту
$conditions = [];
if (!empty($stateFilter)) {
    // Якщо користувач не адміністратор, приховати доступ до обмежених станів
    if ($userLevel < 2 && in_array($stateFilter, ['Обмежена кількість', 'Відсутність'])) {
        echo '<p>Недостатньо прав для перегляду вибраного стану.</p>';
        $accessControl->checkAccess(2);
    }
    $conditions['operation'] = $stateFilter; // Фільтрація за станом
}
if (!empty($searchValue)) {
    $conditions['description LIKE'] = '%' . $searchValue . '%'; // Пошук у описі
}

// Сортування за датою (найновіші зверху)
$orderBy = ['date' => 'DESC'];

// Отримуємо сповіщення з бази даних
$alertsData = $db->readWithSort('alerts', $columns, $conditions, $orderBy);

// Інтерфейс вибору стану
echo '<div class="table-selection">';
echo '<h1>Фільтрувати сповіщення за станом:</h1>';
echo '<ul>';
if ($userLevel >= 2) {
    // Адміністратору доступні всі стани
    echo '<li><a href="?state=Відсутність">Відсутність</a></li>';
    echo '<li><a href="?state=Обмежена кількість">Обмежена кількість</a></li>';
}
echo '<li><a href="?state=Додано товар">Додано товар</a></li>';
echo '<li><a href="?state=Додано категорію">Додано категорію</a></li>';
echo '</ul>';
echo '</div>';

// Перевірка: чи є сповіщення
if (!isset($_GET['state']) && !isset($_GET['search'])) {
} elseif (empty($alertsData)) {
    // Якщо немає результатів за заданими фільтрами
    echo "<p>Немає сповіщень за вибраними умовами.</p>";
} else {
    // Вивід таблиці сповіщень
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Стан</th>';
    echo '<th>Опис</th>';
    echo '<th>Дата</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($alertsData as $alert) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($alert['operation']) . '</td>';
        echo '<td>' . htmlspecialchars($alert['description']) . '</td>';
        echo '<td>' . htmlspecialchars($alert['date']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

require_once('../php/footer.php'); // Нижня частина сайту
?>
