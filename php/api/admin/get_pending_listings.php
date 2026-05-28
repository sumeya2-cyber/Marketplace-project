<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT listing_id, product_id, price, quantity, listing_type, date_posted, status 
              FROM product_listing 
              WHERE status = 'Pending' 
              ORDER BY date_posted DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $pending]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>