<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../includes/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['name']) || empty($input['email']) || empty($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
    exit;
}

$name = trim($input['name']);
$email = trim($input['email']);
$password = $input['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $check = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $check->bindParam(':email', $email);
    $check->execute();
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
        exit;
    }

    $parts = preg_split('/\s+/', $name);
    $firstName = array_shift($parts);
    $lastName = trim(implode(' ', $parts));
    if ($lastName === '') {
        $lastName = ' '; // keep not null schema happy
    }

    $userId = 'USR-' . bin2hex(random_bytes(6));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $query = 'INSERT INTO users (user_id, f_name, l_name, email, phone, password, status, created_at) VALUES (:user_id, :f_name, :l_name, :email, :phone, :password, :status, NOW())';
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':f_name', $firstName);
    $stmt->bindParam(':l_name', $lastName);
    $stmt->bindParam(':email', $email);
    $emptyPhone = '';
    $stmt->bindParam(':phone', $emptyPhone);
    $stmt->bindParam(':password', $hashedPassword);
    $activeStatus = 'Active';
    $stmt->bindParam(':status', $activeStatus);

    if ($stmt->execute()) {
        sendAdminNotification($db, null, 'New User Registration', "A new user has registered: $name ($email).", $userId, 'user_registration');
        echo json_encode(['success' => true, 'message' => 'Signup successful. Please login to continue.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to register user.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>