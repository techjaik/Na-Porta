<?php
class Database {
    private $host = 'sql105.infinityfree.com';
    private $db_name = 'if0_40155099_naporta_db';
    private $username = 'if0_40155099';
    private $password = 'Jaishreeramm9';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            die("Database connection failed. Please contact support.");
        }
        
        return $this->conn;
    }
}

// Global database connection
$database = new Database();
$pdo = $database->getConnection();
?>
