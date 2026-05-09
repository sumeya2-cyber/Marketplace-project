<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM product_listing WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($product);
} else {
    echo json_encode(['error' => 'Product not found']);
}
?>