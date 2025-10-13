<?php
// Admin Products Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Simple admin check
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit();
}

// Get admin info
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $compare_price = floatval($_POST['compare_price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_url = trim($_POST['image_url'] ?? '');
        
        // Validate input
        if (empty($name) || empty($short_description) || $price <= 0) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                // Create slug from name
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                // Try with image_url first, fallback without if column doesn't exist
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, slug, short_description, description, price, compare_price, 
                                            stock_quantity, category_id, is_featured, is_active, image_url, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $success_result = $stmt->execute([$name, $slug, $short_description, $description, $price, 
                                      $compare_price ?: null, $stock_quantity, $category_id ?: null, 
                                      $is_featured, $is_active, $image_url ?: null]);
                } catch (Exception $e) {
                    // Fallback without image_url if column doesn't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, slug, short_description, description, price, compare_price, 
                                            stock_quantity, category_id, is_featured, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $success_result = $stmt->execute([$name, $slug, $short_description, $description, $price, 
                                      $compare_price ?: null, $stock_quantity, $category_id ?: null, 
                                      $is_featured, $is_active]);
                }
                
                if ($success_result) {
                    $success = "Produto adicionado com sucesso!";
                } else {
                    $error = "Erro ao adicionar produto.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit_product') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $compare_price = floatval($_POST['compare_price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_url = trim($_POST['image_url'] ?? '');
        
        // Validate input
        if (empty($name) || empty($short_description) || $price <= 0 || $id <= 0) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                // Create slug from name
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                // Try with image_url first, fallback without if column doesn't exist
                try {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, slug = ?, short_description = ?, description = ?, price = ?, compare_price = ?, 
                            stock_quantity = ?, category_id = ?, is_featured = ?, is_active = ?, image_url = ?
                        WHERE id = ?
                    ");
                    
                    $success_result = $stmt->execute([$name, $slug, $short_description, $description, $price, 
                                      $compare_price ?: null, $stock_quantity, $category_id ?: null, 
                                      $is_featured, $is_active, $image_url ?: null, $id]);
                } catch (Exception $e) {
                    // Fallback without image_url if column doesn't exist
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, slug = ?, short_description = ?, description = ?, price = ?, compare_price = ?, 
                            stock_quantity = ?, category_id = ?, is_featured = ?, is_active = ?
                        WHERE id = ?
                    ");
                    
                    $success_result = $stmt->execute([$name, $slug, $short_description, $description, $price, 
                                      $compare_price ?: null, $stock_quantity, $category_id ?: null, 
                                      $is_featured, $is_active, $id]);
                }
                
                if ($success_result) {
                    $success = "Produto atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar produto.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_product') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID inválido.";
        } else {
            try {
                // Delete product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                
                if ($stmt->execute([$id])) {
                    $success = "Produto excluído com sucesso!";
                } else {
                    $error = "Erro ao excluir produto.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Get categories for dropdown
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore category loading errors
}

// Get products from database
$products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erro ao carregar produtos: " . $e->getMessage();
}

$page_title = 'Produtos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Na Porta</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
            --sidebar-width: 250px;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #2c3e50;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-center mb-4">
                <i class="fas fa-home me-2"></i>Na Porta
                <small class="d-block text-muted">Admin</small>
            </h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index-fixed.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="products.php">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categorias
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>Usuários
                </a>
            </li>
            <li class="nav-item mt-4">
                <hr class="text-muted">
                <a class="nav-link" href="../pages/home-fixed.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout-simple.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-admin">
            <div class="container-fluid">
                <h5 class="mb-0">Gerenciar Produtos</h5>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout-simple.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>Produtos</h2>
                    <p class="text-muted">Gerencie os produtos da sua loja</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i>Adicionar Produto
                    </button>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Products Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>Nenhum produto encontrado</h5>
                        <p class="text-muted">Adicione produtos para começar a vender</p>
                        <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Adicionar Primeiro Produto
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Preço</th>
                                    <th>Estoque</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if ($product['is_featured']): ?>
                                        <span class="badge bg-warning ms-2">Destaque</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Sem categoria'); ?></td>
                                    <td>R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $product['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Editar"
                                                    onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Excluir"
                                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Adicionar Produto
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="short_description" class="form-label">Descrição Curta *</label>
                            <input type="text" class="form-control" id="short_description" name="short_description" 
                                   placeholder="Descrição breve do produto" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição Completa</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Descrição detalhada do produto"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL da Imagem</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   placeholder="https://picsum.photos/id/237/200/300">
                            <small class="text-muted">Cole o link de uma imagem online (ex: https://picsum.photos/id/237/200/300)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Preço *</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="compare_price" class="form-label">Preço Comparativo</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="compare_price" name="compare_price" 
                                           step="0.01" min="0">
                                </div>
                                <small class="text-muted">Preço original (para mostrar desconto)</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_quantity" class="form-label">Estoque</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       min="0" value="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        <i class="fas fa-star text-warning me-1"></i>Produto em Destaque
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-eye text-success me-1"></i>Produto Ativo
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Dica:</strong> Produtos em destaque aparecem na página inicial do site.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Adicionar Produto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Produto
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_product">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="edit_name" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_short_description" class="form-label">Descrição Curta *</label>
                            <input type="text" class="form-control" id="edit_short_description" name="short_description" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descrição Completa</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="edit_image_url" class="form-label">URL da Imagem</label>
                            <input type="url" class="form-control" id="edit_image_url" name="image_url" 
                                   placeholder="https://picsum.photos/id/237/200/300">
                            <small class="text-muted">Cole o link de uma imagem online (ex: https://picsum.photos/id/237/200/300)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_price" class="form-label">Preço *</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="edit_price" name="price" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_compare_price" class="form-label">Preço Comparativo</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="edit_compare_price" name="compare_price" 
                                           step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_stock_quantity" class="form-label">Estoque</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured">
                                    <label class="form-check-label" for="edit_is_featured">
                                        <i class="fas fa-star text-warning me-1"></i>Produto em Destaque
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        <i class="fas fa-eye text-success me-1"></i>Produto Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Produto
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="id" id="delete_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        
                        <p>Tem certeza que deseja excluir o produto <strong id="delete_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Produto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Close modal and reset form after successful submission
        <?php if ($success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Close all modals if they're open
            const modals = ['addProductModal', 'editProductModal', 'deleteProductModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                const modalInstance = mdb.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            
            // Reset add form
            const addForm = document.getElementById('addProductModal').querySelector('form');
            if (addForm) {
                addForm.reset();
                document.getElementById('is_active').checked = true;
            }
        });
        <?php endif; ?>
        
        // Edit product function
        function editProduct(product) {
            // Populate edit form
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_short_description').value = product.short_description;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_compare_price').value = product.compare_price || '';
            document.getElementById('edit_stock_quantity').value = product.stock_quantity;
            document.getElementById('edit_category_id').value = product.category_id || '';
            document.getElementById('edit_image_url').value = product.image_url || '';
            document.getElementById('edit_is_featured').checked = product.is_featured == 1;
            document.getElementById('edit_is_active').checked = product.is_active == 1;
            
            // Show edit modal
            const modal = new mdb.Modal(document.getElementById('editProductModal'));
            modal.show();
        }
        
        // Delete product function
        function deleteProduct(id, name) {
            // Populate delete form
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            // Show delete modal
            const modal = new mdb.Modal(document.getElementById('deleteProductModal'));
            modal.show();
        }
    </script>
</body>
</html>
