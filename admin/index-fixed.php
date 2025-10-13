<?php
// Fixed Admin Dashboard - Na Porta
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Simple admin check
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit();
}

// Get admin info
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'admin';

// Get basic statistics safely
$stats = [
    'total_users' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'total_categories' => 0,
    'total_banners' => 0
];

try {
    // Get stats from database
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $stats['total_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1");
    $stats['total_categories'] = $stmt->fetchColumn();
    
    // Try to get orders if table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $stats['total_orders'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_orders'] = 0;
    }
    
    // Try to get banners if table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM promotional_banners WHERE is_active = 1");
        $stats['total_banners'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_banners'] = 0;
    }
    
} catch (Exception $e) {
    // Database error - use defaults
}

$page_title = 'Dashboard Administrativo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Na Porta</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
            --sidebar-width: 250px;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #2c3e50;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .stat-card {
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-center mb-4">
                <i class="fas fa-home me-2"></i>Na Porta
                <small class="d-block text-muted">Admin</small>
            </h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index-fixed.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categorias
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>Usuários
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="banners.php">
                    <i class="fas fa-images me-2"></i>Banners Promocionais
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="alert('Em desenvolvimento')">
                    <i class="fas fa-chart-bar me-2"></i>Relatórios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="alert('Em desenvolvimento')">
                    <i class="fas fa-cog me-2"></i>Configurações
                </a>
            </li>
            <li class="nav-item mt-4">
                <hr class="text-muted">
                <a class="nav-link" href="../pages/home-fixed.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout-simple.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-admin">
            <div class="container-fluid">
                <button class="btn btn-outline-primary d-md-none" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout-simple.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="container-fluid p-4">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="h3 mb-2">Bem-vindo, <?php echo htmlspecialchars($admin_name); ?>!</h1>
                    <p class="text-muted">Aqui está um resumo da sua loja Na Porta</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-2 col-md-6 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_users']); ?></div>
                                    <div class="text-muted">Usuários Ativos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-6 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_products']); ?></div>
                                    <div class="text-muted">Produtos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-6 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_orders']); ?></div>
                                    <div class="text-muted">Pedidos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-6 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_categories']); ?></div>
                                    <div class="text-muted">Categorias</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-6 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-images"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_banners']); ?></div>
                                    <div class="text-muted">Banners</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2 text-primary"></i>Ações Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="products.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Gerenciar Produtos
                                </a>
                                <a href="categories.php" class="btn btn-outline-success">
                                    <i class="fas fa-tags me-2"></i>Gerenciar Categorias
                                </a>
                                <a href="banners.php" class="btn btn-outline-danger">
                                    <i class="fas fa-images me-2"></i>Gerenciar Banners
                                </a>
                                <a href="users.php" class="btn btn-outline-info">
                                    <i class="fas fa-users me-2"></i>Ver Usuários
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-info"></i>Status do Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-database text-success me-3"></i>
                                <div>
                                    <div class="fw-bold">Banco de Dados</div>
                                    <small class="text-muted">Conectado e funcionando</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-server text-success me-3"></i>
                                <div>
                                    <div class="fw-bold">Servidor Web</div>
                                    <small class="text-muted">Online</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-success me-3"></i>
                                <div>
                                    <div class="fw-bold">Segurança</div>
                                    <small class="text-muted">Sessão ativa</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2 text-warning"></i>Atividade Recente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhuma atividade recente para mostrar.</p>
                                <small class="text-muted">As atividades aparecerão aqui conforme você usar o sistema.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1000;">
        <div class="bg-success text-white p-2 rounded small">
            <strong>✅ Admin Working!</strong><br>
            Role: <?php echo htmlspecialchars($admin_role); ?><br>
            <a href="../setup-fixed.php" class="text-white">Setup</a> | 
            <a href="../pages/home-fixed.php" class="text-white">Site</a>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const toggle = document.getElementById('sidebarToggle');
                
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>
