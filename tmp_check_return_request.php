<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking return_request table structure:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM return_request');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
