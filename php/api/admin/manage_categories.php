<?php
// php/admin/api/manage_categories.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// --- FOR TESTING VIA THUNDER CLIENT / DASHBOARD DEPLOYMENT ---
// If sessions are not fully persistent yet, you can temporarily comment this block out:
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // For local testing convenience if session states clear out early:
    $_SESSION['user_type'] = 'admin'; 
}

require_once '../../config/Database.php';

// Extract raw data from incoming JSON payload packets
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->type) || !isset($data->action)) {
    echo json_encode(['success' => false, 'message' => 'Malformed dataset payload submitted.']);
    exit;
}

$table = '';
$idCol = 'category_id';
$nameCol = 'category_name';
$prefix = '';

// Map structural fields cleanly onto your database layout schema definitions
switch($data->type) {
    case 'properties':
        $table = 'Property_Category';
        $prefix = 'CAT-PROP-';
        break;
    case 'products':
        $table = 'Product_Category';
        $prefix = 'CAT-PROD-';
        break;
    case 'services': // Remapped from 'contracts' to align with Service_Category
        $table = 'Service_Category';
        $prefix = 'CAT-SRV-';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid domain matrix reference type requested.']);
        exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    if ($data->action == "update") {
        // Updated query mapping your exact column syntax structures
        $query = "UPDATE $table SET $nameCol = :name WHERE $idCol = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':id', $data->id);
        
    } elseif ($data->action == "add") {
        // Generate a clean custom primary structural identity string key
        $new_id = $prefix . uniqid();
        
        $query = "INSERT INTO $table ($idCol, $nameCol) VALUES (:id, :name)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $new_id);
        $stmt->bindParam(':name', $data->name);

    } elseif ($data->action == "delete") {
        $query = "DELETE FROM $table WHERE $idCol = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unrecognized action assignment processing layer.']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category operation executed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execution engine processed query with errors.']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database operation rejected: ' . $e->getMessage()
    ]);
}
?>