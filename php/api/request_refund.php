<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['order_item_id']) || empty($data['reason'])) {
    jsonResponse(false, 'Order item ID and reason are required.');
}

$orderItemId = trim($data['order_item_id']);
$reason = trim($data['reason']);
$requestType = isset($data['request_type']) && strtolower($data['request_type']) === 'refund' ? 'Refund' : 'Return';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guestToken = isset($data['guest_token']) ? trim($data['guest_token']) : null;
$ownerId = $userId ?: $guestToken;
if (!$ownerId) {
    jsonResponse(false, 'Please login or provide the guest token to submit a refund request.');
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare('SELECT oi.order_id, o.user_id FROM order_item oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.order_item_id = :item_id LIMIT 1');
    $stmt->bindParam(':item_id', $orderItemId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['user_id'] !== $ownerId) {
        jsonResponse(false, 'Order item not found or does not belong to the current user or guest session.');
    }

    $exists = $db->prepare('SELECT return_request_id FROM return_request WHERE order_item_id = :item_id LIMIT 1');
    $exists->bindParam(':item_id', $orderItemId);
    $exists->execute();
    if ($exists->rowCount() > 0) {
        jsonResponse(false, 'A refund or return request already exists for this order item.');
    }

    $returnRequestId = 'RTR-' . bin2hex(random_bytes(5));
    $insert = $db->prepare('INSERT INTO return_request (return_request_id, order_item_id, reason, requested_at, status, user_id, request_type) VALUES (:id, :item_id, :reason, NOW(), :status, :user_id, :request_type)');
    $insert->execute([
        ':id' => $returnRequestId,
        ':item_id' => $orderItemId,
        ':reason' => $reason,
        ':status' => 'Pending',
        ':user_id' => $ownerId,
        ':request_type' => $requestType
    ]);

    $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
    $userStmt->bindParam(':user_id', $userId);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : 'Customer';
    $customerEmail = $user['email'] ?? null;

    if ($customerEmail) {
        $subject = 'Refund/Return Request Received';
        $body = buildNotificationEmail('Request Submitted', "Your $requestType request has been received. Request ID: $returnRequestId.");
        sendMarketplaceEmail($customerEmail, $customerName, $subject, $body);
    }

    jsonResponse(true, 'Refund/return request submitted successfully.', ['return_request_id' => $returnRequestId]);
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>