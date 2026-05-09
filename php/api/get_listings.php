<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'properties';
$categoryId = isset($_GET['category']) ? $_GET['category'] : null;

$database = new Database();
$db = $database->getConnection();

$query = '';
$params = [];

switch($type) {
    case 'properties':
        $query = "SELECT p.*, u.username, pc.name as category_name 
                  FROM property_listing p
                  JOIN marketusers u ON p.user_id = u.id
                  JOIN property_categories pc ON p.category_id = pc.id
                  WHERE p.itemstatus = 'approved'";
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY p.created_at DESC";
        break;
        
    case 'products':
        $query = "SELECT p.*, u.username, pc.name as category_name 
                  FROM product_listing p
                  JOIN marketusers u ON p.user_id = u.id
                  JOIN product_categories pc ON p.category_id = pc.id
                  WHERE p.itemstatus = 'approved'";
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY p.created_at DESC";
        break;
        
    case 'contracts':
        $query = "SELECT c.*, u.username, cc.name as category_name 
                  FROM contract_listing c
                  JOIN marketusers u ON c.user_id = u.id
                  JOIN contract_categories cc ON c.category_id = cc.id
                  WHERE c.itemstatus = 'approved'";
        if ($categoryId) {
            $query .= " AND c.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY c.created_at DESC";
        break;
        
    default:
        echo json_encode([]);
        exit;
}

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($listings);
?>