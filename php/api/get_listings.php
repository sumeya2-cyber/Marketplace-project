<?php
// php/api/get_listings.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET'); // Changed to GET as it uses URL parameters

// 1. FIXED: Adjusted case-sensitivity casing mismatch
require_once '../config/Database.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'properties';
$categoryId = isset($_GET['category']) ? $_GET['category'] : null;

$database = new Database();
$db = $database->getConnection();

$query = '';
$params = [];

switch($type) {
    case 'properties':
        // 2. FIXED: Refactored to join 'Property_Listing', base 'Property', and 'Property_Category'
        $query = "SELECT pl.listing_id, pl.price, pl.listing_type, pl.date_posted, pl.status,
                         p.address, p.city, p.size, p.description,
                         pc.category_name 
                  FROM Property_Listing pl
                  JOIN Property p ON pl.property_id = p.property_id
                  JOIN Property_Category pc ON p.category_id = pc.category_id
                  WHERE pl.status = 'Approved'"; // Adjusted status column format
                  
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY pl.date_posted DESC";
        break;
        
    case 'products':
        // 3. FIXED: Refactored to join 'Product_Listing', base 'Product', 'Brand', and 'Product_Category'
        $query = "SELECT pl.listing_id, pl.price, pl.quantity, pl.date_posted, pl.status,
                         p.product_name, p.description,
                         b.brand_name,
                         pc.category_name 
                  FROM Product_Listing pl
                  JOIN Product p ON pl.product_id = p.product_id
                  LEFT JOIN Brand b ON p.brand_id = b.brand_id
                  JOIN Product_Category pc ON p.category_id = pc.category_id
                  WHERE pl.status = 'Approved'";
                  
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY pl.date_posted DESC";
        break;
        
    case 'services': // Changed case name from 'contracts' to align with Professional Services requests
        // 4. FIXED: Refactored to map accurately onto 'Service_Request' and 'Service_Category'
        $query = "SELECT sr.request_id, sr.description, sr.budget, sr.deadline, sr.status,
                         u.f_name, u.l_name,
                         sc.category_name 
                  FROM Service_Request sr
                  JOIN Users u ON sr.user_id = u.user_id
                  JOIN Service_Category sc ON sr.category_id = sc.category_id
                  WHERE sr.status = 'Open'"; // Services use 'Open' status before assignment
                  
        if ($categoryId) {
            $query .= " AND sr.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $query .= " ORDER BY sr.request_id DESC";
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid listing type requested']);
        exit;
}

try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return empty array instead of failing if no listings match filters yet
    echo json_encode($listings);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . $e->getMessage()]);
}
?>