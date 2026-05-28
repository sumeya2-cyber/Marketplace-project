<?php
// php/api/manage_categories.php
header('Content-Type: application/json');
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? ''; // e.g., 'update' or 'delete'

// Use the same switch logic as get_categories.php to identify the table
// ... (your switch logic here) ...

if ($action === 'update') {
    $query = "UPDATE $table SET $nameColumn = :name WHERE $idColumn = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['name' => $input['name'], 'id' => $input['id']]);
    echo json_encode(['success' => true]);
}
?>