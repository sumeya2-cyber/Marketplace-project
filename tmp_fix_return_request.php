<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding missing user_id column to return_request...\n";
    
    try {
        $pdo->query('ALTER TABLE `return_request` ADD COLUMN `user_id` varchar(50) DEFAULT NULL');
        echo "✓ Added user_id column to return_request\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->query('ALTER TABLE `return_request` ADD COLUMN `request_type` varchar(20) DEFAULT "Return"');
        echo "✓ Added request_type column to return_request\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->query('ALTER TABLE `return_request` ADD CONSTRAINT `fk_return_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL');
        echo "✓ Added foreign key constraint for return_request.user_id\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\nNew return_request structure:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM return_request');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo 'FATAL ERROR: ' . $e->getMessage();
}
