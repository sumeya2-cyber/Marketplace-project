<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$database = new Database();
$db = $database->getConnection();

$table = '';
switch($data->type) {
    case 'property':
        $table = 'property_listing';
       
        break;
    case 'product':
        $table = 'product_listing';
        
        break;
    case 'contract':
        $table = 'contract_listing';
        
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
}

$status = ($data->action === 'approve') ? 'approved' : 'rejected';

$query = "UPDATE $table SET itemstatus = '$status' WHERE id = :id";
$stmt = $db->prepare($query);
//$stmt->bindParam(':itemstatus', $status);
$stmt->bindParam(':id', $data->id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>