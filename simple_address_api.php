<?php
/**
 * SUPER SIMPLE ADDRESS API - No classes, no complexity
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Database connection from config
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'add') {
            $name = trim($input['name'] ?? '');
            $cep = trim($input['cep'] ?? '');
            $street = trim($input['street'] ?? '');
            $number = trim($input['number'] ?? '');
            $complement = trim($input['complement'] ?? '');
            $neighborhood = trim($input['neighborhood'] ?? '');
            $city = trim($input['city'] ?? '');
            $state = trim($input['state'] ?? '');
            $isDefault = $input['is_default'] ? 1 : 0;
            
            // Simple validation
            if (empty($name) || empty($cep) || empty($street) || empty($number) || empty($neighborhood) || empty($city) || empty($state)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // If default, remove default from others
            if ($isDefault) {
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }
            
            // Insert new address
            $stmt = $pdo->prepare("
                INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$userId, $name, $cep, $street, $number, $complement, $neighborhood, $city, $state, $isDefault]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Address added successfully',
                'address_id' => $pdo->lastInsertId()
            ]);
            exit;
        }
    }
    
    // Handle GET requests (list addresses)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'addresses' => $addresses
        ]);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
