<?php
/**
 * SUPER SIMPLE CHECKOUT - Just make it work!
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Simple database connection
$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            delivery_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Handle order submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        $address = trim($_POST['address'] ?? '');
        $payment = $_POST['payment_method'] ?? 'cash';
        
        if (empty($address)) {
            $error = 'Por favor, preencha o endereço de entrega.';
        } else {
            // Get cart items
            $cartStmt = $pdo->prepare("
                SELECT ci.*, p.name, p.price
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = ?
            ");
            $cartStmt->execute([$userId]);
            $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cartItems)) {
                $error = 'Seu carrinho está vazio.';
            } else {
                // Calculate total
                $total = 0;
                foreach ($cartItems as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
                
                // Create order
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $orderStmt->execute([$userId, $total, $address, $payment]);
                $orderId = $pdo->lastInsertId();
                
                // Create order items
                foreach ($cartItems as $item) {
                    $itemStmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                }
                
                // Clear cart
                $clearStmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $clearStmt->execute([$userId]);
                
                $success = "Pedido #$orderId criado com sucesso! Total: R$ " . number_format($total, 2, ',', '.');
            }
        }
    }
    
    // Get cart items for display
    $cartStmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $cartStmt->execute([$userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cartTotal = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
    
} catch (Exception $e) {
    $error = 'Erro: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Simples - Na Porta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-shopping-cart me-2"></i>Finalizar Pedido</h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                                <br><br>
                                <a href="products.php" class="btn btn-primary">Fazer Novo Pedido</a>
                                <a href="account.php" class="btn btn-secondary">Ver Meus Pedidos</a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($cartItems)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-shopping-cart me-2"></i>Seu carrinho está vazio.
                                <br><br>
                                <a href="products.php" class="btn btn-primary">Adicionar Produtos</a>
                            </div>
                        <?php else: ?>
                            
                            <h5>Seus Itens:</h5>
                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Quantidade</th>
                                            <th>Preço</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td>R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                                <td>R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-success">
                                            <th colspan="3">Total do Pedido:</th>
                                            <th>R$ <?= number_format($cartTotal, 2, ',', '.') ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="place_order" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Endereço de Entrega:</strong></label>
                                    <textarea name="address" class="form-control" rows="3" required 
                                              placeholder="Digite seu endereço completo: Rua, Número, Bairro, Cidade - Estado, CEP"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Forma de Pagamento:</strong></label>
                                    <div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="cash" id="cash" checked>
                                            <label class="form-check-label" for="cash">
                                                <i class="fas fa-money-bill-wave me-2"></i>Dinheiro na Entrega
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="card">
                                            <label class="form-check-label" for="card">
                                                <i class="fas fa-credit-card me-2"></i>Cartão na Entrega
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="cart.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Carrinho
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check me-2"></i>Finalizar Pedido
                                    </button>
                                </div>
                            </form>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
