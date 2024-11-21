<?php
require_once '../php/mysql.php';

$action = $_GET['action'] ?? null;

if ($action === 'search') {
    $search = $_GET['query'] ?? '';
    $results = $db->searchLike('consumer', ['id', 'full_name'], 'full_name', $search);
    echo json_encode($results);
} elseif ($action === 'details') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $results = $db->read('consumer', ['*'], ['id' => $id]);
        echo json_encode($results[0] ?? []);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
