<?php
/**
 * Admin - User Addresses Management
 */

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';

$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_address') {
        $addressId = (int)$_POST['address_id'];
        try {
            $db->query("DELETE FROM user_addresses WHERE id = ?", [$addressId]);
            $message = 'Endereço excluído com sucesso!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao excluir endereço: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all addresses with user information
$addresses = $db->fetchAll("
    SELECT ua.*, u.name as user_name, u.email as user_email
    FROM user_addresses ua
    JOIN users u ON ua.user_id = u.id
    ORDER BY ua.created_at DESC
");

// Get statistics
$stats = [
    'total_addresses' => $db->fetch("SELECT COUNT(*) as count FROM user_addresses")['count'],
    'users_with_addresses' => $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM user_addresses")['count'],
    'default_addresses' => $db->fetch("SELECT COUNT(*) as count FROM user_addresses WHERE is_default = 1")['count']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Endereços - Admin Na Porta</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .address-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .address-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .default-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-store me-2"></i>Na Porta Admin
                    </h4>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link text-white" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link text-white" href="users.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                        <a class="nav-link text-white active" href="user_addresses.php">
                            <i class="fas fa-map-marker-alt me-2"></i>Endereços
                        </a>
                        <a class="nav-link text-white" href="products.php">
                            <i class="fas fa-box me-2"></i>Produtos
                        </a>
                        <a class="nav-link text-white" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <a class="nav-link text-white" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>Pedidos
                        </a>
                        <hr class="text-white">
                        <a class="nav-link text-white" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-map-marker-alt me-2"></i>Gerenciar Endereços</h2>
                    <a href="../account.php" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Ver Site
                    </a>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                            <h3 class="mb-1"><?= $stats['total_addresses'] ?></h3>
                            <p class="text-muted mb-0">Total de Endereços</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <i class="fas fa-users fa-2x text-success mb-2"></i>
                            <h3 class="mb-1"><?= $stats['users_with_addresses'] ?></h3>
                            <p class="text-muted mb-0">Usuários com Endereços</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h3 class="mb-1"><?= $stats['default_addresses'] ?></h3>
                            <p class="text-muted mb-0">Endereços Padrão</p>
                        </div>
                    </div>
                </div>
                
                <!-- Addresses List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Todos os Endereços
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum endereço cadastrado</h5>
                            <p class="text-muted">Os endereços dos usuários aparecerão aqui quando forem cadastrados.</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="address-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?= htmlspecialchars($address['name']) ?></h6>
                                        <?php if ($address['is_default']): ?>
                                        <span class="default-badge">Padrão</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-primary">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($address['user_name']) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($address['user_email']) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="address-details mb-3">
                                        <p class="mb-1">
                                            <strong><?= htmlspecialchars($address['street']) ?>, <?= htmlspecialchars($address['number']) ?></strong>
                                            <?php if ($address['complement']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($address['complement']) ?></small>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-1 text-muted">
                                            <?= htmlspecialchars($address['neighborhood']) ?>, 
                                            <?= htmlspecialchars($address['city']) ?>/<?= htmlspecialchars($address['state']) ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-map-pin me-1"></i>CEP: <?= htmlspecialchars($address['cep']) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($address['created_at'])) ?>
                                        </small>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este endereço?')">
                                            <input type="hidden" name="action" value="delete_address">
                                            <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
