<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['order_id']) || empty($input['payment_status']) || empty($input['method_id'])) {
    jsonResponse(false, 'Order ID, payment status, and payment method are required.');
}

$orderId = trim($input['order_id']);
$status = trim($input['payment_status']);
$methodId = trim($input['method_id']);
$paymentId = isset($input['payment_id']) ? trim($input['payment_id']) : null;
$transactionReference = isset($input['transaction_reference']) ? trim($input['transaction_reference']) : null;
$gatewayStatus = isset($input['gateway_status']) ? trim($input['gateway_status']) : null;
$paidAmount = isset($input['paid_amount']) ? round(floatval($input['paid_amount']), 2) : null;

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    $orderStmt = $db->prepare('SELECT user_id, total_amount, status FROM orders WHERE order_id = :order_id LIMIT 1');
    $orderStmt->bindParam(':order_id', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(false, 'Order not found.');
    }

    if (!$paymentId) {
        $paymentId = 'PAY-' . bin2hex(random_bytes(6));
    }

    $methodStmt = $db->prepare('SELECT method_name FROM payment_method WHERE method_id = :method_id LIMIT 1');
    $methodStmt->bindParam(':method_id', $methodId);
    $methodStmt->execute();
    $method = $methodStmt->fetch(PDO::FETCH_ASSOC);
    if (!$method) {
        jsonResponse(false, 'Invalid payment method.');
    }

    $paymentType = $method['method_name'];
    $amountToStore = $paidAmount !== null ? $paidAmount : floatval($order['total_amount']);

    $existsStmt = $db->prepare('SELECT payment_id FROM payment WHERE payment_id = :payment_id LIMIT 1');
    $existsStmt->bindParam(':payment_id', $paymentId);
    $existsStmt->execute();

    if ($existsStmt->rowCount() > 0) {
        $update = $db->prepare('UPDATE payment SET payment_status = :status, payment_type = :payment_type, transaction_reference = :transaction_reference, gateway_status = :gateway_status, paid_amount = :paid_amount, method_id = :method_id, payment_time = NOW() WHERE payment_id = :payment_id');
        $update->execute([
            ':status' => $status,
            ':payment_type' => $paymentType,
            ':transaction_reference' => $transactionReference,
            ':gateway_status' => $gatewayStatus,
            ':paid_amount' => $amountToStore,
            ':method_id' => $methodId,
            ':payment_id' => $paymentId
        ]);
    } else {
        $insert = $db->prepare('INSERT INTO payment (payment_id, user_id, order_id, payment_status, payment_type, payment_time, method_id, transaction_reference, paid_amount) VALUES (:payment_id, :user_id, :order_id, :status, :payment_type, NOW(), :method_id, :transaction_reference, :paid_amount)');
        $insert->execute([
            ':payment_id' => $paymentId,
            ':user_id' => $order['user_id'],
            ':order_id' => $orderId,
            ':status' => $status,
            ':payment_type' => $paymentType,
            ':method_id' => $methodId,
            ':transaction_reference' => $transactionReference,
            ':paid_amount' => $amountToStore
        ]);
    }

    $newOrderStatus = $order['status'];
    if (in_array(strtolower($status), ['completed', 'paid'])) {
        $newOrderStatus = 'Paid';
    } elseif (strtolower($status) === 'refunded') {
        $newOrderStatus = 'Refunded';
    } elseif (strtolower($status) === 'failed') {
        $newOrderStatus = 'Pending';
    }

    $orderUpdate = $db->prepare('UPDATE orders SET status = :status WHERE order_id = :order_id');
    $orderUpdate->execute([':status' => $newOrderStatus, ':order_id' => $orderId]);

    $db->commit();

    $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
    $userStmt->bindParam(':user_id', $order['user_id']);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : 'Customer';
    $customerEmail = $user['email'] ?? null;

    if ($customerEmail) {
        $subject = 'Payment Status Updated for Order ' . $orderId;
        $body = buildNotificationEmail('Payment Update', "Your payment status for order $orderId is now: $status.");
        sendMarketplaceEmail($customerEmail, $customerName, $subject, $body);
    }

    jsonResponse(true, 'Payment processed successfully.', ['payment_id' => $paymentId, 'order_status' => $newOrderStatus]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>