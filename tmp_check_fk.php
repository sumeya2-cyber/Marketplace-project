<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking orders table constraints:\n";
    $stmt = $pdo->query('SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME="orders" AND COLUMN_NAME="user_id"');
    foreach ($stmt as $row) {
        echo "Constraint: " . $row['CONSTRAINT_NAME'] . " -> " . $row['REFERENCED_TABLE_NAME'] . "\n";
    }
    
    echo "\nChecking orders table structure:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM orders');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' ' . $row['Type'] . ' NULL=' . ($row['Null'] === 'YES' ? 'YES' : 'NO') . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
