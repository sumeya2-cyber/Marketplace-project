<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
$db = (new Database())->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$listing_id = $input['listing_id'] ?? null;
if (!$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Missing listing_id']);
    exit;
}

try {
    // Check if it's a product listing or service request
    $checkProduct = $db->prepare("SELECT listing_id, product_id FROM product_listing WHERE listing_id = :id");
    $checkProduct->execute([':id' => $listing_id]);
    $productResult = $checkProduct->fetch(PDO::FETCH_ASSOC);
    
    if ($productResult) {
        // It's a product listing - delete it
        $product_id = $productResult['product_id'];
        // delete images
        $stmtImg = $db->prepare("SELECT image_url FROM product_image WHERE product_id = :pid");
        $stmtImg->execute([':pid' => $product_id]);
        $imgs = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
        foreach ($imgs as $img) {
            $path = __DIR__ . '/../../' . $img['image_url'];
            if (file_exists($path)) @unlink($path);
        }
        $db->beginTransaction();
        $delListing = $db->prepare("DELETE FROM product_listing WHERE listing_id = :lid");
        $delListing->execute([':lid' => $listing_id]);
        $delImgs = $db->prepare("DELETE FROM product_image WHERE product_id = :pid");
        $delImgs->execute([':pid' => $product_id]);
        $delProduct = $db->prepare("DELETE FROM product WHERE product_id = :pid");
        $delProduct->execute([':pid' => $product_id]);
        $db->commit();
    } else {
        // It's a service request - delete it
        $db->beginTransaction();
        $delService = $db->prepare("DELETE FROM service_request WHERE request_id = :rid");
        $delService->execute([':rid' => $listing_id]);
        $db->commit();
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
