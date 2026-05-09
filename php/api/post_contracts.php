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
    $image_path = uploadImage($_FILES['image'], 'contract');
    if (!$image_path) {
        jsonResponse(false, 'Failed to upload image');
    }
}

$query = "INSERT INTO contract_listing 
          (user_id, category_id, title, description, budget, duration, 
           location, experience_level, image_path, status) 
          VALUES 
          (:user_id, :category_id, :title, :description, :budget, :duration,
           :location, :experience_level, :image_path, 'pending')";

$stmt = $db->prepare($query);

$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':category_id', $_POST['category_id']);
$stmt->bindParam(':title', $_POST['title']);
$stmt->bindParam(':description', $_POST['description']);
$stmt->bindParam(':budget', $_POST['price']);
$stmt->bindParam(':duration', $_POST['duration']);
$stmt->bindParam(':location', $_POST['location']);
$stmt->bindParam(':experience_level', $_POST['experience_level']);
$stmt->bindParam(':image_path', $image_path);

if ($stmt->execute()) {
    jsonResponse(true, 'Contract posted successfully. Waiting for admin approval.');
} else {
    jsonResponse(false, 'Failed to post contract');
}
?>