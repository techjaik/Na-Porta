<?php
// Redirect to working version
header('Location: home-fixed.php');
exit();

// Get featured products
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pi.image_path, c.name as category_name 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND p.is_featured = 1 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured_products = [];
}

// Get categories
try {
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content fade-in">
                <h1 class="display-4 fw-bold mb-4">
                    Essenciais na sua porta
                </h1>
                <p class="lead mb-4">
                    Água, gás, produtos de limpeza e mercearia entregues com segurança e praticidade. 
                    Sem sair de casa, sem complicação.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="products.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Explorar Produtos
                    </a>
                    <a href="#como-funciona" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-play me-2"></i>
                        Como Funciona
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center slide-in-right">
                <img src="<?php echo SITE_URL; ?>/assets/images/hero-delivery.svg" 
                     alt="Entrega Na Porta" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</section>

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
                <a href="products.php?category=<?php echo $category['slug']; ?>" class="category-card">
                    <div class="category-icon">
                        <?php
                        $icons = [
                            'agua' => 'fas fa-tint text-primary',
                            'gas' => 'fas fa-fire text-danger',
                            'limpeza' => 'fas fa-spray-can text-success',
                            'mercearia' => 'fas fa-shopping-basket text-warning'
                        ];
                        $icon_class = $icons[$category['slug']] ?? 'fas fa-box text-secondary';
                        ?>
                        <i class="<?php echo $icon_class; ?>"></i>
                    </div>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featured_products)): ?>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Produtos em Destaque</h2>
            <p class="text-muted">Os mais pedidos pelos nossos clientes</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card product-card h-100">
                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                    <span class="badge bg-danger">Oferta</span>
                    <?php endif; ?>
                    
                    <img src="<?php echo SITE_URL . '/uploads/' . ($product['image_path'] ?: 'placeholder.jpg'); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary mb-2 align-self-start">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                        
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted flex-grow-1">
                            <?php echo htmlspecialchars($product['short_description']); ?>
                        </p>
                        
                        <div class="mt-auto">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                    <small class="product-compare-price">
                                        <?php echo format_currency($product['compare_price']); ?>
                                    </small><br>
                                    <?php endif; ?>
                                    <span class="product-price">
                                        <?php echo format_currency($product['price']); ?>
                                    </span>
                                </div>
                                
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
                                <button class="btn btn-primary add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus me-2"></i>Adicionar
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-times me-2"></i>Indisponível
                                </button>
                                <?php endif; ?>
                                
                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="products.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-th me-2"></i>Ver Todos os Produtos
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

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

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">O que nossos clientes dizem</h2>
            <p class="text-muted">Experiências reais de quem já usa o Na Porta</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">
                            "Nunca mais fiquei sem água em casa! O Na Porta é muito prático e confiável."
                        </p>
                        <div class="mt-auto">
                            <strong>Maria Silva</strong><br>
                            <small class="text-muted">São Paulo, SP</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">
                            "Entrega super rápida e produtos de qualidade. Recomendo para todos!"
                        </p>
                        <div class="mt-auto">
                            <strong>João Santos</strong><br>
                            <small class="text-muted">Rio de Janeiro, RJ</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">
                            "Facilidade no pagamento com PIX e atendimento excelente. Nota 10!"
                        </p>
                        <div class="mt-auto">
                            <strong>Ana Costa</strong><br>
                            <small class="text-muted">Belo Horizonte, MG</small>
                        </div>
                    </div>
                </div>
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
            <a href="auth/register.php" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i>Criar Conta
            </a>
            <a href="products.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Comprar Agora
            </a>
        </div>
        <?php else: ?>
        <a href="products.php" class="btn btn-light btn-lg">
            <i class="fas fa-shopping-cart me-2"></i>Começar a Comprar
        </a>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
