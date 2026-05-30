<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding guest-specific columns to orders table...\n";
    
    try {
        $pdo->query('ALTER TABLE `orders` ADD COLUMN `guest_token` varchar(100) DEFAULT NULL');
        echo "✓ Added guest_token column\n";
    } catch (Exception $e) {
        echo "⚠ Error adding guest_token: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->query('ALTER TABLE `orders` ADD COLUMN `guest_name` varchar(255) DEFAULT NULL');
        echo "✓ Added guest_name column\n";
    } catch (Exception $e) {
        echo "⚠ Error adding guest_name: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->query('ALTER TABLE `orders` ADD COLUMN `guest_email` varchar(255) DEFAULT NULL');
        echo "✓ Added guest_email column\n";
    } catch (Exception $e) {
        echo "⚠ Error adding guest_email: " . $e->getMessage() . "\n";
    }
    
    echo "\nNew orders table structure:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM orders');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo 'FATAL ERROR: ' . $e->getMessage();
}
