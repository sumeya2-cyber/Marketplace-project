<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$id = $_POST['id'];
$title = $_POST['title'];
$category_id = $_POST['category_id'];
$description = $_POST['description'];
$price = $_POST['price'];
$quantity = $_POST['quantity'];
$condition = $_POST['condition'];
$status = $_POST['status'];

// Handle image upload if new image is provided
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    // Delete old image if exists
    $query = "SELECT image_path FROM product_listing WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $old_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($old_product && $old_product['image_path']) {
        $old_file = '../../' . $old_product['image_path'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    
    $image_path = uploadImage($_FILES['image'], 'product');
    if (!$image_path) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

// Update query
if ($image_path) {
    $query = "UPDATE product_listing 
              SET title = :title, category_id = :category_id, description = :description,
                  price = :price, quantity = :quantity, `condition` = :condition,
                  status = :status, image_path = :image_path
              WHERE id = :id";
} else {
    $query = "UPDATE product_listing 
              SET title = :title, category_id = :category_id, description = :description,
                  price = :price, quantity = :quantity, `condition` = :condition,
                  status = :status
              WHERE id = :id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':category_id', $category_id);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':price', $price);
$stmt->bindParam(':quantity', $quantity);
$stmt->bindParam(':condition', $condition);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':id', $id);
if ($image_path) {
    $stmt->bindParam(':image_path', $image_path);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update product']);
}
?>