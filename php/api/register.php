<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if user exists
$query = "SELECT id FROM marketusers WHERE email = :email OR username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':username', $data->username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'User already exists']);
    exit;
}

// Create user
$query = "INSERT INTO marketusers (username, email, password, full_name, phone, user_type) 
          VALUES (:username, :email, :password, :full_name, :phone, 'user')";
$stmt = $db->prepare($query);

$hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

$stmt->bindParam(':username', $data->username);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':password', $hashed_password);
$stmt->bindParam(':full_name', $data->fullName);
$stmt->bindParam(':phone', $data->phone);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User registered successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>