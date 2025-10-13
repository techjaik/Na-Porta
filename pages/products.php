<?php
// Redirect to working version
$query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: products-working.php' . $query_string);
exit();

// Get filters
$category_slug = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = ['p.is_active = 1'];
$params = [];

if ($category_slug) {
    $where_conditions[] = 'c.slug = ?';
    $params[] = $category_slug;
}

if ($search) {
    $where_conditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Validate sort parameter
$allowed_sorts = ['name', 'price_asc', 'price_desc', 'newest'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'name';
}

$order_clause = match($sort) {
    'name' => 'p.name ASC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest' => 'p.created_at DESC',
    default => 'p.name ASC'
};

try {
    // Get total count
    $count_sql = "
        SELECT COUNT(*) 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_products = $stmt->fetchColumn();
    
    // Get products
    $sql = "
        SELECT p.*, pi.image_path, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $where_clause
        ORDER BY $order_clause
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for filter
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current category info
    $current_category = null;
    if ($category_slug) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
        $stmt->execute([$category_slug]);
        $current_category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_category) {
            $page_title = $current_category['name'] . ' - Produtos';
            $page_description = $current_category['description'];
        }
    }
    
} catch (PDOException $e) {
    $products = [];
    $categories = [];
    $total_products = 0;
    error_log('Products page error: ' . $e->getMessage());
}

// Calculate pagination
$total_pages = ceil($total_products / $per_page);

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Início</a></li>
            <?php if ($current_category): ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($current_category['name']); ?></li>
            <?php else: ?>
            <li class="breadcrumb-item active">Produtos</li>
            <?php endif; ?>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold">
                <?php if ($current_category): ?>
                    <i class="<?php 
                        $icons = [
                            'agua' => 'fas fa-tint text-primary',
                            'gas' => 'fas fa-fire text-danger',
                            'limpeza' => 'fas fa-spray-can text-success',
                            'mercearia' => 'fas fa-shopping-basket text-warning'
                        ];
                        echo $icons[$current_category['slug']] ?? 'fas fa-box text-secondary';
                    ?> me-2"></i>
                    <?php echo htmlspecialchars($current_category['name']); ?>
                <?php elseif ($search): ?>
                    <i class="fas fa-search text-primary me-2"></i>
                    Resultados para "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    <i class="fas fa-box text-primary me-2"></i>
                    Todos os Produtos
                <?php endif; ?>
            </h1>
            
            <?php if ($current_category && $current_category['description']): ?>
            <p class="text-muted"><?php echo htmlspecialchars($current_category['description']); ?></p>
            <?php endif; ?>
            
            <p class="text-muted">
                <?php echo $total_products; ?> produto<?php echo $total_products != 1 ? 's' : ''; ?> encontrado<?php echo $total_products != 1 ? 's' : ''; ?>
            </p>
        </div>
        
        <div class="col-md-4 text-md-end">
            <!-- Sort Options -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                    <i class="fas fa-sort me-2"></i>Ordenar
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item <?php echo $sort === 'name' ? 'active' : ''; ?>" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'page' => 1])); ?>">
                        Nome A-Z
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'price_asc' ? 'active' : ''; ?>" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc', 'page' => 1])); ?>">
                        Menor Preço
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'price_desc' ? 'active' : ''; ?>" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc', 'page' => 1])); ?>">
                        Maior Preço
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'newest' ? 'active' : ''; ?>" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest', 'page' => 1])); ?>">
                        Mais Recentes
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Categories Filter -->
                    <h6 class="fw-bold mb-3">Categorias</h6>
                    <div class="list-group list-group-flush mb-4">
                        <a href="products.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
                           class="list-group-item list-group-item-action <?php echo !$category_slug ? 'active' : ''; ?>">
                            <i class="fas fa-th me-2"></i>Todas
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['slug'], 'page' => 1])); ?>" 
                           class="list-group-item list-group-item-action <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                            <i class="<?php 
                                $icons = [
                                    'agua' => 'fas fa-tint',
                                    'gas' => 'fas fa-fire',
                                    'limpeza' => 'fas fa-spray-can',
                                    'mercearia' => 'fas fa-shopping-basket'
                                ];
                                echo $icons[$cat['slug']] ?? 'fas fa-box';
                            ?> me-2"></i>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Search within category -->
                    <h6 class="fw-bold mb-3">Buscar</h6>
                    <form method="GET" class="mb-3">
                        <?php if ($category_slug): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Buscar produtos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($search || $category_slug): ?>
                    <a href="products.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-times me-2"></i>Limpar Filtros
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>Nenhum produto encontrado</h4>
                <p class="text-muted">Tente ajustar os filtros ou fazer uma nova busca.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-th me-2"></i>Ver Todos os Produtos
                </a>
            </div>
            <?php else: ?>
            
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-xl-4">
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Navegação de produtos" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Próxima <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
