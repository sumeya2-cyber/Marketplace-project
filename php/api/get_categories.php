<?php
// php/api/get_categories.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 1. FIXED: Corrected Database.php casing mismatch
require_once '../config/Database.php';

// Acceptable parameters: 'properties', 'products', or 'services'
$type = isset($_GET['type']) ? $_GET['type'] : 'products';

$database = new Database();
$db = $database->getConnection();

$table = '';
$idColumn = '';
$nameColumn = '';

// 2. FIXED: Remapped targets to match your exact capitalized lookup tables
switch($type) {
    case 'properties':
        $table = 'Property_Category';
        $idColumn = 'category_id';
        $nameColumn = 'category_name';
        break;
    case 'products':
        $table = 'Product_Category';
        $idColumn = 'category_id';
        $nameColumn = 'category_name';
        break;
    case 'services': // FIXED: Changed case identity from 'contracts' to fit your Service module
        $table = 'Service_Category';
        $idColumn = 'category_id';
        $nameColumn = 'category_name';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid domain type requested.']);
        exit;
}

try {
    // 3. FIXED: Adjusted column maps to use your precise schema fields and dropped non-existent 'status' checks
    $query = "SELECT $idColumn AS id, $nameColumn AS name FROM $table ORDER BY $nameColumn ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Always return the array back directly to the client view
    echo json_encode($categories);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database category pull failed: ' . $e->getMessage()]);
}
?>