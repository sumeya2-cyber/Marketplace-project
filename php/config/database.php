<?php
class Database {
    // ADJUSTED: Matching your exact localhost credentials
    private $host = "localhost";
    private $db_name = "marketplace_system"; // <-- Fixed to match your phpMyAdmin DB name
    private $username = "root";          // Default XAMPP username
    private $password = "";              // Default XAMPP password is empty
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // OPTIMIZED: Added charset directly to the connection string for better efficiency
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enables query debugging
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns records as clean arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Uses native SQL execution for security
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>