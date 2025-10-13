<?php
// Working User Orders Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Simple functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: ../auth/login-working.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get user data
$user = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: ../auth/login-working.php');
        exit();
    }
} catch (Exception $e) {
    $error = "Erro ao carregar dados do usuário.";
}

$page_title = 'Meus Pedidos - Na Porta';
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../home-fixed.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products-working.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../cart-working.php">
                            <i class="fas fa-shopping-cart me-1"></i>Carrinho
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile-working.php">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><a class="dropdown-item active" href="orders-working.php">
                                <i class="fas fa-box me-2"></i>Pedidos
                            </a></li>
                            <li><a class="dropdown-item" href="addresses.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereços
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-2">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../home-fixed.php">Início</a></li>
                <li class="breadcrumb-item"><a href="profile-working.php">Minha Conta</a></li>
                <li class="breadcrumb-item active">Pedidos</li>
            </ol>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Minha Conta
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profile-working.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i>Perfil
                        </a>
                        <a href="orders-working.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-box me-2"></i>Pedidos
                        </a>
                        <a href="addresses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i>Endereços
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>

            <!-- Orders Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Meus Pedidos</h2>
                    <a href="../products-working.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Fazer Novo Pedido
                    </a>
                </div>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div></div>
                </div>

                <!-- Orders List -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php
                            // Handle order cancellation
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
                                $order_id = (int)($_POST['order_id'] ?? 0);
                                
                                if ($order_id > 0) {
                                    try {
                                        // Check if order belongs to user and can be cancelled
                                        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
                                        $stmt->execute([$order_id, $_SESSION['user_id']]);
                                        $order_status = $stmt->fetchColumn();
                                        
                                        if ($order_status && in_array($order_status, ['pending', 'confirmed'])) {
                                            // Update order status to cancelled
                                            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
                                            if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
                                                $success = "Pedido cancelado com sucesso!";
                                            } else {
                                                $error = "Erro ao cancelar pedido.";
                                            }
                                        } else {
                                            $error = "Este pedido não pode ser cancelado.";
                                        }
                                    } catch (Exception $e) {
                                        $error = "Erro ao cancelar pedido: " . $e->getMessage();
                                    }
                                }
                            }
                            
                            // Get user orders
                            $orders = [];
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT o.*, COUNT(oi.id) as item_count
                                    FROM orders o
                                    LEFT JOIN order_items oi ON o.id = oi.order_id
                                    WHERE o.user_id = ?
                                    GROUP BY o.id
                                    ORDER BY o.created_at DESC
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {
                                // Ignore errors if tables don't exist yet
                            }
                            
                            if (empty($orders)):
                            ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>Nenhum pedido encontrado</h5>
                                <p class="text-muted">Você ainda não fez nenhum pedido.</p>
                                <a href="../products-working.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Fazer Primeiro Pedido
                                </a>
                            </div>
                            <?php else: ?>
                            <!-- Orders List -->
                            <?php foreach ($orders as $order): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <h6 class="mb-1">Pedido #<?php echo $order['id']; ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge bg-<?php 
                                                $status_colors = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'preparing' => 'primary',
                                                    'delivering' => 'secondary',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                echo $status_colors[$order['status']] ?? 'secondary';
                                            ?>">
                                                <?php
                                                $status_names = [
                                                    'pending' => 'Pendente',
                                                    'confirmed' => 'Confirmado',
                                                    'preparing' => 'Preparando',
                                                    'delivering' => 'Entregando',
                                                    'delivered' => 'Entregue',
                                                    'cancelled' => 'Cancelado'
                                                ];
                                                echo $status_names[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?></small>
                                        </div>
                                        <div class="col-md-3">
                                            <strong class="text-primary">
                                                R$ <?php echo number_format($order['total_amount'], 2, ',', '.'); ?>
                                            </strong>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="../order-success.php?order_id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Ver Detalhes
                                                </a>
                                                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Cancelar
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                    </div>
                </div>

                <!-- Order Status Info -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Status dos Pedidos
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-warning me-3">Pendente</span>
                                    <small class="text-muted">Aguardando confirmação</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info me-3">Preparando</span>
                                    <small class="text-muted">Separando produtos</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary me-3">Saiu para entrega</span>
                                    <small class="text-muted">A caminho do destino</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success me-3">Entregue</span>
                                    <small class="text-muted">Pedido finalizado</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-truck me-2"></i>Entrega
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    <strong>Horário de entrega:</strong> 8h às 18h
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-calendar me-2 text-primary"></i>
                                    <strong>Prazo:</strong> 1 a 2 dias úteis
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-shipping-fast me-2 text-primary"></i>
                                    <strong>Frete grátis</strong> acima de R$ 50,00
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                        <li><a href="../home-fixed.php" class="text-muted">Início</a></li>
                        <li><a href="../products-working.php" class="text-muted">Produtos</a></li>
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

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Cancelar Pedido
                    </h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja cancelar este pedido?</p>
                    <p class="text-muted">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Não, manter pedido
                    </button>
                    <form method="POST" style="display: inline;" id="cancelOrderForm">
                        <input type="hidden" name="action" value="cancel_order">
                        <input type="hidden" name="order_id" id="cancelOrderId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-2"></i>Sim, cancelar pedido
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
    function cancelOrder(orderId) {
        document.getElementById('cancelOrderId').value = orderId;
        const modal = new mdb.Modal(document.getElementById('cancelOrderModal'));
        modal.show();
    }
    </script>
</body>
</html>
