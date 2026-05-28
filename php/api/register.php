<?php
// php/api/register.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. FIXED: Adjusted casing mismatch to map to your capitalized file 'Database.php'
require_once '../config/Database.php';

$data = json_decode(file_get_contents("php://input"));

// 2. FIXED: Checking required fields that match your actual schema layout
if (
    !isset($data->user_id) || 
    !isset($data->f_name) || 
    !isset($data->l_name) || 
    !isset($data->email) || 
    !isset($data->phone) || 
    !isset($data->password)
) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 3. FIXED: Checked matching constraints inside your 'Users' table
    $query = "SELECT user_id FROM Users WHERE email = :email OR user_id = :user_id OR phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'User ID, Email, or Phone already registered']);
        exit;
    }

    // 4. FIXED: Remapped target table structure to 'Users' and used valid column identities
    $query = "INSERT INTO Users (user_id, f_name, l_name, email, phone, address, gender, password, status) 
              VALUES (:user_id, :f_name, :l_name, :email, :phone, :address, :gender, :password, 'Active')";
    $stmt = $db->prepare($query);

    // Securely hash the password string
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

    // Optional fields handling to avoid null binding crashes
    $address = isset($data->address) ? $data->address : null;
    $gender = isset($data->gender) ? $data->gender : null;

    // 5. FIXED: Bind parameters directly matching column targets
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':f_name', $data->f_name);
    $stmt->bindParam(':l_name', $data->l_name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':password', $hashed_password);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User registered successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }

} catch (PDOException $e) {
    // Catch database errors (like a column type mismatch) and output them cleanly for debugging
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>