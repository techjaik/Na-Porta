<?php
// Fixed Home Page - Na Porta
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Simple includes without complex functions
require_once '../config/config.php';
require_once '../config/database.php';

// Simple functions
function format_currency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get products, categories, and promotional banners safely
$products = [];
$categories = [];
$promotional_banners = [];

try {
    // Get categories
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get featured products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 LIMIT 4");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get promotional banners
    $stmt = $pdo->prepare("SELECT * FROM promotional_banners WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute();
    $promotional_banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use sample data if database fails
    $categories = [
        ['id' => 1, 'name' => 'Água', 'slug' => 'agua', 'description' => 'Água mineral e galões'],
        ['id' => 2, 'name' => 'Gás', 'slug' => 'gas', 'description' => 'Botijões de gás de cozinha'],
        ['id' => 3, 'name' => 'Limpeza', 'slug' => 'limpeza', 'description' => 'Produtos de limpeza doméstica'],
        ['id' => 4, 'name' => 'Mercearia', 'slug' => 'mercearia', 'description' => 'Itens básicos de mercearia']
    ];
    
    $products = [
        ['id' => 1, 'name' => 'Água Mineral 20L', 'price' => 15.90, 'short_description' => 'Água mineral natural 20L', 'stock_quantity' => 50],
        ['id' => 2, 'name' => 'Botijão de Gás 13kg', 'price' => 85.00, 'short_description' => 'Botijão de gás 13kg', 'stock_quantity' => 25],
        ['id' => 3, 'name' => 'Kit Limpeza Completo', 'price' => 45.50, 'short_description' => 'Kit limpeza completo', 'stock_quantity' => 30],
        ['id' => 4, 'name' => 'Cesta Básica Familiar', 'price' => 120.00, 'short_description' => 'Cesta básica familiar', 'stock_quantity' => 15]
    ];
}

$page_title = 'Início - Na Porta';
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
            --secondary-color: #424242;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #1e88e5);
            color: white;
            padding: 80px 0;
        }
        
        .category-card {
            transition: transform 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            text-decoration: none;
            color: inherit;
        }
        
        .product-card {
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Promotional Banner Styles */
        .promotional-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        
        .banner-slider {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .banner-slide {
            display: none;
            position: relative;
            min-height: 300px;
            background-size: cover;
            background-position: center;
            border-radius: 15px;
        }
        
        .banner-slide.active {
            display: block;
        }
        
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.7), rgba(0,0,0,0.3));
            border-radius: 15px;
        }
        
        .banner-content {
            position: absolute;
            top: 50%;
            left: 50px;
            transform: translateY(-50%);
            color: white;
            z-index: 2;
            max-width: 500px;
        }
        
        .banner-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .banner-description {
            font-size: 1.2rem;
            margin-bottom: 25px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .banner-btn {
            background: #ff6b35;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
        }
        
        .banner-btn:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
            color: white;
            text-decoration: none;
        }
        
        .banner-nav {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }
        
        .banner-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .banner-dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        .banner-arrows {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .banner-arrows:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        .banner-prev {
            left: 20px;
        }
        
        .banner-next {
            right: 20px;
        }
        
        @media (max-width: 768px) {
            .banner-content {
                left: 20px;
                right: 20px;
                max-width: none;
            }
            
            .banner-title {
                font-size: 1.8rem;
            }
            
            .banner-description {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>/pages/home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo SITE_URL; ?>/pages/home-fixed.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products-working.php">Produtos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#como-funciona">Como Funciona</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="cart-working.php">
                            <i class="fas fa-shopping-cart me-1"></i>Carrinho (0)
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
                            <li><a class="dropdown-item" href="account/orders-working.php">
                                <i class="fas fa-box me-2"></i>Pedidos
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 fade-in">
                    <h1 class="display-4 fw-bold mb-4">Essenciais na sua porta</h1>
                    <p class="lead mb-4">
                        Água, gás, produtos de limpeza e mercearia entregues com segurança e praticidade. 
                        Sem sair de casa, sem complicação.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="products-working.php" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>Explorar Produtos
                        </a>
                        <a href="#como-funciona" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-play me-2"></i>Como Funciona
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-truck display-1 opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Promotional Banners Section -->
    <?php if (!empty($promotional_banners)): ?>
    <section class="promotional-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Ofertas Especiais</h2>
                <p class="text-muted">Não perca nossas promoções e descontos exclusivos</p>
            </div>
            
            <div class="banner-slider" id="bannerSlider">
                <?php foreach ($promotional_banners as $index => $banner): ?>
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: url('<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($banner['file_path']); ?>')">
                    
                    <?php if ($banner['file_type'] === 'video'): ?>
                    <video autoplay muted loop style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                        <source src="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($banner['file_path']); ?>" type="video/mp4">
                    </video>
                    <?php endif; ?>
                    
                    <div class="banner-overlay"></div>
                    
                    <div class="banner-content">
                        <h3 class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></h3>
                        <?php if (!empty($banner['description'])): ?>
                        <p class="banner-description"><?php echo htmlspecialchars($banner['description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($banner['link_url'])): ?>
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="banner-btn">
                            <i class="fas fa-shopping-cart me-2"></i>Comprar Agora
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($promotional_banners) > 1): ?>
                <!-- Navigation Arrows -->
                <button class="banner-arrows banner-prev" onclick="changeBanner(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="banner-arrows banner-next" onclick="changeBanner(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Navigation Dots -->
                <div class="banner-nav">
                    <?php foreach ($promotional_banners as $index => $banner): ?>
                    <div class="banner-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                         onclick="goToBanner(<?php echo $index; ?>)"></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categories Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Nossas Categorias</h2>
                <p class="text-muted">Encontre tudo que você precisa para sua casa</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-3 col-md-6">
                    <a href="products-working.php?category=<?php echo urlencode($category['slug']); ?>" class="category-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <?php
                                    $icons = [
                                        'agua' => 'fas fa-tint text-primary',
                                        'gas' => 'fas fa-fire text-danger',
                                        'limpeza' => 'fas fa-spray-can text-success',
                                        'mercearia' => 'fas fa-shopping-basket text-warning'
                                    ];
                                    $icon_class = $icons[$category['slug']] ?? 'fas fa-box text-secondary';
                                    ?>
                                    <i class="<?php echo $icon_class; ?> fa-3x"></i>
                                </div>
                                <h5 class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section id="produtos" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Produtos em Destaque</h2>
                <p class="text-muted">Os mais pedidos pelos nossos clientes</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <?php if ($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="img-fluid" style="max-height: 180px; object-fit: contain;">
                            <?php else: ?>
                            <i class="fas fa-image fa-3x text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars($product['short_description']); ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="h5 text-primary mb-0">
                                        <?php echo format_currency($product['price']); ?>
                                    </span>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> Disponível
                                    </small>
                                    <?php else: ?>
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle"></i> Esgotado
                                    </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus me-2"></i>Adicionar
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-times me-2"></i>Indisponível
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="#" class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="como-funciona" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Como Funciona</h2>
                <p class="text-muted">Simples, rápido e seguro</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 text-center">
                    <div class="mb-4">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-search fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">1. Escolha</h5>
                    <p class="text-muted">Navegue pelas categorias e escolha os produtos que precisa</p>
                </div>
                
                <div class="col-lg-3 col-md-6 text-center">
                    <div class="mb-4">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">2. Peça</h5>
                    <p class="text-muted">Adicione ao carrinho e finalize seu pedido com PIX</p>
                </div>
                
                <div class="col-lg-3 col-md-6 text-center">
                    <div class="mb-4">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">3. Acompanhe</h5>
                    <p class="text-muted">Receba notificações em tempo real sobre seu pedido</p>
                </div>
                
                <div class="col-lg-3 col-md-6 text-center">
                    <div class="mb-4">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-truck fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">4. Receba</h5>
                    <p class="text-muted">Entrega rápida e segura na sua porta</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">Pronto para começar?</h2>
            <p class="lead mb-4">
                Junte-se a milhares de clientes satisfeitos e tenha seus essenciais sempre em casa.
            </p>
            <?php if (!is_logged_in()): ?>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="auth/register-working.php" class="btn btn-light btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Criar Conta
                </a>
                <a href="products-working.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Comprar Agora
                </a>
            </div>
            <?php else: ?>
            <a href="products-working.php" class="btn btn-light btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Começar a Comprar
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-home me-2"></i>Na Porta</h5>
                    <p class="text-muted">Essenciais domésticos na sua porta. Rápido, seguro e confiável.</p>
                </div>
                <div class="col-md-4">
                    <h6>Links Úteis</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted">Sobre Nós</a></li>
                        <li><a href="#" class="text-muted">Como Funciona</a></li>
                        <li><a href="#" class="text-muted">Contato</a></li>
                        <li><a href="#" class="text-muted">FAQ</a></li>
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

    <!-- Debug Info -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
        <div class="bg-success text-white p-2 rounded small">
            <strong>✅ Working!</strong><br>
            Categories: <?php echo count($categories); ?><br>
            Products: <?php echo count($products); ?><br>
            <a href="../setup-fixed.php" class="text-white">Setup</a> | 
            <a href="../admin/login.php" class="text-white">Admin</a>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Simple smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Banner Slider Functionality
        let currentBanner = 0;
        const banners = document.querySelectorAll('.banner-slide');
        const dots = document.querySelectorAll('.banner-dot');
        
        function showBanner(index) {
            // Hide all banners
            banners.forEach(banner => banner.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current banner
            if (banners[index]) {
                banners[index].classList.add('active');
                if (dots[index]) {
                    dots[index].classList.add('active');
                }
                currentBanner = index;
            }
        }
        
        function changeBanner(direction) {
            const totalBanners = banners.length;
            if (totalBanners === 0) return;
            
            currentBanner += direction;
            
            if (currentBanner >= totalBanners) {
                currentBanner = 0;
            } else if (currentBanner < 0) {
                currentBanner = totalBanners - 1;
            }
            
            showBanner(currentBanner);
        }
        
        function goToBanner(index) {
            showBanner(index);
        }
        
        // Auto-slide banners every 5 seconds
        if (banners.length > 1) {
            setInterval(() => {
                changeBanner(1);
            }, 5000);
        }
        
        // Add to cart function
        function addToCart(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart-working.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'add';
            
            const productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            productInput.value = productId;
            
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = '1';
            
            form.appendChild(actionInput);
            form.appendChild(productInput);
            form.appendChild(quantityInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
