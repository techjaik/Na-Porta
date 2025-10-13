<?php
// Order Success Page
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

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: auth/login-working.php');
    exit();
}

// Get order ID
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: cart-working.php');
    exit();
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: cart-working.php');
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Erro ao carregar pedido.";
}

$page_title = 'Pedido Confirmado - Na Porta';
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
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
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
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="account/profile-working.php">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Success Content -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Success Message -->
                    <div class="text-center mb-5">
                        <i class="fas fa-check-circle success-icon mb-3"></i>
                        <h1 class="text-success mb-3">Pedido Confirmado!</h1>
                        <p class="lead text-muted">
                            Seu pedido foi recebido e está sendo preparado. 
                            Você receberá atualizações por email.
                        </p>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i>Detalhes do Pedido #<?php echo $order['id']; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-calendar me-2"></i>Data do Pedido</h6>
                                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-credit-card me-2"></i>Forma de Pagamento</h6>
                                    <p class="text-muted">
                                        <?php 
                                        $payment_methods = [
                                            'money' => 'Dinheiro',
                                            'card' => 'Cartão',
                                            'pix' => 'PIX'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6><i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                            </div>
                            
                            <?php if ($order['notes']): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-comment me-2"></i>Observações</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>Itens do Pedido
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($order_items as $item): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                    <small class="text-muted">
                                        Quantidade: <?php echo $item['quantity']; ?> × 
                                        <?php echo format_currency($item['price']); ?>
                                    </small>
                                </div>
                                <div>
                                    <strong><?php echo format_currency($item['subtotal']); ?></strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h5>Total do Pedido</h5>
                                <h5 class="text-primary"><?php echo format_currency($order['total_amount']); ?></h5>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Info -->
                    <div class="alert alert-info">
                        <i class="fas fa-truck me-2"></i>
                        <strong>Informações de Entrega:</strong><br>
                        • Tempo estimado: 30-60 minutos<br>
                        • Status: Pedido confirmado e sendo preparado<br>
                        • Você receberá atualizações por email
                    </div>
                    
                    <!-- Actions -->
                    <div class="text-center">
                        <a href="home-fixed.php" class="btn btn-primary me-3">
                            <i class="fas fa-home me-2"></i>Voltar ao Início
                        </a>
                        <a href="account/orders-working.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Meus Pedidos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
