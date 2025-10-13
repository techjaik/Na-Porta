<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' - Essenciais na sua porta'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Água, gás, produtos de limpeza e mercearia entregues na sua porta. Rápido, seguro e confiável.'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1976d2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Na Porta">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Essenciais domésticos na sua porta'; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Na Porta",
        "url": "<?php echo SITE_URL; ?>",
        "logo": "<?php echo SITE_URL; ?>/assets/images/logo.png",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+55-11-99999-9999",
            "contactType": "customer service",
            "availableLanguage": "Portuguese"
        },
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "BR"
        }
    }
    </script>
</head>
<body>
    <!-- LGPD Cookie Consent -->
    <?php if (!get_lgpd_consent()): ?>
    <div id="lgpd-banner" class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3" style="z-index: 9999;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <p class="mb-0">
                        <i class="fas fa-cookie-bite me-2"></i>
                        Este site utiliza cookies para melhorar sua experiência. Ao continuar navegando, você concorda com nossa 
                        <a href="<?php echo SITE_URL; ?>/pages/privacy.php" class="text-info">Política de Privacidade</a> e 
                        <a href="<?php echo SITE_URL; ?>/pages/terms.php" class="text-info">Termos de Uso</a>.
                    </p>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-primary btn-sm me-2" onclick="acceptLGPD()">Aceitar</button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="declineLGPD()">Recusar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>/pages/home.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/home.php">
                            <i class="fas fa-home me-1"></i>Início
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-th-large me-1"></i>Categorias
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products.php?category=agua">
                                <i class="fas fa-tint text-primary me-2"></i>Água
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products.php?category=gas">
                                <i class="fas fa-fire text-danger me-2"></i>Gás
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products.php?category=limpeza">
                                <i class="fas fa-spray-can text-success me-2"></i>Limpeza
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products.php?category=mercearia">
                                <i class="fas fa-shopping-basket text-warning me-2"></i>Mercearia
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/products.php">
                            <i class="fas fa-box me-1"></i>Produtos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/contact.php">
                            <i class="fas fa-envelope me-1"></i>Contato
                        </a>
                    </li>
                </ul>

                <!-- Search Bar -->
                <form class="d-flex me-3" action="<?php echo SITE_URL; ?>/pages/search.php" method="GET">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q" placeholder="Buscar produtos..." 
                               value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <!-- User Menu -->
                <ul class="navbar-nav">
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/pages/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php $cart_count = get_cart_count(); ?>
                            <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if (is_logged_in()): ?>
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>Minha Conta
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/profile.php">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/orders.php">
                                <i class="fas fa-box me-2"></i>Pedidos
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/addresses.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereços
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Login/Register -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Cadastrar
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
    <div class="container mt-3">
        <?php foreach ($flash_messages as $message): ?>
        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message['message']); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content"><?php // Content will be inserted here ?>
