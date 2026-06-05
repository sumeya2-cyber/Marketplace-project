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
if (!$input || empty($input['listing_type']) || empty($input['item_id'])) {
    jsonResponse(false, 'Listing type and item ID are required.');
}

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guestToken = isset($input['guest_token']) ? trim($input['guest_token']) : null;
$guestName = isset($input['guest_name']) ? trim($input['guest_name']) : null;
$guestEmail = isset($input['guest_email']) ? trim($input['guest_email']) : null;
$listingType = strtolower(trim($input['listing_type']));
if ($listingType === 'properties') {
    $listingType = 'property';
} elseif ($listingType === 'products') {
    $listingType = 'product';
}
$itemId = trim($input['item_id']);
$quantity = isset($input['quantity']) ? max(1, intval($input['quantity'])) : 1;
$paymentMethodId = isset($input['payment_method_id']) ? trim($input['payment_method_id']) : null;

if (!$userId && !$guestToken) {
    $guestToken = 'GUEST-' . bin2hex(random_bytes(6));
}
if (!$userId && (!$guestName || !$guestEmail)) {
    jsonResponse(false, 'Guest name and email are required for checkout.');
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    if ($listingType === 'product') {
        $stmt = $db->prepare("SELECT pl.price, pr.product_name FROM product_listing pl
            JOIN product pr ON pl.product_id = pr.product_id
            WHERE pr.product_id = :id AND LOWER(pl.status) = 'approved'
            ORDER BY pl.date_posted DESC LIMIT 1");
    } elseif ($listingType === 'property') {
        $stmt = $db->prepare("SELECT pl.price, CONCAT_WS(', ', p.address, p.city) AS product_name FROM property_listing pl
            JOIN property p ON pl.property_id = p.property_id
            WHERE p.property_id = :id AND LOWER(pl.status) = 'approved'
            ORDER BY pl.date_posted DESC LIMIT 1");
    } else {
        jsonResponse(false, 'Unsupported listing type. Only product and property orders can be placed through this endpoint.');
    }

    $stmt->bindParam(':id', $itemId);
    $stmt->execute();
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        jsonResponse(false, 'Listing not found or not approved for order placement.');
    }

    $price = floatval($listing['price']);
    $totalAmount = round($price * $quantity, 2);
    $orderId = 'ORD-' . bin2hex(random_bytes(6));
    $orderItemId = 'ITEM-' . bin2hex(random_bytes(6));

    // For guest orders, store NULL for user_id and save guest info in guest_* columns
    $insertOrder = $db->prepare('INSERT INTO orders (order_id, user_id, status, total_amount, order_date, due_date, guest_token, guest_name, guest_email) VALUES (:order_id, :user_id, :status, :total_amount, NOW(), NULL, :guest_token, :guest_name, :guest_email)');
    $insertOrder->execute([
        ':order_id' => $orderId,
        ':user_id' => $userId,  // NULL for guests, user ID for registered users
        ':status' => 'Pending',
        ':total_amount' => $totalAmount,
        ':guest_token' => $userId ? null : $guestToken,
        ':guest_name' => $userId ? null : $guestName,
        ':guest_email' => $userId ? null : $guestEmail
    ]);

    if ($listingType === 'product') {
        $insertItem = $db->prepare('INSERT INTO order_item (order_item_id, order_id, product_id, price, quantity) VALUES (:order_item_id, :order_id, :product_id, :price, :quantity)');
        $insertItem->execute([
            ':order_item_id' => $orderItemId,
            ':order_id' => $orderId,
            ':product_id' => $itemId,
            ':price' => $price,
            ':quantity' => $quantity
        ]);
    } else {
        $insertItem = $db->prepare('INSERT INTO order_item (order_item_id, order_id, property_id, price, quantity) VALUES (:order_item_id, :order_id, :property_id, :price, :quantity)');
        $insertItem->execute([
            ':order_item_id' => $orderItemId,
            ':order_id' => $orderId,
            ':property_id' => $itemId,
            ':price' => $price,
            ':quantity' => $quantity
        ]);
    }

    $paymentId = null;
    if ($paymentMethodId) {
        $methodStmt = $db->prepare('SELECT method_name FROM payment_method WHERE method_id = :method_id LIMIT 1');
        $methodStmt->bindParam(':method_id', $paymentMethodId);
        $methodStmt->execute();
        $method = $methodStmt->fetch(PDO::FETCH_ASSOC);

        if (!$method) {
            $db->rollBack();
            jsonResponse(false, 'Selected payment method is invalid.');
        }

        $paymentId = 'PAY-' . bin2hex(random_bytes(6));
        $paymentType = $method['method_name'];
        $insertPayment = $db->prepare('INSERT INTO payment (payment_id, user_id, order_id, payment_status, payment_type, payment_time, method_id, paid_amount) VALUES (:payment_id, :user_id, :order_id, :payment_status, :payment_type, NOW(), :method_id, :paid_amount)');
        $insertPayment->execute([
            ':payment_id' => $paymentId,
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':payment_status' => 'Pending',
            ':payment_type' => $paymentType,
            ':method_id' => $paymentMethodId,
            ':paid_amount' => $totalAmount
        ]);
    }

    $db->commit();

    $customerName = 'Customer';
    $customerEmail = null;
    if ($userId) {
        $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : $customerName;
        $customerEmail = $user['email'] ?? null;
    } else {
        $customerName = $guestName ?: $customerName;
        $customerEmail = $guestEmail;
    }

    if ($customerEmail) {
        $subject = 'Order Received: ' . $orderId;
        $message = "Thank you for your order.\nOrder ID: $orderId\nItem: " . $listing['product_name'] . "\nAmount: $totalAmount";
        sendBuyerNotification($db, $order['user_id'], $customerEmail, $customerName, $subject, $message, $orderId, 'order');
    }

    $redirectUrl = null;
    if ($paymentId && $paymentMethodId) {
        $redirectUrl = 'payment_gateway_redirect.php?order_id=' . urlencode($orderId) . '&payment_id=' . urlencode($paymentId) . '&method_id=' . urlencode($paymentMethodId);
    }

    jsonResponse(true, 'Order created successfully.', ['order_id' => $orderId, 'payment_id' => $paymentId, 'redirect_url' => $redirectUrl]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>