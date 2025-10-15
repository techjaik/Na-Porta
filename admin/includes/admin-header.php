<?php
/**
 * Na Porta - Unified Admin Header
 * Consistent styling and navigation for all admin pages
 */

if (!isset($auth) || !$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$admin = $auth->getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> - Na Porta</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* Modern Color Palette */
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            /* Neutral Colors */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Layout */
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-subtitle {
            font-size: 0.875rem;
            opacity: 0.8;
            font-weight: 300;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 0;
            position: relative;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            border-right: 3px solid white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }
        
        /* Main Content */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--gray-50);
        }
        
        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            background: white;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-info {
            background-color: var(--info-color);
            color: white;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--gray-50);
            border-bottom: 2px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table-hover tbody tr:hover {
            background-color: var(--gray-50);
        }
        
        /* Forms */
        .form-control {
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }
        
        .alert-info {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border-left: 4px solid var(--info-color);
        }
        
        /* Page Title */
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-content {
                padding: 1rem;
            }
        }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">Na Porta</div>
            <div class="sidebar-subtitle">Painel Administrativo</div>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i>
                    Produtos
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    Categorias
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    Usuários
                </a>
            </li>
            <li class="nav-item">
                <a href="banners.php" class="nav-link <?= $currentPage === 'banners' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i>
                    Banners
                </a>
            </li>
            <li class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="../index.php" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title"><?= $pageTitle ?? 'Admin' ?></h1>
                    <?php if (isset($pageSubtitle)): ?>
                        <p class="page-subtitle"><?= $pageSubtitle ?></p>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($admin['name']) ?>
                    </span>
                    <span class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </header>
        
        <main class="admin-content"><?php
