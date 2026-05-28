<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

// Capture the raw JSON sent from the dashboard
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->action)) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Use listing_id to target the specific row
    $query = "UPDATE product_listing SET status = :status WHERE listing_id = :id";
    $stmt = $db->prepare($query);
    
    // Execute the update
    $stmt->execute([':status' => $data->action, ':id' => $data->id]);

    echo json_encode(['success' => true, 'message' => 'Status updated to ' . $data->action]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>