<?php
/**
 * Na Porta - Database Connection Manager
 * Secure database connection with environment detection
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    
    private function __construct() {
        $this->detectEnvironment();
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Detect environment and set database credentials
     */
    private function detectEnvironment() {
        // Check if we're on InfinityFree hosting
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.free.nf') !== false) {
            // Production - InfinityFree
            $this->host = 'sql105.infinityfree.com';
            $this->database = 'if0_40155099_naporta_db';
            $this->username = 'if0_40155099';
            $this->password = 'Jaishreeramm9';
        } else {
            // Development - Local XAMPP
            $this->host = 'localhost';
            $this->database = 'na_porta_db';
            $this->username = 'root';
            $this->password = '';
        }
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute prepared statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erro na consulta ao banco de dados.");
        }
    }
    
    /**
     * Get single row
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Get all rows
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Global database instance
$db = Database::getInstance();
$pdo = $db->getConnection();
?>
