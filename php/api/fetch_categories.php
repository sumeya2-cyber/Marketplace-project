<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'properties';

$map = [
    'properties' => ['table' => 'property_category', 'id' => 'category_id', 'name' => 'category_name'],
    'products'   => ['table' => 'product_category', 'id' => 'category_id', 'name' => 'category_name'],
    'services'   => ['table' => 'service_category', 'id' => 'category_id', 'name' => 'category_name'],
];

if (!isset($map[$type])) {
    echo json_encode(['success' => false, 'message' => 'Invalid category type.']);
    exit;
}

$info = $map[$type];
$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT {$info['id']} AS id, {$info['name']} AS name FROM {$info['table']} ORDER BY {$info['name']} ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($categories);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch categories: ' . $e->getMessage()]);
}
?>