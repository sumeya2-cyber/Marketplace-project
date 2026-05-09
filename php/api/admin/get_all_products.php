<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT p.*, u.username, pc.name as category_name 
          FROM product_listing p
          JOIN users u ON p.user_id = u.id
          JOIN product_categories pc ON p.category_id = pc.id
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
?>