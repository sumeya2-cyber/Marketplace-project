<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['order_id'])) {
    jsonResponse(false, 'Order ID is required.');
}

$orderId = trim($input['order_id']);
$guestToken = isset($input['guest_token']) ? trim($input['guest_token']) : null;
$paymentProvider = isset($input['payment_provider']) ? trim($input['payment_provider']) : null;
$paymentMethodId = isset($input['payment_method_id']) ? trim($input['payment_method_id']) : null;
$providerLabel = $paymentMethodId ?: $paymentProvider;

if (!$providerLabel) {
    jsonResponse(false, 'Payment provider is required.');
}

$database = new Database();
$db = $database->getConnection();

try {
    $orderStmt = $db->prepare('SELECT order_id, user_id, guest_token, status FROM orders WHERE order_id = :order_id LIMIT 1');
    $orderStmt->bindParam(':order_id', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(false, 'Order not found.');
    }

    if ($order['user_id']) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $order['user_id']) {
            jsonResponse(false, 'You are not authorized to pay this order.');
        }
    } else {
        if (!$guestToken || $guestToken !== $order['guest_token']) {
            jsonResponse(false, 'Invalid guest session for this order.');
        }
    }

    if (in_array(strtolower($order['status']), ['paid', 'refunded', 'completed'])) {
        jsonResponse(false, 'This order has already been finalized.');
    }

    $update = $db->prepare('UPDATE orders SET status = :status WHERE order_id = :order_id');
    $update->execute([':status' => 'Paid', ':order_id' => $orderId]);

    $userEmail = null;
    $userName = 'Customer';
    if ($order['user_id']) {
        $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
        $userStmt->bindParam(':user_id', $order['user_id']);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userEmail = $user['email'];
            $userName = trim($user['f_name'] . ' ' . $user['l_name']) ?: $userName;
        }
    } else {
        $orderStmt = $db->prepare('SELECT guest_name, guest_email FROM orders WHERE order_id = :order_id LIMIT 1');
        $orderStmt->bindParam(':order_id', $orderId);
        $orderStmt->execute();
        $guest = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if ($guest) {
            $userEmail = $guest['guest_email'];
            $userName = $guest['guest_name'] ?: $userName;
        }
    }

    if ($userEmail) {
        $subject = 'Payment Completed for Order ' . $orderId;
        $message = "Your payment for order $orderId has been successfully completed using {$providerLabel}.";
        sendBuyerNotification($db, $order['user_id'], $userEmail, $userName, $subject, $message, $orderId, 'order');
        sendAdminNotification($db, null, $subject, "Order $orderId payment completed using {$providerLabel}.", $orderId, 'order');
    }

    jsonResponse(true, 'Payment completed successfully.', ['order_id' => $orderId, 'status' => 'Paid', 'provider' => $providerLabel]);
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>