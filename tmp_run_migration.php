<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting migration...\n";
    
    // 1) Drop the foreign key constraint on orders.user_id
    try {
        $pdo->query('ALTER TABLE `orders` DROP FOREIGN KEY IF EXISTS `fk_orders_user`');
        echo "✓ Dropped foreign key fk_orders_user\n";
    } catch (Exception $e) {
        echo "⚠ Error dropping fk_orders_user: " . $e->getMessage() . "\n";
    }
    
    // 2) Modify user_id to allow NULL
    try {
        $pdo->query('ALTER TABLE `orders` MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL');
        echo "✓ Modified orders.user_id to allow NULL\n";
    } catch (Exception $e) {
        echo "⚠ Error modifying user_id: " . $e->getMessage() . "\n";
    }
    
    // 3) Re-add the foreign key constraint
    try {
        $pdo->query('ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL');
        echo "✓ Re-added foreign key fk_orders_user with ON DELETE SET NULL\n";
    } catch (Exception $e) {
        echo "⚠ Error adding fk_orders_user: " . $e->getMessage() . "\n";
    }
    
    // 4) Fix return_request.user_id to allow NULL
    try {
        $pdo->query('ALTER TABLE `return_request` MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL');
        echo "✓ Modified return_request.user_id to allow NULL\n";
    } catch (Exception $e) {
        echo "⚠ Error modifying return_request.user_id: " . $e->getMessage() . "\n";
    }
    
    // 5) Fix review.user_id to allow NULL
    try {
        $pdo->query('ALTER TABLE `review` MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL');
        echo "✓ Modified review.user_id to allow NULL\n";
    } catch (Exception $e) {
        echo "⚠ Error modifying review.user_id: " . $e->getMessage() . "\n";
    }
    
    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo 'FATAL ERROR: ' . $e->getMessage();
}
