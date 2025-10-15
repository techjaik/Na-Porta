<?php
/**
 * Na Porta - Clean Main Website Homepage
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$db = Database::getInstance();

// Get featured products
$featuredProducts = [];
try {
    $featuredProducts = $db->fetchAll("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 AND p.is_featured = 1 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
} catch (Exception $e) {
    error_log("Featured products error: " . $e->getMessage());
}

// Get categories
$categories = [];
try {
    $categories = $db->fetchAll("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC, name ASC
    ");
} catch (Exception $e) {
    error_log("Categories error: " . $e->getMessage());
}

// Get promotional banners
$banners = [];
try {
    $banners = $db->fetchAll("
        SELECT * FROM promotional_banners 
        WHERE is_active = 1 
        ORDER BY created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Banners error: " . $e->getMessage());
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Na Porta - Delivery de Água, Gás e Mais</title>
    <meta name="description" content="Delivery rápido de água, gás, produtos de limpeza e mercearia. Peça online e receba em casa!">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
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
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: var(--shadow-lg);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .display-4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }
        
        .category-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            overflow: hidden;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }
        
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            overflow: hidden;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-outline-light:hover {
            transform: translateY(-2px);
        }
        
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            position: relative;
            margin-bottom: 3rem;
            color: var(--gray-800);
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        .footer {
            background: var(--gray-800);
            color: white;
            padding: 4rem 0 2rem;
        }
        
        .footer h5, .footer h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--warning-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Banner Slider Styles */
        .banner-slider-section {
            background: white;
            padding: 3rem 0 0;
        }
        
        .banner-slide {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 500px;
            display: flex;
            align-items: center;
        }
        
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            pointer-events: none;
        }
        
        .banner-content-wrapper {
            position: relative;
            z-index: 2;
            padding: 2rem 0;
        }
        
        .banner-slide-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .banner-slide-description {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .banner-visual {
            position: relative;
            z-index: 2;
            padding: 2rem;
        }
        
        .banner-image {
            max-height: 400px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            transition: transform 0.3s ease;
        }
        
        .banner-placeholder {
            color: rgba(255, 255, 255, 0.6);
            padding: 4rem 2rem;
        }
        
        /* Carousel Customization */
        .carousel-indicators {
            bottom: 20px;
        }
        
        .carousel-indicators [data-bs-target] {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            border: 2px solid white;
            transition: all 0.3s ease;
        }
        
        .carousel-indicators .active {
            background-color: var(--warning-color);
            transform: scale(1.2);
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            transition: all 0.3s ease;
        }
        
        .carousel-control-prev {
            left: 20px;
        }
        
        .carousel-control-next {
            right: 20px;
        }
        
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 24px;
            height: 24px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .banner-slide-title {
                font-size: 2rem;
            }
            
            .banner-slide-description {
                font-size: 1rem;
            }
            
            .banner-slide {
                min-height: 400px;
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                width: 40px;
                height: 40px;
            }
            
            .carousel-control-prev {
                left: 10px;
            }
            
            .carousel-control-next {
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produtos</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Categorias
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item" href="products.php?category=<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge" id="cart-count">0</span>
                        </a>
                    </li>
                    
                    <?php if ($user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="account/profile.php">Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="account/orders.php">Meus Pedidos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Entrar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/register.php">Cadastrar</a>
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
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4">
                            Delivery Rápido<br>
                            <span class="text-warning">Na Sua Porta</span>
                        </h1>
                    <p class="lead mb-4">
                        Água, gás, produtos de limpeza e mercearia entregues com rapidez e qualidade. 
                        Peça online e receba em casa!
                    </p>
                    <div class="d-flex gap-3">
                        <a href="products.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Ver Produtos
                        </a>
                        <a href="#categories" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-list me-2"></i>Categorias
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-truck fa-10x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Promotional Banners Slider -->
    <?php if (!empty($banners)): ?>
    <section class="banner-slider-section">
        <div class="container-fluid px-0">
            <h2 class="text-center section-title mb-5">Promoções Especiais</h2>
            
            <div id="promotionalCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                <!-- Carousel Indicators -->
                <div class="carousel-indicators">
                    <?php foreach ($banners as $index => $banner): ?>
                        <button type="button" data-bs-target="#promotionalCarousel" data-bs-slide-to="<?= $index ?>" 
                                <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> 
                                aria-label="Slide <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Carousel Items -->
                <div class="carousel-inner">
                    <?php foreach ($banners as $index => $banner): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <div class="banner-slide">
                                <div class="container">
                                    <div class="row align-items-center min-vh-50">
                                        <div class="col-lg-6">
                                            <div class="banner-content-wrapper">
                                                <h1 class="banner-slide-title"><?= htmlspecialchars($banner['title']) ?></h1>
                                                <?php if ($banner['description']): ?>
                                                    <p class="banner-slide-description"><?= htmlspecialchars($banner['description']) ?></p>
                                                <?php endif; ?>
                                                <a href="products.php" class="btn btn-warning btn-lg mt-3">
                                                    <i class="fas fa-shopping-bag me-2"></i>Ver Produtos
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 text-center">
                                            <div class="banner-visual">
                                                <?php if (isset($banner['file_path']) && !empty($banner['file_path'])): ?>
                                                    <img src="<?= htmlspecialchars($banner['file_path']) ?>" 
                                                         alt="<?= htmlspecialchars($banner['title']) ?>"
                                                         class="img-fluid banner-image">
                                                <?php else: ?>
                                                    <div class="banner-placeholder">
                                                        <i class="fas fa-bullhorn fa-8x"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="banner-overlay"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Carousel Controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#promotionalCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promotionalCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Categories Section -->
    <section class="py-5" id="categories">
        <div class="container">
            <h2 class="text-center section-title">Nossas Categorias</h2>
            
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="category-card h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <?php if (isset($category['image']) && $category['image']): ?>
                                        <img src="<?= htmlspecialchars($category['image']) ?>" 
                                             alt="<?= htmlspecialchars($category['name']) ?>"
                                             class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                        <?php
                                        $icons = [
                                            'agua' => 'fas fa-tint text-primary',
                                            'gas' => 'fas fa-fire text-danger',
                                            'limpeza' => 'fas fa-spray-can text-success',
                                            'mercearia' => 'fas fa-shopping-basket text-warning'
                                        ];
                                        $icon = $icons[$category['slug']] ?? 'fas fa-box text-secondary';
                                        ?>
                                        <i class="<?= $icon ?> fa-3x"></i>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                <p class="card-text text-muted">
                                    <?= htmlspecialchars($category['description']) ?>
                                </p>
                                <a href="products.php?category=<?= $category['id'] ?>" class="btn btn-primary">
                                    Ver Produtos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title">Produtos em Destaque</h2>
            
            <div class="row">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="product-card h-100">
                            <?php if (isset($product['image_url']) && $product['image_url']): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($product['name']) ?></h6>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">
                                        R$ <?= number_format($product['price'], 2, ',', '.') ?>
                                    </span>
                                    <button class="btn btn-sm btn-primary add-to-cart" 
                                            data-product-id="<?= $product['id'] ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-eye me-2"></i>Ver Todos os Produtos
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-home me-2"></i>Na Porta</h5>
                    <p class="text-light">
                        Delivery rápido e confiável de água, gás, produtos de limpeza e mercearia. 
                        Sua comodidade é nossa prioridade.
                    </p>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h6>Links Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Início</a></li>
                        <li><a href="products.php" class="text-light text-decoration-none">Produtos</a></li>
                        <li><a href="auth/login.php" class="text-light text-decoration-none">Entrar</a></li>
                        <li><a href="auth/register.php" class="text-light text-decoration-none">Cadastrar</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h6>Contato</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i>(11) 9999-9999</li>
                        <li><i class="fas fa-envelope me-2"></i>contato@naporta.com</li>
                        <li><i class="fas fa-clock me-2"></i>Seg-Dom: 8h às 22h</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Na Porta. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Add to cart buttons
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    addToCart(productId);
                });
            });
        });
        
        function addToCart(productId) {
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount();
                    showToast('Produto adicionado ao carrinho!', 'success');
                } else {
                    showToast(data.message || 'Erro ao adicionar produto', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erro ao adicionar produto', 'error');
            });
        }
        
        function updateCartCount() {
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
        
        function showToast(message, type) {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }
    </script>
</body>
</html>
