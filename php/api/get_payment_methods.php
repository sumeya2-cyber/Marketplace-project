<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$defaultMethods = [
    ['method_id' => 'telebirr', 'method_name' => 'Telebirr', 'provider_name' => 'Telebirr'],
    ['method_id' => 'cbe', 'method_name' => 'CBE Birr', 'provider_name' => 'CBE Birr'],
    ['method_id' => 'chapa', 'method_name' => 'Chapa', 'provider_name' => 'Chapa'],
    ['method_id' => 'paypal', 'method_name' => 'PayPal', 'provider_name' => 'PayPal'],
    ['method_id' => 'stripe', 'method_name' => 'Stripe', 'provider_name' => 'Stripe'],
    ['method_id' => 'bank_transfer', 'method_name' => 'Bank Transfer', 'provider_name' => 'Bank Transfer']
];

try {
    $stmt = $db->prepare('SELECT method_id, method_name, provider_name FROM payment_method');
    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$methods || count($methods) === 0) {
        $methods = $defaultMethods;
    }

    echo json_encode(['success' => true, 'methods' => $methods]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to load payment methods: ' . $e->getMessage(), 'methods' => $defaultMethods]);
}
