<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Check if we're in production or development
        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
            // Production environment
            $this->host = getenv('DB_HOST') ?: 'your-production-host';
            $this->db_name = getenv('DB_NAME') ?: 'your-production-db';
            $this->username = getenv('DB_USER') ?: 'your-production-user';
            $this->password = getenv('DB_PASS') ?: 'your-production-password';
        } else {
            // Development environment (localhost)
            $this->host = 'localhost';
            $this->db_name = 'na_porta_db';
            $this->username = 'root';
            $this->password = '';
        }
    }

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
            // Don't expose database errors in production
            if ($_SERVER['HTTP_HOST'] === 'localhost') {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo "Database connection failed. Please try again later.";
            }
        }
        
        return $this->conn;
    }
}

// Global database connection
$database = new Database();
$pdo = $database->getConnection();
?>
