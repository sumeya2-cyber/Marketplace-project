<?php
// Safer JSON response wrapper to capture any unexpected output (warnings/notices)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

ob_start();
require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guestToken = isset($_GET['guest_token']) ? trim($_GET['guest_token']) : null;

if (!$userId && empty($guestToken)) {
    $payload = ['success' => false, 'message' => 'Please log in or provide a guest token.'];
    $raw = ob_get_clean();
    if (!empty($raw)) {
        $payload['raw_output'] = $raw;
    }
    echo json_encode($payload);
    exit;
}

$ownerId = $userId ? $userId : $guestToken;

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare('SELECT o.order_id, o.status, o.total_amount, o.order_date, oi.order_item_id, oi.product_id, oi.property_id, oi.price, oi.quantity FROM orders o JOIN order_item oi ON o.order_id = oi.order_id WHERE o.user_id = :user_id ORDER BY o.order_date DESC');
    $stmt->bindParam(':user_id', $ownerId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $payload = ['success' => true, 'orders' => $rows];
} catch (PDOException $e) {
    $payload = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
}

$raw = ob_get_clean();
if (!empty($raw)) {
    // Include any unexpected HTML/text output to help debugging
    $payload['raw_output'] = $raw;
}

echo json_encode($payload);
?>