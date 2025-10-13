<?php
// LIVE HOSTING DATABASE CONFIGURATION
// Copy this content to your config/database.php file on the hosting server

class Database {
    // UPDATE THESE VALUES WITH YOUR HOSTING DATABASE DETAILS
    private $host = 'sql200.epizy.com';  // Your hosting MySQL server
    private $db_name = 'epiz_XXXXXXX_naporta_db';  // Your full database name
    private $username = 'epiz_XXXXXXX';  // Your database username
    private $password = 'your_database_password';  // Your database password
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
            // Don't show database errors to users in production
            die("Database connection failed. Please contact support.");
        }
        
        return $this->conn;
    }
}

// Global database connection
$database = new Database();
$pdo = $database->getConnection();
?>

<!-- 
INSTRUCTIONS FOR LIVE HOSTING:

1. Get your database details from your hosting control panel:
   - Host: Usually provided by hosting (e.g., sql200.epizy.com)
   - Database name: Usually prefixed (e.g., epiz_XXXXXXX_naporta_db)
   - Username: Usually prefixed (e.g., epiz_XXXXXXX)
   - Password: The one you created

2. Replace the values above with your actual hosting database details

3. Copy this entire content and paste it into config/database.php on your hosting server
-->
