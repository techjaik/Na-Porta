<?php
// Admin Categories Page
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
    
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate input
        if (empty($name)) {
            $error = "Por favor, preencha o nome da categoria.";
        } else {
            try {
                // Create slug from name
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                // Check if slug already exists
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if ($stmt->fetch()) {
                    $error = "Já existe uma categoria com este nome.";
                } else {
                    // Insert new category
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (name, slug, description, sort_order, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$name, $slug, $description, $sort_order, $is_active])) {
                        $success = "Categoria adicionada com sucesso!";
                    } else {
                        $error = "Erro ao adicionar categoria.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit_category') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate input
        if (empty($name) || $id <= 0) {
            $error = "Dados inválidos.";
        } else {
            try {
                // Create slug from name
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                // Check if slug already exists (excluding current category)
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
                
                if ($stmt->fetch()) {
                    $error = "Já existe uma categoria com este nome.";
                } else {
                    // Update category
                    $stmt = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, slug = ?, description = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$name, $slug, $description, $sort_order, $is_active, $id])) {
                        $success = "Categoria atualizada com sucesso!";
                    } else {
                        $error = "Erro ao atualizar categoria.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID inválido.";
        } else {
            try {
                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $error = "Não é possível excluir esta categoria pois ela possui $product_count produto(s) associado(s).";
                } else {
                    // Delete category
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    
                    if ($stmt->execute([$id])) {
                        $success = "Categoria excluída com sucesso!";
                    } else {
                        $error = "Erro ao excluir categoria.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Get categories from database
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erro ao carregar categorias: " . $e->getMessage();
}

$page_title = 'Categorias';
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
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="categories.php">
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
                <h5 class="mb-0">Gerenciar Categorias</h5>
                
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
                    <h2>Categorias</h2>
                    <p class="text-muted">Organize os produtos em categorias</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Nova Categoria
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

            <!-- Categories Grid -->
            <div class="row g-4">
                <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5>Nenhuma categoria encontrada</h5>
                            <p class="text-muted">Crie categorias para organizar seus produtos</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Primeira Categoria
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <?php
                                    $icons = [
                                        'agua' => 'fas fa-tint text-primary',
                                        'gas' => 'fas fa-fire text-danger',
                                        'limpeza' => 'fas fa-spray-can text-success',
                                        'mercearia' => 'fas fa-shopping-basket text-warning'
                                    ];
                                    $icon_class = $icons[$category['slug']] ?? 'fas fa-tag text-secondary';
                                    ?>
                                    <i class="<?php echo $icon_class; ?> fa-2x"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $category['is_active'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($category['description']): ?>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Ordem: <?php echo $category['sort_order']; ?></small>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="Editar" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="Excluir"
                                            onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Nova Categoria
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   placeholder="Ex: Água, Gás, Limpeza..." required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Descrição da categoria (opcional)"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="0" min="0">
                                <small class="text-muted">Menor número aparece primeiro</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-eye text-success me-1"></i>Categoria Ativa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Dica:</strong> Categorias ativas aparecem no site para os clientes navegarem.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Criar Categoria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Categoria
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_sort_order" class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                                <small class="text-muted">Menor número aparece primeiro</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        <i class="fas fa-eye text-success me-1"></i>Categoria Ativa
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

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Categoria
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="id" id="delete_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        
                        <p>Tem certeza que deseja excluir a categoria <strong id="delete_name"></strong>?</p>
                        
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Categorias com produtos associados não podem ser excluídas.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Categoria
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
            const modals = ['addCategoryModal', 'editCategoryModal', 'deleteCategoryModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                const modalInstance = mdb.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            
            // Reset add form
            const addForm = document.getElementById('addCategoryModal').querySelector('form');
            if (addForm) {
                addForm.reset();
                document.getElementById('is_active').checked = true;
            }
        });
        <?php endif; ?>
        
        // Edit category function
        function editCategory(category) {
            // Populate edit form
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_sort_order').value = category.sort_order;
            document.getElementById('edit_is_active').checked = category.is_active == 1;
            
            // Show edit modal
            const modal = new mdb.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
        
        // Delete category function
        function deleteCategory(id, name) {
            // Populate delete form
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            // Show delete modal
            const modal = new mdb.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }
    </script>
</body>
</html>
