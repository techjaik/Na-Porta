<?php
// Working Home Page - Na Porta
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include config
require_once 'config/config.php';
require_once 'config/database.php';

// Get products if database is available
$products = [];
$categories = [];

try {
    // Get categories
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get featured products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 LIMIT 4");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Database not ready, use sample data
    $categories = [
        ['id' => 1, 'name' => 'Água', 'slug' => 'agua'],
        ['id' => 2, 'name' => 'Gás', 'slug' => 'gas'],
        ['id' => 3, 'name' => 'Limpeza', 'slug' => 'limpeza'],
        ['id' => 4, 'name' => 'Mercearia', 'slug' => 'mercearia']
    ];
    
    $products = [
        ['id' => 1, 'name' => 'Água Mineral 20L', 'price' => 15.90, 'short_description' => 'Água mineral natural'],
        ['id' => 2, 'name' => 'Botijão de Gás 13kg', 'price' => 85.00, 'short_description' => 'Botijão de gás'],
        ['id' => 3, 'name' => 'Kit Limpeza', 'price' => 45.50, 'short_description' => 'Kit completo'],
        ['id' => 4, 'name' => 'Cesta Básica', 'price' => 120.00, 'short_description' => 'Cesta familiar']
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Na Porta - Essenciais na sua porta</title>
    
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
        }
        
        .category-card:hover {
            transform: translateY(-5px);
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#produtos">Produtos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#como-funciona">Como Funciona</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-shopping-cart me-1"></i>Carrinho (0)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user me-1"></i>Entrar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Essenciais na sua porta</h1>
                    <p class="lead mb-4">
                        Água, gás, produtos de limpeza e mercearia entregues com rapidez e segurança. 
                        Tudo que você precisa, quando você precisa.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#produtos" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Comprar Agora
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

    <!-- Categories Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Nossas Categorias</h2>
                    <p class="text-muted">Encontre tudo que você precisa para sua casa</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card category-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <?php
                                $icons = [
                                    'agua' => 'fas fa-tint text-primary',
                                    'gas' => 'fas fa-fire text-danger',
                                    'limpeza' => 'fas fa-spray-can text-success',
                                    'mercearia' => 'fas fa-shopping-basket text-warning'
                                ];
                                $icon = $icons[$category['slug']] ?? 'fas fa-box text-secondary';
                                ?>
                                <i class="<?php echo $icon; ?> fa-3x"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php 
                                $descriptions = [
                                    'agua' => 'Água mineral e galões',
                                    'gas' => 'Botijões de gás',
                                    'limpeza' => 'Produtos de limpeza',
                                    'mercearia' => 'Itens básicos'
                                ];
                                echo $descriptions[$category['slug']] ?? 'Produtos essenciais';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="produtos" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Produtos em Destaque</h2>
                    <p class="text-muted">Os mais pedidos pelos nossos clientes</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="como-funciona" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Como Funciona</h2>
                    <p class="text-muted">Simples, rápido e seguro</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-search fa-3x text-primary"></i>
                    </div>
                    <h5>1. Escolha</h5>
                    <p class="text-muted">Navegue pelas categorias e escolha os produtos que precisa</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-shopping-cart fa-3x text-primary"></i>
                    </div>
                    <h5>2. Peça</h5>
                    <p class="text-muted">Adicione ao carrinho e finalize com pagamento PIX</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-bell fa-3x text-primary"></i>
                    </div>
                    <h5>3. Acompanhe</h5>
                    <p class="text-muted">Receba notificações em tempo real sobre seu pedido</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-truck fa-3x text-primary"></i>
                    </div>
                    <h5>4. Receba</h5>
                    <p class="text-muted">Entrega rápida e segura na sua porta</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-home me-2"></i>Na Porta</h5>
                    <p class="text-muted">Essenciais domésticos na sua porta</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2024 Na Porta. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <!-- Debug Info -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
        <div class="bg-info text-white p-2 rounded small">
            <strong>Debug:</strong><br>
            Categories: <?php echo count($categories); ?><br>
            Products: <?php echo count($products); ?><br>
            <a href="setup-fixed.php" class="text-white">Setup</a> | 
            <a href="create-admin.php" class="text-white">Admin</a>
        </div>
    </div>
</body>
</html>
