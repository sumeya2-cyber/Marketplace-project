<?php
// php/api/post_contracts.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// --- FORCED TEST RETAINERS ---
$user_id = 'USR-999'; 
$category_id = 'CAT-SRV-01';

try {
    $db->beginTransaction();

    // 1. SAFETY CHECK: Ensure the User anchor exists so foreign keys pass
    $checkUser = $db->prepare("SELECT user_id FROM Users WHERE user_id = :uid");
    $checkUser->execute([':uid' => $user_id]);
    if ($checkUser->rowCount() === 0) {
        $insertUser = $db->prepare("INSERT INTO Users (user_id, f_name, l_name, email, phone, password, status) 
                                    VALUES (:uid, 'Test', 'User', 'testuser@example.com', '09000000', 'hash', 'Active')");
        $insertUser->execute([':uid' => $user_id]);
    }

    // 2. SAFETY CHECK: Ensure the Service Category exists so foreign keys pass
    $checkCat = $db->prepare("SELECT category_id FROM Service_Category WHERE category_id = :cid");
    $checkCat->execute([':cid' => $category_id]);
    if ($checkCat->rowCount() === 0) {
        $insertCat = $db->prepare("INSERT INTO Service_Category (category_id, category_name) VALUES (:cid, 'General Freelance')");
        $insertCat->execute([':cid' => $category_id]);
    }

    // 3. Insert the actual service project entry
    $request_id = 'SRV-' . uniqid();
    $query = "INSERT INTO Service_Request (request_id, user_id, category_id, description, budget, deadline, status) 
              VALUES (:request_id, :user_id, :category_id, :description, :budget, :deadline, 'Open')";

    $stmt = $db->prepare($query);

    $title = isset($_POST['title']) ? $_POST['title'] : 'Development Project';
    $desc = isset($_POST['description']) ? $_POST['description'] : 'Looking for a developer.';
    $full_description = "Title: " . $title . " | Details: " . $desc;

    $budget = isset($_POST['price']) ? floatval($_POST['price']) : 5000.00;
    $deadline = !empty($_POST['duration']) ? $_POST['duration'] : date('Y-m-d', strtotime('+30 days'));

    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':user_id', $user_id); 
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':description', $full_description);
    $stmt->bindParam(':budget', $budget);
    $stmt->bindParam(':deadline', $deadline);

    $stmt->execute();
    $db->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Database guard passed! Service request inserted successfully.'
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Database error details: ' . $e->getMessage()
    ]);
}
?>