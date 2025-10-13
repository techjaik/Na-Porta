<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin - <?php echo SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="<?php echo SITE_URL; ?>/admin/assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>/admin/">
                <i class="fas fa-cog me-2"></i><?php echo SITE_NAME; ?> Admin
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#adminNav">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-box me-1"></i>Produtos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/products.php">
                                <i class="fas fa-list me-2"></i>Listar Produtos
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/products.php?action=add">
                                <i class="fas fa-plus me-2"></i>Adicionar Produto
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/categories.php">
                                <i class="fas fa-tags me-2"></i>Categorias
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/orders.php">
                            <i class="fas fa-shopping-cart me-1"></i>Pedidos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/users.php">
                            <i class="fas fa-users me-1"></i>Usuários
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-tools me-1"></i>Marketing
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/coupons.php">
                                <i class="fas fa-ticket-alt me-2"></i>Cupons
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/newsletter.php">
                                <i class="fas fa-envelope me-2"></i>Newsletter
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/support.php">
                            <i class="fas fa-headset me-1"></i>Suporte
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Relatórios
                        </a>
                    </li>
                </ul>

                <!-- Right Side Menu -->
                <ul class="navbar-nav">
                    <!-- View Site -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/home.php" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>Ver Site
                        </a>
                    </li>
                    
                    <!-- Admin User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php">
                                <i class="fas fa-user me-2"></i>Meu Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
    <div class="container-fluid mt-3">
        <?php foreach ($flash_messages as $message): ?>
        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message['message']); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="admin-main"><?php // Content will be inserted here ?>
