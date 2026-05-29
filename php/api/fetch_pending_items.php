<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Only allow admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
$db = (new Database())->getConnection();

try {
    $query = "SELECT pl.listing_id, pl.product_id, p.product_name, pl.price, pl.quantity, p.category_id,
                         '' AS location, '' AS brand, pl.status, pl.date_posted, 'product' AS type
              FROM product_listing pl
              JOIN product p ON pl.product_id = p.product_id
              WHERE pl.status = 'Pending'
              UNION
              SELECT sr.request_id AS listing_id, sr.request_id AS product_id, 'Service Request' AS product_name, sr.budget AS price, 1 AS quantity, sr.category_id,
                         '' AS location, '' AS brand, sr.status, sr.deadline AS date_posted, 'service' AS type
              FROM service_request sr
              WHERE sr.status = 'Pending'
              ORDER BY date_posted DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
