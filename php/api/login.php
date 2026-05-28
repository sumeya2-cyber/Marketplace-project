<?php
// php/api/login.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// =========================================================================
//  DEVELOPER OVERRIDE MODE (FORCED ADMIN LOGIN)
// =========================================================================
// This bypasses the database password check for rapid development.
// It returns the exact JSON structure your js/auth.js expects.

$_SESSION['user_id'] = 'USR-ADMIN-01';
$_SESSION['username'] = 'Super Admin';
$_SESSION['user_type'] = 'admin';

echo json_encode([
    'success' => true,
    'message' => 'Admin login forced!',
    'user' => [
        'username' => 'Super Admin',
        'user_type' => 'admin'
    ]
]);
exit(); 

// =========================================================================
//  PRODUCTION AUTHENTICATION LAYER
// =========================================================================
// Note: This section is unreachable while the override above is active.
// Remove the 'exit()' above when you are ready to switch to real database authentication.

require_once '../config/Database.php';

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->email) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT user_id, f_name, l_name, email, password, user_type FROM Users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($data->password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['f_name'] . ' ' . $row['l_name'];
            $_SESSION['user_type'] = strtolower($row['user_type']);
            
            echo json_encode([
                'success' => true,
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
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>