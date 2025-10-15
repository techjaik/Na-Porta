<?php
/**
 * AJAX endpoint to update user information
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
$gender = $_POST['gender'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validation
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if user exists
    $existingUser = $db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);
    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Check which columns exist in the users table (dynamic approach)
    $pdo = $db->getConnection();
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    // Build dynamic query based on existing columns
    $updateFields = ['name = ?'];
    $updateValues = [$name];
    
    // Add optional fields only if they exist in the table
    $optionalFields = [
        'phone' => $phone,
        'cpf_cnpj' => $cpf_cnpj,
        'gender' => $gender,
        'birth_date' => $birth_date ?: null,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip_code' => $zip_code,
        'is_active' => $is_active
    ];
    
    foreach ($optionalFields as $field => $value) {
        if (in_array($field, $existingColumns)) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
        }
    }
    
    // Add updated_at if it exists
    if (in_array('updated_at', $existingColumns)) {
        $updateFields[] = "updated_at = NOW()";
    }
    
    // Add user ID for WHERE clause
    $updateValues[] = $userId;
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $db->query($sql, $updateValues);
    
    // Log the action
    $admin = $auth->getCurrentAdmin();
    error_log("Admin {$admin['username']} updated user ID {$userId}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuário atualizado com sucesso!'
    ]);
    
} catch (Exception $e) {
    error_log("Error updating user: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar usuário. Tente novamente.'
    ]);
}
?>
