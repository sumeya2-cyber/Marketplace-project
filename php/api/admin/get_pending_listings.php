<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([]);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$pending = [];

// Get pending properties
$query = "SELECT p.*, 'property' as type, u.username 
          FROM property_listing p 
          JOIN marketusers u ON p.user_id = u.id 
          WHERE p.itemstatus = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending = array_merge($pending, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Get pending products
$query = "SELECT p.*, 'product' as type, u.username 
          FROM product_listing p 
          JOIN marketusers u ON p.user_id = u.id 
          WHERE p.itemstatus = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending = array_merge($pending, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Get pending contracts
$query = "SELECT c.*, 'contract' as type, u.username 
          FROM contract_listing c 
          JOIN marketusers u ON c.user_id = u.id 
          WHERE c.itemstatus = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending = array_merge($pending, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode($pending);
?>