<?php
// Working Shopping Cart Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Simple functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function format_currency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($product_id > 0 && $quantity > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                $success = "Produto adicionado ao carrinho!";
            }
            break;
            
        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($product_id > 0) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                $success = "Carrinho atualizado!";
            }
            break;
            
        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $success = "Produto removido do carrinho!";
            }
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            $success = "Carrinho limpo!";
            break;
            
        case 'checkout':
            if (!is_logged_in()) {
                $error = "Você precisa estar logado para finalizar a compra.";
                break;
            }
            
            if (empty($_SESSION['cart'])) {
                $error = "Seu carrinho está vazio.";
                break;
            }
            
            $delivery_address = trim($_POST['delivery_address'] ?? '');
            $payment_method = $_POST['payment_method'] ?? '';
            $notes = trim($_POST['notes'] ?? '');
            $address_option = $_POST['address_option'] ?? '';
            $save_new_address = isset($_POST['save_new_address']);
            $address_title = trim($_POST['address_title'] ?? '');
            
            // Handle address selection
            if (strpos($address_option, 'saved_') === 0) {
                // Using saved address - get it from database
                $address_id = (int)str_replace('saved_', '', $address_option);
                try {
                    $stmt_addr = $pdo->prepare("SELECT address FROM user_addresses WHERE id = ? AND user_id = ?");
                    $stmt_addr->execute([$address_id, $_SESSION['user_id']]);
                    $saved_addr = $stmt_addr->fetchColumn();
                    if ($saved_addr) {
                        $delivery_address = $saved_addr;
                    }
                } catch (Exception $e) {
                    // Continue with manual address
                }
            }
            
            if (empty($delivery_address) || empty($payment_method)) {
                $error = "Por favor, preencha todos os campos obrigatórios.";
                break;
            }
            
            try {
                // Force fix the orders table structure BEFORE starting transaction
                try {
                    // First, check if orders table exists and has wrong structure
                    $result = $pdo->query("SHOW TABLES LIKE 'orders'");
                    if ($result->rowCount() > 0) {
                        // Table exists, check if delivery_address column exists
                        $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'");
                        if ($result->rowCount() == 0) {
                            // Column missing, add it instead of dropping table
                            try {
                                $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_address TEXT NOT NULL AFTER total_amount");
                            } catch (Exception $e) {
                                // If ALTER fails, try to drop and recreate
                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                                $pdo->exec("DROP TABLE IF EXISTS order_items");
                                $pdo->exec("DROP TABLE IF EXISTS orders");
                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                            }
                        }
                    }
                    
                    // Create tables with correct structure
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS orders (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            total_amount DECIMAL(10,2) NOT NULL,
                            delivery_address TEXT NOT NULL,
                            payment_method VARCHAR(50) NOT NULL,
                            notes TEXT,
                            status VARCHAR(20) DEFAULT 'pending',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS order_items (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            order_id INT NOT NULL,
                            product_id INT NOT NULL,
                            quantity INT NOT NULL DEFAULT 1,
                            price DECIMAL(10,2) NOT NULL,
                            subtotal DECIMAL(10,2) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS user_addresses (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            title VARCHAR(100) NOT NULL,
                            address TEXT NOT NULL,
                            is_default BOOLEAN DEFAULT FALSE,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )
                    ");
                    
                } catch (Exception $e) {
                    throw new Exception("Database setup failed: " . $e->getMessage());
                }
                
                // NOW start transaction for the actual order processing
                $pdo->beginTransaction();
                
                // Prepare the insert statement
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, notes, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                // Calculate cart total before using it (ensure variables are available)
                if (empty($cart_total) || empty($cart_items)) {
                    // Recalculate cart items and total
                    $cart_items = [];
                    $cart_total = 0;
                    
                    if (!empty($_SESSION['cart'])) {
                        $product_ids = array_keys($_SESSION['cart']);
                        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                        
                        $stmt_temp = $pdo->prepare("
                            SELECT p.*, c.name as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.id IN ($placeholders) AND p.is_active = 1
                        ");
                        $stmt_temp->execute($product_ids);
                        $products = $stmt_temp->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($products as $product) {
                            $quantity = $_SESSION['cart'][$product['id']];
                            $subtotal = $product['price'] * $quantity;
                            
                            $cart_items[] = [
                                'product' => $product,
                                'quantity' => $quantity,
                                'subtotal' => $subtotal
                            ];
                            
                            $cart_total += $subtotal;
                        }
                    }
                    
                    // Add delivery fee if needed
                    if ($cart_total < 50 && $cart_total > 0) {
                        $cart_total += 8.00;
                    }
                }
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $cart_total,
                    $delivery_address,
                    $payment_method,
                    $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($cart_items as $item) {
                    $stmt->execute([
                        $order_id,
                        $item['product']['id'],
                        $item['quantity'],
                        $item['product']['price'],
                        $item['subtotal']
                    ]);
                }
                
                // Save new address if requested
                if ($save_new_address && !empty($address_title) && $address_option === 'new') {
                    try {
                        $stmt_save = $pdo->prepare("
                            INSERT INTO user_addresses (user_id, title, address, is_default, created_at)
                            VALUES (?, ?, ?, 0, NOW())
                        ");
                        $stmt_save->execute([$_SESSION['user_id'], $address_title, $delivery_address]);
                    } catch (Exception $e) {
                        // Don't fail the order if address save fails
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Redirect to success page
                header("Location: order-success.php?order_id=" . $order_id);
                exit();
                
            } catch (Exception $e) {
                // Only rollback if transaction is active
                if ($pdo->inTransaction()) {
                    $pdo->rollback();
                }
                $error = "Erro ao processar pedido: " . $e->getMessage();
            }
            break;
    }
}

// Get cart items with product details
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

if (!empty($_SESSION['cart'])) {
    try {
        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id IN ($placeholders) AND p.is_active = 1
        ");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $subtotal = $product['price'] * $quantity;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            
            $cart_total += $subtotal;
            $cart_count += $quantity;
        }
    } catch (Exception $e) {
        $error = "Erro ao carregar carrinho.";
    }
}

$page_title = 'Carrinho de Compras - Na Porta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .cart-item {
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            background: #f8f9fa;
        }
        
        .quantity-input {
            width: 80px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home-fixed.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products-working.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="cart-working.php">
                            <i class="fas fa-shopping-cart me-1"></i>Carrinho (<?php echo $cart_count; ?>)
                        </a>
                    </li>
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="account/profile-working.php">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="account/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login-working.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Entrar
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-2">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="home-fixed.php">Início</a></li>
                <li class="breadcrumb-item active">Carrinho</li>
            </ol>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Messages -->
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Cart Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Meu Carrinho</h2>
                    <?php if (!empty($cart_items)): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                onclick="return confirm('Tem certeza que deseja limpar o carrinho?')">
                            <i class="fas fa-trash me-2"></i>Limpar Carrinho
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Cart Items -->
                <?php if (empty($cart_items)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Seu carrinho está vazio</h5>
                        <p class="text-muted">Adicione produtos para começar suas compras</p>
                        <a href="products-working.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i>Continuar Comprando
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $index => $item): ?>
                        <div class="cart-item p-4 <?php echo $index > 0 ? 'border-top' : ''; ?>">
                            <div class="row align-items-center">
                                <!-- Product Image -->
                                <div class="col-md-2">
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 80px;">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="col-md-4">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['product']['category_name']); ?></small>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['product']['short_description']); ?></p>
                                </div>
                                
                                <!-- Price -->
                                <div class="col-md-2 text-center">
                                    <strong><?php echo format_currency($item['product']['price']); ?></strong>
                                </div>
                                
                                <!-- Quantity -->
                                <div class="col-md-2">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="99" class="form-control quantity-input text-center"
                                               onchange="this.form.submit()">
                                    </form>
                                </div>
                                
                                <!-- Subtotal -->
                                <div class="col-md-1 text-center">
                                    <strong class="text-primary"><?php echo format_currency($item['subtotal']); ?></strong>
                                </div>
                                
                                <!-- Remove -->
                                <div class="col-md-1 text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                onclick="return confirm('Remover este produto do carrinho?')"
                                                title="Remover produto">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Continue Shopping -->
                <div class="mt-3">
                    <a href="products-working.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Continuar Comprando
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <?php if (!empty($cart_items)): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-receipt me-2"></i>Resumo do Pedido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?>)</span>
                            <span><?php echo format_currency($cart_total); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frete</span>
                            <span class="text-success">
                                <?php if ($cart_total >= 50): ?>
                                Grátis
                                <?php else: ?>
                                <?php echo format_currency(8.00); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($cart_total < 50): ?>
                        <small class="text-muted">Frete grátis a partir de R$ 50,00</small>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong class="text-primary">
                                <?php 
                                $final_total = $cart_total + ($cart_total >= 50 ? 0 : 8.00);
                                echo format_currency($final_total); 
                                ?>
                            </strong>
                        </div>
                        
                        <?php if (is_logged_in()): ?>
                        <button class="btn btn-primary btn-lg w-100" data-mdb-toggle="modal" data-mdb-target="#checkoutModal">
                            <i class="fas fa-credit-card me-2"></i>Finalizar Compra
                        </button>
                        <?php else: ?>
                        <div class="d-grid gap-2">
                            <a href="auth/login-working.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar para Finalizar
                            </a>
                            <a href="auth/register-working.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Criar Conta
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Compra 100% segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-home me-2"></i>Na Porta</h5>
                    <p class="text-muted">Essenciais domésticos na sua porta</p>
                </div>
                <div class="col-md-4">
                    <h6>Links Úteis</h6>
                    <ul class="list-unstyled">
                        <li><a href="home-fixed.php" class="text-muted">Início</a></li>
                        <li><a href="products-working.php" class="text-muted">Produtos</a></li>
                        <li><a href="#" class="text-muted">Contato</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>Contato</h6>
                    <p class="text-muted mb-1">
                        <i class="fas fa-envelope me-2"></i><?php echo SITE_EMAIL; ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-phone me-2"></i>(11) 99999-9999
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="text-muted mb-0">© 2024 Na Porta. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-credit-card me-2"></i>Finalizar Compra
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="checkout">
                        
                        <!-- Order Summary -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>Resumo do Pedido
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product']['name']); ?></strong>
                                        <small class="text-muted"> x<?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span><?php echo format_currency($item['subtotal']); ?></span>
                                </div>
                                <?php endforeach; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total: <?php echo format_currency($final_total); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery Address -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega *
                            </label>
                            
                            <?php
                            // Get user saved addresses
                            $saved_addresses = [];
                            if (is_logged_in()) {
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $saved_addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) {
                                    // Ignore if table doesn't exist
                                }
                            }
                            ?>
                            
                            <?php if (!empty($saved_addresses)): ?>
                            <!-- Saved Addresses -->
                            <div class="mb-3">
                                <label class="form-label">Escolher endereço salvo:</label>
                                <?php foreach ($saved_addresses as $addr): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="address_option" 
                                           id="addr_<?php echo $addr['id']; ?>" value="saved_<?php echo $addr['id']; ?>"
                                           <?php echo $addr['is_default'] ? 'checked' : ''; ?>
                                           onchange="selectSavedAddress('<?php echo htmlspecialchars($addr['address']); ?>')">
                                    <label class="form-check-label" for="addr_<?php echo $addr['id']; ?>">
                                        <strong><?php echo htmlspecialchars($addr['title']); ?></strong>
                                        <?php if ($addr['is_default']): ?>
                                        <span class="badge bg-success ms-2">Padrão</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($addr['address']); ?></small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="address_option" 
                                           id="addr_new" value="new" onchange="selectNewAddress()">
                                    <label class="form-check-label" for="addr_new">
                                        <strong>Usar novo endereço</strong>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Address Input -->
                            <div id="address_input_div" <?php echo !empty($saved_addresses) ? 'style="display: none;"' : ''; ?>>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" 
                                          rows="3" placeholder="Rua, número, bairro, cidade..."
                                          <?php echo empty($saved_addresses) ? 'required' : ''; ?>></textarea>
                                          
                                <?php if (!empty($saved_addresses)): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="save_new_address" name="save_new_address">
                                    <label class="form-check-label" for="save_new_address">
                                        <i class="fas fa-save me-1"></i>Salvar este endereço para próximas compras
                                    </label>
                                </div>
                                <div id="address_title_div" style="display: none;" class="mt-2">
                                    <input type="text" class="form-control" id="address_title" name="address_title" 
                                           placeholder="Título do endereço (ex: Casa, Trabalho...)">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (empty($saved_addresses)): ?>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Você pode salvar endereços em <a href="account/addresses.php" target="_blank">Meus Endereços</a> para facilitar futuras compras.
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-credit-card me-2"></i>Forma de Pagamento *
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_money" value="money" required>
                                <label class="form-check-label" for="payment_money">
                                    <i class="fas fa-money-bill me-2"></i>Dinheiro
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_card" value="card" required>
                                <label class="form-check-label" for="payment_card">
                                    <i class="fas fa-credit-card me-2"></i>Cartão (Débito/Crédito)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_pix" value="pix" required>
                                <label class="form-check-label" for="payment_pix">
                                    <i class="fas fa-qrcode me-2"></i>PIX
                                </label>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                <i class="fas fa-comment me-2"></i>Observações
                            </label>
                            <textarea class="form-control" id="notes" name="notes" 
                                      rows="2" placeholder="Observações especiais para o pedido (opcional)"></textarea>
                        </div>
                        
                        <!-- Delivery Info -->
                        <div class="alert alert-info">
                            <i class="fas fa-truck me-2"></i>
                            <strong>Informações de Entrega:</strong><br>
                            • Entrega grátis para pedidos acima de R$ 50,00<br>
                            • Taxa de entrega: R$ 8,00 (pedidos abaixo de R$ 50,00)<br>
                            • Tempo estimado: 30-60 minutos
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Confirmar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        function updateQuantity(productId, quantity) {
            if (quantity < 0) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'update';
            
            const productInput = document.createElement('input');
            productInput.name = 'product_id';
            productInput.value = productId;
            
            const quantityInput = document.createElement('input');
            quantityInput.name = 'quantity';
            quantityInput.value = quantity;
            
            form.appendChild(actionInput);
            form.appendChild(productInput);
            form.appendChild(quantityInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function removeItem(productId) {
            if (confirm('Remover este item do carrinho?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'remove';
                
                const productInput = document.createElement('input');
                productInput.name = 'product_id';
                productInput.value = productId;
                
                form.appendChild(actionInput);
                form.appendChild(productInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Address selection functions
        function selectSavedAddress(address) {
            const addressInput = document.getElementById('delivery_address');
            const addressDiv = document.getElementById('address_input_div');
            
            addressInput.value = address;
            addressInput.removeAttribute('required');
            addressDiv.style.display = 'none';
        }
        
        function selectNewAddress() {
            const addressInput = document.getElementById('delivery_address');
            const addressDiv = document.getElementById('address_input_div');
            
            addressInput.value = '';
            addressInput.setAttribute('required', 'required');
            addressDiv.style.display = 'block';
        }
        
        // Toggle address title input when save checkbox is checked
        document.addEventListener('DOMContentLoaded', function() {
            const saveCheckbox = document.getElementById('save_new_address');
            const titleDiv = document.getElementById('address_title_div');
            
            if (saveCheckbox) {
                saveCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        titleDiv.style.display = 'block';
                        document.getElementById('address_title').setAttribute('required', 'required');
                    } else {
                        titleDiv.style.display = 'none';
                        document.getElementById('address_title').removeAttribute('required');
                    }
                });
            }
        });
    </script>
</body>
</html>
