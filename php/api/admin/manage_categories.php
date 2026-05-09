<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$table = '';
switch($data->type) {
    case 'properties':
        $table = 'property_categories';
        break;
    case 'products':
        $table = 'product_categories';
        break;
    case 'contracts':
        $table = 'contract_categories';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
}

$database = new Database();
$db = $database->getConnection();

if ($data->action == "update") {
    $query = "UPDATE $table SET name = :name, description = :description where id=:id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':id', $data->id);
    
}
elseif($data->action=="add"){
$query = "INSERT INTO $table (name, description, status) VALUES (:name, :description, 'active')";

$stmt = $db->prepare($query);
$stmt->bindParam(':name', $data->name);
$stmt->bindParam(':description', $data->description);

} elseif ($data->action == "delete") {
    $query = "DELETE FROM $table WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);

}


if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>

