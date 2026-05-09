<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Please login first');
}

$database = new Database();
$db = $database->getConnection();

$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $image_path = uploadImage($_FILES['image'], 'product');
    if (!$image_path) {
        jsonResponse(false, 'Failed to upload image');
    }
}

$query = "INSERT INTO product_listing 
          (user_id, category_id, title, itemdescription, price, quantity, 
           productcondition, image_path, itemstatus) 
          VALUES 
          (:user_id, :category_id, :title, :itemdescription, :price, :quantity,
           :productcondition, :image_path, 'pending')";

$stmt = $db->prepare($query);

$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':category_id', $_POST['category_id']);
$stmt->bindParam(':title', $_POST['title']);
$stmt->bindParam(':itemdescription', $_POST['itemdescription']);
$stmt->bindParam(':price', $_POST['price']);
$stmt->bindParam(':quantity', $_POST['quantity']);
$stmt->bindParam(':productcondition', $_POST['productcondition']);
$stmt->bindParam(':image_path', $image_path);

if ($stmt->execute()) {
    jsonResponse(true, 'Product posted successfully. Waiting for admin approval.');
} else {
    jsonResponse(false, 'Failed to post product');
}
?>