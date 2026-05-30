<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['order_id']) || empty($data['status'])) {
    jsonResponse(false, 'Order ID and delivery status are required.');
}

$orderId = trim($data['order_id']);
$status = trim($data['status']);
$location = isset($data['location']) ? trim($data['location']) : null;
$notes = isset($data['notes']) ? trim($data['notes']) : null;
$deliveryProvider = isset($data['delivery_provider']) ? trim($data['delivery_provider']) : null;

$database = new Database();
$db = $database->getConnection();

try {
    $orderStmt = $db->prepare('SELECT o.order_id, o.user_id, o.delivery_status, o.tracking_number FROM orders o WHERE o.order_id = :order_id LIMIT 1');
    $orderStmt->bindParam(':order_id', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(false, 'Order not found.');
    }

    $trackingNumber = $order['tracking_number'];
    if (!$trackingNumber) {
        $trackingNumber = 'TRK-' . strtoupper(bin2hex(random_bytes(4)));
    }

    $db->beginTransaction();
    $updateOrder = $db->prepare('UPDATE orders SET delivery_status = :status, tracking_number = :tracking_number, delivery_provider = COALESCE(:delivery_provider, delivery_provider) WHERE order_id = :order_id');
    $updateOrder->execute([
        ':status' => $status,
        ':tracking_number' => $trackingNumber,
        ':delivery_provider' => $deliveryProvider,
        ':order_id' => $orderId
    ]);

    $historyInsert = $db->prepare('INSERT INTO delivery_status_history (order_id, status, location, notes, created_at) VALUES (:order_id, :status, :location, :notes, NOW())');
    $historyInsert->execute([
        ':order_id' => $orderId,
        ':status' => $status,
        ':location' => $location,
        ':notes' => $notes
    ]);

    if (strtolower($status) === 'delivered') {
        $orderStatus = 'Delivered';
        $statusUpdate = $db->prepare('UPDATE orders SET status = :status WHERE order_id = :order_id');
        $statusUpdate->execute([':status' => $orderStatus, ':order_id' => $orderId]);
    }

    $db->commit();

    $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
    $userStmt->bindParam(':user_id', $order['user_id']);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : 'Customer';
    $customerEmail = $user['email'] ?? null;

    if ($customerEmail) {
        $subject = 'Delivery Update for Order ' . $orderId;
        $body = buildNotificationEmail('Delivery Update', "Your order $orderId has a new delivery status: $status. Tracking number: $trackingNumber.");
        sendMarketplaceEmail($customerEmail, $customerName, $subject, $body);
    }

    jsonResponse(true, 'Delivery status updated successfully.', ['tracking_number' => $trackingNumber]);
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>