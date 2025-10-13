<?php
// Working Products Page with Categories
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Simple functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function format_currency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';

// Get categories
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get products with filters
$products = [];
$total_products = 0;

try {
    // Build query
    $where_conditions = ["p.is_active = 1"];
    $params = [];
    
    if ($category_filter) {
        $where_conditions[] = "c.slug = ?";
        $params[] = $category_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Order by
    $order_by = "p.name ASC";
    switch ($sort) {
        case 'price_low':
            $order_by = "p.price ASC";
            break;
        case 'price_high':
            $order_by = "p.price DESC";
            break;
        case 'newest':
            $order_by = "p.created_at DESC";
            break;
        case 'featured':
            $order_by = "p.is_featured DESC, p.name ASC";
            break;
    }
    
    // Get products
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where_clause 
        ORDER BY $order_by
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_products = count($products);
    
} catch (Exception $e) {
    $error = "Erro ao carregar produtos: " . $e->getMessage();
}

// Get current category name
$current_category_name = '';
if ($category_filter) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $category_filter) {
            $current_category_name = $cat['name'];
            break;
        }
    }
}

$page_title = $current_category_name ? "$current_category_name - Na Porta" : "Produtos - Na Porta";
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
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .category-filter {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .filter-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home-fixed.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products-working.php">Produtos</a>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" method="GET" action="">
                    <?php if ($category_filter): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <?php endif; ?>
                    <input class="form-control me-2" type="search" name="search" 
                           placeholder="Buscar produtos..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
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

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-2">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="home-fixed.php">Início</a></li>
                <li class="breadcrumb-item"><a href="products-working.php">Produtos</a></li>
                <?php if ($current_category_name): ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($current_category_name); ?></li>
                <?php endif; ?>
            </ol>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="category-filter">
                    <h5 class="mb-3">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </h5>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Categorias</h6>
                        <div class="list-group list-group-flush">
                            <a href="products-working.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
                               class="list-group-item list-group-item-action border-0 <?php echo !$category_filter ? 'active' : ''; ?>">
                                <i class="fas fa-th me-2"></i>Todas as Categorias
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="products-working.php?category=<?php echo urlencode($category['slug']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="list-group-item list-group-item-action border-0 <?php echo $category_filter === $category['slug'] ? 'active' : ''; ?>">
                                <?php
                                $icons = [
                                    'agua' => 'fas fa-tint',
                                    'gas' => 'fas fa-fire',
                                    'limpeza' => 'fas fa-spray-can',
                                    'mercearia' => 'fas fa-shopping-basket'
                                ];
                                $icon = $icons[$category['slug']] ?? 'fas fa-tag';
                                ?>
                                <i class="<?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($category['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Clear Filters -->
                    <?php if ($category_filter || $search): ?>
                    <div class="d-grid">
                        <a href="products-working.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Limpar Filtros
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><?php echo $current_category_name ?: 'Todos os Produtos'; ?></h2>
                        <p class="text-muted mb-0"><?php echo $total_products; ?> produto(s) encontrado(s)</p>
                        
                        <!-- Active Filters -->
                        <?php if ($search): ?>
                        <div class="mt-2">
                            <span class="filter-badge me-2">
                                Busca: "<?php echo htmlspecialchars($search); ?>"
                                <a href="products-working.php<?php echo $category_filter ? '?category=' . urlencode($category_filter) : ''; ?>" 
                                   class="text-white ms-1">×</a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sort -->
                    <div>
                        <select class="form-select" onchange="updateSort(this.value)">
                            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nome A-Z</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Menor Preço</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Maior Preço</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mais Recentes</option>
                            <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Em Destaque</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Nenhum produto encontrado</h5>
                    <p class="text-muted">Tente ajustar os filtros ou fazer uma nova busca.</p>
                    <a href="products-working.php" class="btn btn-primary">
                        <i class="fas fa-th me-2"></i>Ver Todos os Produtos
                    </a>
                </div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            <!-- Product Image -->
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-fluid" style="max-height: 180px; object-fit: contain;">
                                <?php else: ?>
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="card-body d-flex flex-column">
                                <!-- Category Badge -->
                                <?php if ($product['category_name']): ?>
                                <span class="badge bg-secondary mb-2 align-self-start">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                                <?php endif; ?>
                                
                                <!-- Featured Badge -->
                                <?php if ($product['is_featured']): ?>
                                <span class="badge bg-warning text-dark mb-2 align-self-start">
                                    <i class="fas fa-star me-1"></i>Destaque
                                </span>
                                <?php endif; ?>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted flex-grow-1">
                                    <?php echo htmlspecialchars($product['short_description']); ?>
                                </p>
                                
                                <!-- Price and Stock -->
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                            <small class="text-muted text-decoration-line-through">
                                                <?php echo format_currency($product['compare_price']); ?>
                                            </small><br>
                                            <?php endif; ?>
                                            <span class="h5 text-primary mb-0">
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
                                    
                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary add-to-cart" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                onclick="addToCart(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-cart-plus me-2"></i>Adicionar ao Carrinho
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-times me-2"></i>Indisponível
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline-primary" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye me-2"></i>Ver Detalhes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                    <h6>Categorias</h6>
                    <ul class="list-unstyled">
                        <?php foreach ($categories as $category): ?>
                        <li><a href="products-working.php?category=<?php echo urlencode($category['slug']); ?>" 
                               class="text-muted"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
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

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        function updateSort(sortValue) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sortValue);
            window.location.href = url.toString();
        }
        
        function addToCart(productId) {
            // Add to cart via form submission
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
        
        function viewProduct(productId) {
            alert('Ver detalhes do produto ' + productId + ' (Em desenvolvimento)');
        }
    </script>
</body>
</html>
