<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
$db = (new Database())->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$listing_id = $input['listing_id'] ?? null;
if (!$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Missing listing_id']);
    exit;
}

try {
    // Check if it's a product listing or service request
    $checkProduct = $db->prepare("SELECT listing_id FROM product_listing WHERE listing_id = :id");
    $checkProduct->execute([':id' => $listing_id]);
    
    if ($checkProduct->rowCount() > 0) {
        // It's a product listing
        $stmt = $db->prepare("UPDATE product_listing SET status = 'Approved' WHERE listing_id = :lid");
        $stmt->execute([':lid' => $listing_id]);
    } else {
        // It's a service request
        $stmt = $db->prepare("UPDATE service_request SET status = 'Approved' WHERE request_id = :rid");
        $stmt->execute([':rid' => $listing_id]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
