<?php
// php/api/post_properties.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// --- FORCED TEST RETAINERS FOR THUNDER CLIENT TESTING ---
$user_id = 'USR-999'; 
$category_id = 'CAT-PROP-01';

try {
    $db->beginTransaction();

    // 1. SAFETY CHECK: Ensure the User anchor exists
    $checkUser = $db->prepare("SELECT user_id FROM Users WHERE user_id = :uid");
    $checkUser->execute([':uid' => $user_id]);
    if ($checkUser->rowCount() === 0) {
        $insertUser = $db->prepare("INSERT INTO Users (user_id, f_name, l_name, email, phone, password, status) 
                                    VALUES (:uid, 'Test', 'User', 'testuser@example.com', '09000000', 'hash', 'Active')");
        $insertUser->execute([':uid' => $user_id]);
    }

    // 2. SAFETY CHECK: Ensure the Property Category exists
    $checkCat = $db->prepare("SELECT category_id FROM Property_Category WHERE category_id = :cid");
    $checkCat->execute([':cid' => $category_id]);
    if ($checkCat->rowCount() === 0) {
        $insertCat = $db->prepare("INSERT INTO Property_Category (category_id, category_name) VALUES (:cid, 'Modern Apartment')");
        $insertCat->execute([':cid' => $category_id]);
    }

    // Generate unique relational identifiers
    $property_id = 'PROP-' . uniqid();
    $listing_id = 'LIST-PROP-' . uniqid();

    // 3. Create physical structural baseline Property entity record
    $query1 = "INSERT INTO Property (property_id, address, city, size, description, category_id) 
               VALUES (:property_id, :address, :city, :size, :description, :category_id)";
    
    $stmt1 = $db->prepare($query1);
    
    $address = isset($_POST['location']) ? $_POST['location'] : 'Bole, Near Edna Mall';
    $city = isset($_POST['city']) ? $_POST['city'] : 'Addis Ababa';
    $size = isset($_POST['area_sqft']) ? $_POST['area_sqft'] . ' sqft' : '150 sqft';
    $desc = isset($_POST['itemdescription']) ? $_POST['itemdescription'] : 'Fully furnished flat.';

    $stmt1->bindParam(':property_id', $property_id);
    $stmt1->bindParam(':address', $address);
    $stmt1->bindParam(':city', $city);
    $stmt1->bindParam(':size', $size);
    $stmt1->bindParam(':description', $desc);
    $stmt1->bindParam(':category_id', $category_id);
    $stmt1->execute();

    // 4. Publish transactional parameters onto Property_Listing (Set to Approved!)
    $query2 = "INSERT INTO Property_Listing (listing_id, property_id, price, listing_type, status) 
               VALUES (:listing_id, :property_id, :price, :listing_type, 'Approved')";
    
    $stmt2 = $db->prepare($query2);
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 45000.00;
    $type = isset($_POST['listing_type']) ? $_POST['listing_type'] : 'Rent';

    $stmt2->bindParam(':listing_id', $listing_id);
    $stmt2->bindParam(':property_id', $property_id);
    $stmt2->bindParam(':price', $price);
    $stmt2->bindParam(':listing_type', $type);
    $stmt2->execute();

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Property marketplace entry created successfully in your XAMPP tables!'
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Transaction failure: ' . $e->getMessage()]);
}
?>