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
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
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
                $addressId = $db->query("
                    INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [$userId, $name, $cep, $street, $number, $complement, $neighborhood, $city, $state, $isDefault ? 1 : 0]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço adicionado com sucesso',
                    'address_id' => $db->lastInsertId()
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
    error_log("Address API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>
