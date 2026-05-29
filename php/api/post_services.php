<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Extract form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['itemdescription'] ?? '');
    $budget = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $category_id = trim($_POST['category_id'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $experience_level = trim($_POST['experience_level'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($title === '' || $category_id === '') {
        throw new Exception('Title and Category are required');
    }

    // Generate unique request ID
    $request_id = 'SRV-' . time() . '-' . mt_rand(1000, 9999);
    
    // Build full description from form fields
    $full_description = "Title: " . $title . "\n";
    $full_description .= "Details: " . $description . "\n";
    if ($duration) $full_description .= "Duration: " . $duration . "\n";
    if ($experience_level) $full_description .= "Experience Level: " . $experience_level . "\n";
    if ($location) $full_description .= "Location: " . $location . "\n";

    // Insert service request with 'Pending' status so admin can approve
    $stmt = $db->prepare("INSERT INTO service_request (request_id, category_id, description, budget, status, deadline) 
                         VALUES (:rid, :cid, :desc, :budget, 'Pending', NOW())");
    $stmt->execute([
        ':rid' => $request_id,
        ':cid' => $category_id,
        ':desc' => $full_description,
        ':budget' => $budget
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Service request submitted successfully! Waiting for admin approval.']);
    exit;

} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
