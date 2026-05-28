<?php
// test_conn.php

// 1. Include your Database class file
require_once 'Database.php'; 

// 2. Instantiate the Database class object
$database = new Database();
$dbConnection = $database->getConnection();

// 3. Verify if the connection object was successfully returned
if ($dbConnection) {
    echo "<h3>Success! Your Object-Oriented Database class is connected perfectly.</h3>";
    
    // Let's test pulling your platform's operational Payment Methods to be 100% sure! [cite: 33, 59]
    try {
        $query = "SELECT method_name, provider_name FROM Payment_Method";
        $stmt = $dbConnection->prepare($query);
        $stmt->execute();
        
        $methods = $stmt->fetchAll();
        
        echo "<strong>Active Financial Gateways:</strong><br>";
        foreach($methods as $row) {
            echo "- " . htmlspecialchars($row['method_name']) . " provided by " . htmlspecialchars($row['provider_name']) . "<br>";
        }
    } catch(PDOException $e) {
        echo "Connection succeeded, but query failed: " . $e->getMessage();
    }
} else {
    echo "Failed to initialize connection engine.";
}
?>