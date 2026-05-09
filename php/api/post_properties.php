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
    $image_path = uploadImage($_FILES['image'], 'property');
    if (!$image_path) {
        jsonResponse(false, 'Failed to upload image');
    }
}

$query = "INSERT INTO property_listing 
          (user_id, category_id, title,itemdescription, price, listing_type, 
           location, bedrooms, bathrooms, area_sqft, image_path, status) 
          VALUES 
          (:user_id, :category_id, :title, :itemdescription, :price, :listing_type,
           :location, :bedrooms, :bathrooms, :area_sqft, :image_path, 'pending')";

$stmt = $db->prepare($query);

$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':category_id', $_POST['category_id']);
$stmt->bindParam(':title', $_POST['title']);
$stmt->bindParam(':itemdescription', $_POST['itemdescription']);
$stmt->bindParam(':price', $_POST['price']);
$stmt->bindParam(':listing_type', $_POST['listing_type']);
$stmt->bindParam(':location', $_POST['location']);
$stmt->bindParam(':bedrooms', $_POST['bedrooms']);
$stmt->bindParam(':bathrooms', $_POST['bathrooms']);
$stmt->bindParam(':area_sqft', $_POST['area_sqft']);
$stmt->bindParam(':image_path', $image_path);

if ($stmt->execute()) {
    jsonResponse(true, 'Property posted successfully. Waiting for admin approval.');
} else {
    jsonResponse(false, 'Failed to post property');
}
?>