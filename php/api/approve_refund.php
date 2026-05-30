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
if (!$data || empty($data['return_request_id']) || empty($data['decision'])) {
    jsonResponse(false, 'Return request ID and decision are required.');
}

$returnRequestId = trim($data['return_request_id']);
$decision = strtolower(trim($data['decision']));
$adminNote = isset($data['admin_note']) ? trim($data['admin_note']) : null;

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    $stmt = $db->prepare('SELECT rr.return_request_id, rr.order_item_id, rr.status, rr.request_type, rr.user_id, oi.order_id, oi.price, oi.quantity, o.user_id AS order_user_id 
        FROM return_request rr
        JOIN order_item oi ON rr.order_item_id = oi.order_item_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE rr.return_request_id = :return_request_id LIMIT 1');
    $stmt->bindParam(':return_request_id', $returnRequestId);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        jsonResponse(false, 'Return request not found.');
    }

    if ($decision === 'approve') {
        if ($request['status'] === 'Approved') {
            jsonResponse(false, 'This request has already been approved.');
        }

        $update = $db->prepare('UPDATE return_request SET status = :status WHERE return_request_id = :id');
        $update->execute([':status' => 'Approved', ':id' => $returnRequestId]);

        $amount = round(floatval($request['price']) * intval($request['quantity']), 2);
        $returnRecordId = 'RR-' . bin2hex(random_bytes(5));
        $paymentStmt = $db->prepare('SELECT payment_id FROM payment WHERE order_id = :order_id ORDER BY payment_time DESC LIMIT 1');
        $paymentStmt->bindParam(':order_id', $request['order_id']);
        $paymentStmt->execute();
        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        $paymentId = $payment['payment_id'] ?? null;

        $insertReturn = $db->prepare('INSERT INTO return_record (return_id, return_request_id, payment_id, status, amount, processed_at, ownership_percentage, user_id, order_id) VALUES (:return_id, :return_request_id, :payment_id, :status, :amount, NOW(), NULL, :user_id, :order_id)');
        $insertReturn->execute([
            ':return_id' => $returnRecordId,
            ':return_request_id' => $returnRequestId,
            ':payment_id' => $paymentId,
            ':status' => 'Processed',
            ':amount' => $amount,
            ':user_id' => $request['order_user_id'],
            ':order_id' => $request['order_id']
        ]);

        $refundId = 'RFND-' . bin2hex(random_bytes(5));
        $insertRefund = $db->prepare('INSERT INTO refund (refund_id, return_id, status, amount, refunded_by) VALUES (:refund_id, :return_id, :status, :amount, :refunded_by)');
        $insertRefund->execute([
            ':refund_id' => $refundId,
            ':return_id' => $returnRecordId,
            ':status' => 'Processed',
            ':amount' => $amount,
            ':refunded_by' => $_SESSION['user_id']
        ]);

        if ($paymentId) {
            $updatePayment = $db->prepare('UPDATE payment SET payment_status = :status WHERE payment_id = :payment_id');
            $updatePayment->execute([':status' => 'Refunded', ':payment_id' => $paymentId]);
        }

        $updateOrder = $db->prepare('UPDATE orders SET status = :status WHERE order_id = :order_id');
        $updateOrder->execute([':status' => 'Refunded', ':order_id' => $request['order_id']]);

        $db->commit();

        $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
        $userStmt->bindParam(':user_id', $request['order_user_id']);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : 'Customer';
        $customerEmail = $user['email'] ?? null;

        if ($customerEmail) {
            $subject = 'Refund Approved for Order ' . $request['order_id'];
            $body = buildNotificationEmail('Refund Approved', "Your refund request has been approved. Amount: $amount.");
            sendMarketplaceEmail($customerEmail, $customerName, $subject, $body);
        }

        jsonResponse(true, 'Refund approved and processed.', ['refund_id' => $refundId]);
    }

    if ($decision === 'reject') {
        $update = $db->prepare('UPDATE return_request SET status = :status WHERE return_request_id = :id');
        $update->execute([':status' => 'Rejected', ':id' => $returnRequestId]);
        $db->commit();
        jsonResponse(true, 'Return/refund request rejected.');
    }

    jsonResponse(false, 'Decision must be approve or reject.');
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>