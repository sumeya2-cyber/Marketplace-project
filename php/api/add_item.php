<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/Database.php';

$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : 'products';

$database = new Database();
$db = $database->getConnection();

try {
    if ($type === 'products') {
        // required fields
        $title = trim($_POST['title'] ?? '');
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $category_id = trim($_POST['category_id'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $description = trim($_POST['itemdescription'] ?? '');

        if ($title === '' || $category_id === '') {
            throw new Exception('Title and Category are required');
        }

        // create product with unique ID (using timestamp + random)
        $product_id = 'PROD-' . time() . '-' . mt_rand(1000, 9999);
        if (empty($product_id) || strlen($product_id) < 10) {
            throw new Exception('Failed to generate valid product ID');
        }

        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO product (product_id, product_name, category_id, description) VALUES (:pid, :pname, :cid, :desc)");
        $stmt->execute([':pid' => $product_id, ':pname' => $title, ':cid' => $category_id, ':desc' => $description]);

        // handle image upload optionally
        if (!empty($_FILES['image']['tmp_name'])) {
            $uploadsDir = __DIR__ . '/../../uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = $product_id . '_' . time() . '.' . $ext;
            $target = $uploadsDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imgUrl = 'uploads/' . $filename;
                $stmtImg = $db->prepare("INSERT INTO product_image (product_id, image_url) VALUES (:pid, :url)");
                $stmtImg->execute([':pid' => $product_id, ':url' => $imgUrl]);
            }
        }

        // create listing with status Pending
        $listing_id = 'LIST-PROD-' . time() . '-' . mt_rand(1000, 9999);
        $listingType = 'product';
        $stmt2 = $db->prepare("INSERT INTO product_listing (listing_id, product_id, price, quantity, listing_type, status, date_posted) VALUES (:lid, :pid, :price, :qty, :listing_type, 'Pending', NOW())");
        $stmt2->execute([':lid' => $listing_id, ':pid' => $product_id, ':price' => $price, ':qty' => $quantity, ':listing_type' => $listingType]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Item saved and awaiting admin approval']);
        exit;
    }

    throw new Exception('Unsupported type');

} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
