<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=marketplace_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach (['orders', 'order_item'] as $table) {
        echo "TABLE: $table\n";
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        foreach ($stmt as $row) {
            echo $row['Field'] . ' ' . $row['Type'] . "\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
