<?php
/**
 * Na Porta - Clean Cart API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$db = Database::getInstance();

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            addToCart($db, $auth, $input);
            break;
            
        case 'update':
            updateCart($db, $auth, $input);
            break;
            
        case 'remove':
            removeFromCart($db, $auth, $input);
            break;
            
        case 'get':
            getCart($db, $auth);
            break;
            
        case 'count':
            getCartCount($db, $auth);
            break;
            
        case 'clear':
            clearCart($db, $auth);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function addToCart($db, $auth, $input) {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    
    if ($productId <= 0 || $quantity <= 0) {
        throw new Exception('Dados inválidos');
    }
    
    // Check if product exists and is active
    $product = $db->fetch("SELECT * FROM products WHERE id = ? AND is_active = 1", [$productId]);
    if (!$product) {
        throw new Exception('Produto não encontrado');
    }
    
    // Get user ID or session ID
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    if (!$userId && !$sessionId) {
        session_start();
        $sessionId = session_id();
    }
    
    // Check if item already exists in cart
    $whereClause = $userId ? "user_id = ?" : "session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $existingItem = $db->fetch(
        "SELECT * FROM cart_items WHERE {$whereClause} AND product_id = ?",
        [$whereParam, $productId]
    );
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        $db->query(
            "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?",
            [$newQuantity, $existingItem['id']]
        );
    } else {
        // Add new item
        $db->query(
            "INSERT INTO cart_items (user_id, session_id, product_id, quantity, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$userId, $sessionId, $productId, $quantity]
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Produto adicionado ao carrinho'
    ]);
}

function updateCart($db, $auth, $input) {
    $itemId = (int)($input['item_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    
    if ($itemId <= 0 || $quantity < 0) {
        throw new Exception('Dados inválidos');
    }
    
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    // Verify item belongs to user/session
    $whereClause = $userId ? "user_id = ?" : "session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $item = $db->fetch(
        "SELECT * FROM cart_items WHERE id = ? AND {$whereClause}",
        [$itemId, $whereParam]
    );
    
    if (!$item) {
        throw new Exception('Item não encontrado no carrinho');
    }
    
    if ($quantity === 0) {
        // Remove item
        $db->query("DELETE FROM cart_items WHERE id = ?", [$itemId]);
    } else {
        // Update quantity
        $db->query(
            "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?",
            [$quantity, $itemId]
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Carrinho atualizado'
    ]);
}

function removeFromCart($db, $auth, $input) {
    $itemId = (int)($input['item_id'] ?? 0);
    
    if ($itemId <= 0) {
        throw new Exception('ID do item inválido');
    }
    
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $whereClause = $userId ? "user_id = ?" : "session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $result = $db->query(
        "DELETE FROM cart_items WHERE id = ? AND {$whereClause}",
        [$itemId, $whereParam]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Item removido do carrinho'
    ]);
}

function getCart($db, $auth) {
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $whereClause = $userId ? "ci.user_id = ?" : "ci.session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $items = $db->fetchAll("
        SELECT ci.*, p.name, p.price, p.image_url as image, p.description,
               (ci.quantity * p.price) as subtotal
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE {$whereClause} AND p.is_active = 1
        ORDER BY ci.created_at DESC
    ", [$whereParam]);
    
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
}

function getCartCount($db, $auth) {
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $whereClause = $userId ? "ci.user_id = ?" : "ci.session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $result = $db->fetch("
        SELECT SUM(ci.quantity) as count
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE {$whereClause} AND p.is_active = 1
    ", [$whereParam]);
    
    echo json_encode([
        'success' => true,
        'count' => (int)($result['count'] ?? 0)
    ]);
}

function clearCart($db, $auth) {
    $userId = $auth->isUserLoggedIn() ? $auth->getCurrentUser()['id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $whereClause = $userId ? "user_id = ?" : "session_id = ?";
    $whereParam = $userId ?: $sessionId;
    
    $db->query("DELETE FROM cart_items WHERE {$whereClause}", [$whereParam]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Carrinho limpo'
    ]);
}
?>
