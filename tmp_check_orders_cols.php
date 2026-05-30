<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SHOW COLUMNS FROM orders');
    $columns = array_map(function($r) { return $r['Field']; }, $stmt->fetchAll());
    echo "Orders table columns:\n";
    print_r($columns);
    echo "\nNeed to add: guest_token, guest_name, guest_email\n";
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
