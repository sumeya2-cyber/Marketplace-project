<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : null;
if (!$orderId) {
    jsonResponse(false, 'Order ID is required.');
}

$database = new Database();
$db = $database->getConnection();

try {
    $ownerStmt = $db->prepare('SELECT user_id FROM orders WHERE order_id = :order_id LIMIT 1');
    $ownerStmt->bindParam(':order_id', $orderId);
    $ownerStmt->execute();
    $order = $ownerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['user_id'] !== $_SESSION['user_id']) {
        jsonResponse(false, 'Order not found or you are not authorized to view its shipping history.');
    }

    $stmt = $db->prepare('SELECT status, location, notes, created_at FROM delivery_status_history WHERE order_id = :order_id ORDER BY created_at ASC');
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Delivery history retrieved.', $history);
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>