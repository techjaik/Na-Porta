<?php
/**
 * Address Management API
 * Handles CRUD operations for user addresses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $db = Database::getInstance();
    $auth = new Auth();

    // Check if user is logged in
    $user = $auth->getCurrentUser();
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        exit;
    }

    // Check if user_addresses table exists, create if not
    $pdo = $db->getConnection();
    try {
        $pdo->query("SELECT 1 FROM user_addresses LIMIT 1");
    } catch (Exception $e) {
        // Table doesn't exist, create it
        $createTableSQL = "
        CREATE TABLE user_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            cep VARCHAR(9) NOT NULL,
            street VARCHAR(200) NOT NULL,
            number VARCHAR(10) NOT NULL,
            complement VARCHAR(100),
            neighborhood VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(2) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_default (is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($createTableSQL);
        error_log("Created user_addresses table");
    }
    
    $userId = $user['id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    if ($method === 'GET') {
        switch ($action) {
            case 'list':
                // Get all addresses for the user
                $addresses = $db->fetchAll("
                    SELECT * FROM user_addresses 
                    WHERE user_id = ? 
                    ORDER BY is_default DESC, created_at DESC
                ", [$userId]);
                
                echo json_encode([
                    'success' => true,
                    'addresses' => $addresses
                ]);
                break;
                
            case 'get':
                // Get specific address
                $addressId = $_GET['id'] ?? 0;
                $address = $db->fetch("
                    SELECT * FROM user_addresses 
                    WHERE id = ? AND user_id = ?
                ", [$addressId, $userId]);
                
                if ($address) {
                    echo json_encode([
                        'success' => true,
                        'address' => $address
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Endereço não encontrado'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
        }
        
    } elseif ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $action = $input['action'] ?? '';

        // Debug logging
        error_log("Address API POST - User ID: $userId, Action: $action");
        error_log("Address API POST - Raw input: " . $rawInput);
        error_log("Address API POST - Parsed input: " . json_encode($input));

        switch ($action) {
            case 'add':
                // Add new address
                $name = trim($input['name'] ?? '');
                $cep = trim($input['cep'] ?? '');
                $street = trim($input['street'] ?? '');
                $number = trim($input['number'] ?? '');
                $complement = trim($input['complement'] ?? '');
                $neighborhood = trim($input['neighborhood'] ?? '');
                $city = trim($input['city'] ?? '');
                $state = trim($input['state'] ?? '');
                $isDefault = (bool)($input['is_default'] ?? false);
                
                // Validation
                if (empty($name) || empty($cep) || empty($street) || empty($number) || 
                    empty($neighborhood) || empty($city) || empty($state)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Todos os campos obrigatórios devem ser preenchidos'
                    ]);
                    exit;
                }
                
                // If this is set as default, remove default from other addresses
                if ($isDefault) {
                    $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
                }
                
                // Insert new address
                $stmt = $db->query("
                    INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [$userId, $name, $cep, $street, $number, $complement, $neighborhood, $city, $state, $isDefault ? 1 : 0]);

                $addressId = $db->lastInsertId();

                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço adicionado com sucesso',
                    'address_id' => $addressId
                ]);
                break;
                
            case 'update':
                // Update existing address
                $addressId = $input['address_id'] ?? 0;
                $name = trim($input['name'] ?? '');
                $cep = trim($input['cep'] ?? '');
                $street = trim($input['street'] ?? '');
                $number = trim($input['number'] ?? '');
                $complement = trim($input['complement'] ?? '');
                $neighborhood = trim($input['neighborhood'] ?? '');
                $city = trim($input['city'] ?? '');
                $state = trim($input['state'] ?? '');
                $isDefault = (bool)($input['is_default'] ?? false);
                
                // Check if address belongs to user
                $existingAddress = $db->fetch("
                    SELECT id FROM user_addresses WHERE id = ? AND user_id = ?
                ", [$addressId, $userId]);
                
                if (!$existingAddress) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Endereço não encontrado'
                    ]);
                    exit;
                }
                
                // Validation
                if (empty($name) || empty($cep) || empty($street) || empty($number) || 
                    empty($neighborhood) || empty($city) || empty($state)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Todos os campos obrigatórios devem ser preenchidos'
                    ]);
                    exit;
                }
                
                // If this is set as default, remove default from other addresses
                if ($isDefault) {
                    $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?", [$userId, $addressId]);
                }
                
                // Update address
                $db->query("
                    UPDATE user_addresses 
                    SET name = ?, cep = ?, street = ?, number = ?, complement = ?, 
                        neighborhood = ?, city = ?, state = ?, is_default = ?
                    WHERE id = ? AND user_id = ?
                ", [$name, $cep, $street, $number, $complement, $neighborhood, $city, $state, $isDefault ? 1 : 0, $addressId, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço atualizado com sucesso'
                ]);
                break;
                
            case 'set_default':
                // Set address as default
                $addressId = $input['address_id'] ?? 0;
                
                // Check if address belongs to user
                $existingAddress = $db->fetch("
                    SELECT id FROM user_addresses WHERE id = ? AND user_id = ?
                ", [$addressId, $userId]);
                
                if (!$existingAddress) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Endereço não encontrado'
                    ]);
                    exit;
                }
                
                // Remove default from all addresses
                $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
                
                // Set this address as default
                $db->query("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?", [$addressId, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço padrão definido com sucesso'
                ]);
                break;
                
            case 'delete':
                // Delete address
                $addressId = $input['address_id'] ?? 0;
                
                // Check if address belongs to user
                $existingAddress = $db->fetch("
                    SELECT id, is_default FROM user_addresses WHERE id = ? AND user_id = ?
                ", [$addressId, $userId]);
                
                if (!$existingAddress) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Endereço não encontrado'
                    ]);
                    exit;
                }
                
                // Delete address
                $db->query("DELETE FROM user_addresses WHERE id = ? AND user_id = ?", [$addressId, $userId]);
                
                // If deleted address was default, set another address as default
                if ($existingAddress['is_default']) {
                    $db->query("
                        UPDATE user_addresses 
                        SET is_default = 1 
                        WHERE user_id = ? 
                        ORDER BY created_at ASC 
                        LIMIT 1
                    ", [$userId]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço excluído com sucesso'
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    error_log("Address API error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    error_log("Request data: " . print_r($_POST, true) . " | Input: " . file_get_contents('php://input'));
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
