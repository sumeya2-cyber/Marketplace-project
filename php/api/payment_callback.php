<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../config/Payment.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request payload.');
}

$callbackToken = isset($input['callback_token']) ? trim($input['callback_token']) : null;
if (!$callbackToken || $callbackToken !== PaymentConfig::getCallbackSecret()) {
    jsonResponse(false, 'Invalid payment callback token.');
}

$orderId = isset($input['order_id']) ? trim($input['order_id']) : null;
$paymentId = isset($input['payment_id']) ? trim($input['payment_id']) : null;
$methodId = isset($input['method_id']) ? trim($input['method_id']) : null;
$status = isset($input['payment_status']) ? trim($input['payment_status']) : 'Completed';
$transactionReference = isset($input['transaction_reference']) ? trim($input['transaction_reference']) : null;
$gatewayStatus = isset($input['gateway_status']) ? trim($input['gateway_status']) : null;
$amount = isset($input['amount']) ? round(floatval($input['amount']), 2) : null;

if (!$orderId || !$methodId || !$amount || !$paymentId) {
    jsonResponse(false, 'Order ID, payment ID, payment method, and amount are required.');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $orderStmt = $db->prepare('SELECT order_id, user_id, total_amount, status FROM orders WHERE order_id = :order_id LIMIT 1');
    $orderStmt->execute([':order_id' => $orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(false, 'Order not found.');
    }

    $methodStmt = $db->prepare('SELECT method_name FROM payment_method WHERE method_id = :method_id LIMIT 1');
    $methodStmt->execute([':method_id' => $methodId]);
    $method = $methodStmt->fetch(PDO::FETCH_ASSOC);
    $paymentType = $method ? $method['method_name'] : $methodId;

    $amountToStore = $amount;
    if ($amountToStore <= 0) {
        jsonResponse(false, 'Invalid payment amount.');
    }

    $existsStmt = $db->prepare('SELECT payment_id FROM payment WHERE payment_id = :payment_id LIMIT 1');
    $existsStmt->execute([':payment_id' => $paymentId]);

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

    $buyerEmail = null;
    $buyerName = 'Customer';
    if ($order['user_id']) {
        $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
        $userStmt->execute([':user_id' => $order['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $buyerEmail = $user['email'];
            $buyerName = trim($user['f_name'] . ' ' . $user['l_name']) ?: $buyerName;
        }
    }

    if ($buyerEmail) {
        $subject = 'Payment Confirmation for Order ' . $orderId;
        $message = "Your payment for order $orderId has been verified and recorded.\n";
        $message .= "Payment method: $paymentType\n";
        $message .= "Amount: $amountToStore\n";
        $message .= "Reference: " . ($transactionReference ?: 'N/A');
        sendBuyerNotification($db, $order['user_id'], $buyerEmail, $buyerName, $subject, $message, $orderId, 'order');
        sendAdminNotification($db, null, $subject, "Payment callback received for order $orderId. Status: $status. Reference: " . ($transactionReference ?: 'N/A'), $orderId, 'order');
    }

    jsonResponse(true, 'Payment callback processed successfully.', ['order_id' => $orderId, 'payment_id' => $paymentId, 'order_status' => $newOrderStatus]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
