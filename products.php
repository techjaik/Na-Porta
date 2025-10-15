<?php
/**
 * Na Porta - Products Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$db = Database::getInstance();

// Get category filter
$categoryId = intval($_GET['category'] ?? 0);
$search = trim($_GET['search'] ?? '');

// Get categories for filter
$categories = [];
$products = [];
try {
    $categories = $db->fetchAll("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    
    // Build products query
    $whereClause = "WHERE p.is_active = 1";
    $params = [];
    
    if ($categoryId > 0) {
        $whereClause .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($search) {
        $whereClause .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $products = $db->fetchAll("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereClause
        ORDER BY p.created_at DESC
    ", $params);
    
} catch (Exception $e) {
    error_log("Products page error: " . $e->getMessage());
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Na Porta</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --gray-50: #f9fafb;
            --gray-800: #1f2937;
            --border-radius: 12px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
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
        
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
        }
        
        .filter-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
        }
        
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" id="cart-count">
                                0
                            </span>
                        </a>
                    </li>
                    <?php if ($user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="account.php">Minha Conta</a></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">Entrar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/register.php">Cadastrar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title mb-2">Nossos Produtos</h1>
                    <p class="lead mb-0">Encontre tudo o que você precisa com qualidade e preço justo</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        <i class="fas fa-box fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3 mb-4">
                    <div class="filter-card p-4">
                        <h5 class="mb-3">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </h5>
                        
                        <!-- Search -->
                        <form method="GET" class="mb-4">
                            <?php if ($categoryId): ?>
                                <input type="hidden" name="category" value="<?= $categoryId ?>">
                            <?php endif; ?>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Buscar produtos..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Categories -->
                        <h6 class="mb-3">Categorias</h6>
                        <div class="list-group list-group-flush">
                            <a href="products.php<?= $search ? '?search=' . urlencode($search) : '' ?>" 
                               class="list-group-item list-group-item-action <?= !$categoryId ? 'active' : '' ?>">
                                <i class="fas fa-th-large me-2"></i>Todas
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="products.php?category=<?= $category['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="list-group-item list-group-item-action <?= $categoryId == $category['id'] ? 'active' : '' ?>">
                                    <i class="fas fa-tag me-2"></i><?= htmlspecialchars($category['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="col-lg-9">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Nenhum produto encontrado</h4>
                            <p class="text-muted">Tente ajustar os filtros ou fazer uma nova busca.</p>
                            <a href="products.php" class="btn btn-primary">Ver Todos os Produtos</a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><?= count($products) ?> produto(s) encontrado(s)</h5>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" onchange="sortProducts(this.value)">
                                    <option value="">Ordenar por</option>
                                    <option value="name">Nome A-Z</option>
                                    <option value="price_low">Menor Preço</option>
                                    <option value="price_high">Maior Preço</option>
                                    <option value="newest">Mais Recentes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($products as $product): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="product-card">
                                        <div class="position-relative">
                                            <?php if (isset($product['image_url']) && $product['image_url']): ?>
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                     class="card-img-top" style="height: 200px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="height: 200px;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                                                <span class="position-absolute top-0 end-0 m-2 badge bg-warning">
                                                    <i class="fas fa-star me-1"></i>Destaque
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0"><?= htmlspecialchars($product['name']) ?></h6>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                                            </div>
                                            
                                            <?php if ($product['description']): ?>
                                                <p class="card-text text-muted small">
                                                    <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="text-success mb-0">
                                                        R$ <?= number_format($product['price'], 2, ',', '.') ?>
                                                    </h5>
                                                </div>
                                                <button class="btn btn-primary btn-sm" 
                                                        onclick="addToCart(<?= $product['id'] ?>)">
                                                    <i class="fas fa-cart-plus me-1"></i>Adicionar
                                                </button>
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
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add to cart functionality
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
        
        // Update cart count
        function updateCartCount() {
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
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
        
        // Sort products
        function sortProducts(sortBy) {
            const url = new URL(window.location);
            if (sortBy) {
                url.searchParams.set('sort', sortBy);
            } else {
                url.searchParams.delete('sort');
            }
            window.location = url;
        }
        
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>
