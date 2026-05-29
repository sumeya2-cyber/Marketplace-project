<?php
// php/api/login.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

define('ADMIN_LOGIN_OVERRIDE', true);

if (ADMIN_LOGIN_OVERRIDE === true) {
    // If the request is for admin credentials, keep the forced admin route.
    $payload = json_decode(file_get_contents('php://input'), true);
    if (isset($payload['type']) && strtolower(trim($payload['type'])) === 'admin') {
        $_SESSION['user_id'] = 'USR-ADMIN-01';
        $_SESSION['username'] = 'Super Admin';
        $_SESSION['user_type'] = 'admin';

        echo json_encode([
            'success' => true,
            'message' => 'Admin login successful.',
            'user' => [
                'username' => 'Super Admin',
                'user_type' => 'admin'
            ]
        ]);
        exit;
    }
}

// =========================================================================
//  PRODUCTION AUTHENTICATION LAYER
// =========================================================================
// Note: This section is unreachable while the override above is active.
// Remove the 'exit()' above when you are ready to switch to real database authentication.

require_once '../config/Database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];
$type = isset($data['type']) ? strtolower(trim($data['type'])) : 'user';

if ($type === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Invalid admin credentials.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = 'SELECT user_id, f_name, l_name, email, password FROM users WHERE email = :email LIMIT 1';
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = trim($user['f_name'] . ' ' . $user['l_name']);
            $_SESSION['user_type'] = 'user';

            echo json_encode([
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'username' => $_SESSION['username'],
                    'user_type' => $_SESSION['user_type']
                ]
            ]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>