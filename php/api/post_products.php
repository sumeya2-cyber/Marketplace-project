<?php
// php/api/post_products.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// --- FORCED TEST RETAINERS FOR THUNDER CLIENT TESTING ---
$user_id = 'USR-999'; 
$category_id = 'CAT-PROD-01';

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

    // 2. SAFETY CHECK: Ensure the Product Category exists
    $checkCat = $db->prepare("SELECT category_id FROM Product_Category WHERE category_id = :cid");
    $checkCat->execute([':cid' => $category_id]);
    if ($checkCat->rowCount() === 0) {
        $insertCat = $db->prepare("INSERT INTO Product_Category (category_id, category_name) VALUES (:cid, 'Electronics')");
        $insertCat->execute([':cid' => $category_id]);
    }

    // Generate unique structural primary keys
    $product_id = 'PROD-' . uniqid();
    $listing_id = 'LIST-PROD-' . uniqid();

    // 3. Insert into base Product table
    $query1 = "INSERT INTO Product (product_id, product_name, brand_id, category_id, description) 
               VALUES (:product_id, :product_name, NULL, :category_id, :description)";
    
    $stmt1 = $db->prepare($query1);
    $title = isset($_POST['title']) ? $_POST['title'] : 'iPhone 15 Pro';
    $desc = isset($_POST['itemdescription']) ? $_POST['itemdescription'] : 'Brand new condition.';

    $stmt1->bindParam(':product_id', $product_id);
    $stmt1->bindParam(':product_name', $title);
    $stmt1->bindParam(':category_id', $category_id);
    $stmt1->bindParam(':description', $desc);
    $stmt1->execute();

    // 4. Insert transactional parameters onto Product_Listing (marked as Approved for easy testing!)
    $query2 = "INSERT INTO Product_Listing (listing_id, product_id, price, quantity, status) 
               VALUES (:listing_id, :product_id, :price, :quantity, 'Approved')";
    
    $stmt2 = $db->prepare($query2);
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 1200.00;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 5;

    $stmt2->bindParam(':listing_id', $listing_id);
    $stmt2->bindParam(':product_id', $product_id);
    $stmt2->bindParam(':price', $price);
    $stmt2->bindParam(':quantity', $quantity);
    $stmt2->execute();

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Product listing created successfully in your XAMPP tables!'
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>