<?php
/**
 * Na Porta - User Orders Page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Redirect to login if not authenticated
if (!$user) {
    header('Location: ../auth/login.php?redirect=account/orders.php');
    exit();
}

// Get user orders
$orders = [];
try {
    $orders = $db->fetchAll("
        SELECT o.*, COUNT(oi.id) as item_count,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items_summary
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ", [$user['id']]);
} catch (Exception $e) {
    error_log("User orders error: " . $e->getMessage());
}

// Get order details if requested
$orderDetails = null;
$orderItems = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $orderId = intval($_GET['view']);
    try {
        $orderDetails = $db->fetch("
            SELECT * FROM orders 
            WHERE id = ? AND user_id = ?
        ", [$orderId, $user['id']]);
        
        if ($orderDetails) {
            $orderItems = $db->fetchAll("
                SELECT oi.*, p.name, p.image_url
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.created_at ASC
            ", [$orderId]);
        }
    } catch (Exception $e) {
        error_log("Order details error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - Na Porta</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --gray-50: #f9fafb;
            --gray-800: #1f2937;
            --border-radius: 12px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .orders-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
        }
        
        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
        }
        
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-pending { background-color: var(--warning-color); color: white; }
        .status-processing { background-color: var(--primary-color); color: white; }
        .status-shipped { background-color: var(--secondary-color); color: white; }
        .status-delivered { background-color: var(--success-color); color: white; }
        .status-cancelled { background-color: var(--danger-color); color: white; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="../cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" id="cart-count">
                                0
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Meu Perfil</a></li>
                            <li><a class="dropdown-item active" href="orders.php">Meus Pedidos</a></li>
                            <li><a class="dropdown-item" href="../account.php">Minha Conta</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title mb-2">Meus Pedidos</h1>
                    <p class="lead mb-0">Acompanhe o status dos seus pedidos</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        <i class="fas fa-shopping-bag fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Orders Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($orderDetails): ?>
                <!-- Order Details Modal -->
                <div class="orders-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Detalhes do Pedido #<?= $orderDetails['id'] ?>
                        </h5>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informações do Pedido</h6>
                            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($orderDetails['created_at'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?= $orderDetails['status'] ?>">
                                    <?= ucfirst($orderDetails['status']) ?>
                                </span>
                            </p>
                            <p><strong>Total:</strong> <span class="text-success">R$ <?= number_format($orderDetails['total_amount'], 2, ',', '.') ?></span></p>
                            <p><strong>Pagamento:</strong> <?= ucfirst(str_replace('_', ' ', $orderDetails['payment_method'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Endereço de Entrega</h6>
                            <p><?= nl2br(htmlspecialchars($orderDetails['delivery_address'] ?? '')) ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Itens do Pedido</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                                         class="me-2 rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($item['name']) ?>
                                            </div>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhum pedido encontrado</h4>
                        <p class="text-muted">Você ainda não fez nenhum pedido.</p>
                        <a href="../products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Começar a Comprar
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($orders as $order): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="order-card p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1">Pedido #<?= $order['id'] ?></h6>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </small>
                                        </div>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Itens:</small><br>
                                        <small><?= htmlspecialchars($order['items_summary'] ?? $order['item_count'] . ' item(s)') ?></small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-success">
                                                R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                                            </strong>
                                        </div>
                                        <div>
                                            <a href="orders.php?view=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="../products.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Fazer Novo Pedido
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update cart count
        function updateCartCount() {
            fetch('../api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }
        
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>
