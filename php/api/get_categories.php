<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'properties';

$database = new Database();
$db = $database->getConnection();

$table = '';
switch($type) {
    case 'properties':
        $table = 'property_categories';
        break;
    case 'products':
        $table = 'product_categories';
        break;
    case 'contracts':
        $table = 'contract_categories';
        break;
    default:
        echo json_encode([]);
        exit;
}

$query = "SELECT id, name, description FROM $table WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($categories);
?>