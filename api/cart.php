<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);

if (!$action || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    // Get user/session identifier
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = $user_id ? null : session_id();
    
    switch ($action) {
        case 'add':
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
                exit;
            }
            
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Estoque insuficiente']);
                exit;
            }
            
            // Check if item already exists in cart
            if ($user_id) {
                $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$session_id, $product_id]);
            }
            
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_item) {
                // Update existing item
                $new_quantity = $existing_item['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Quantidade excede o estoque disponível']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_quantity, $existing_item['id']]);
            } else {
                // Add new item
                if ($user_id) {
                    $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $product_id, $quantity]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$session_id, $product_id, $quantity]);
                }
            }
            break;
            
        case 'update':
            if ($quantity < 1) {
                echo json_encode(['success' => false, 'message' => 'Quantidade inválida']);
                exit;
            }
            
            // Check stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Estoque insuficiente']);
                exit;
            }
            
            // Update cart item
            if ($user_id) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $user_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $session_id, $product_id]);
            }
            break;
            
        case 'remove':
            // Remove item from cart
            if ($user_id) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$session_id, $product_id]);
            }
            break;
            
        case 'clear':
            // Clear entire cart
            if ($user_id) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?");
                $stmt->execute([$session_id]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            exit;
    }
    
    // Get updated cart totals
    $cart_count = get_cart_count();
    $cart_total = get_cart_total();
    
    // Calculate item total for update action
    $item_total = 0;
    if ($action === 'update') {
        $stmt = $pdo->prepare("SELECT p.price FROM products p WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product_price = $stmt->fetchColumn();
        $item_total = $product_price * $quantity;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Carrinho atualizado com sucesso',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total,
        'item_total' => $item_total
    ]);
    
} catch (PDOException $e) {
    error_log('Cart API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>
