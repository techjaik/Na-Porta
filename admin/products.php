<?php
/**
 * Na Porta - Admin Products Management
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Produtos';
$pageSubtitle = 'Gerenciar produtos do catálogo';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || $price <= 0 || $category_id <= 0) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                $stmt = $db->query("
                    INSERT INTO products (name, slug, description, price, category_id, image_url, is_featured, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ", [$name, $slug, $description, $price, $category_id, $image_url, $is_featured, $is_active]);
                
                $success = "Produto adicionado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao adicionar produto: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete_product') {
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            try {
                $db->query("UPDATE products SET is_active = 0 WHERE id = ?", [$product_id]);
                $success = "Produto removido com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao remover produto: " . $e->getMessage();
            }
        }
    }
}

// Get products
$products = [];
$categories = [];
try {
    $products = $db->fetchAll("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 
        ORDER BY p.created_at DESC
    ");
    
    $categories = $db->fetchAll("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
} catch (Exception $e) {
    $error = "Erro ao carregar produtos: " . $e->getMessage();
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Success/Error Messages -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Add Product Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus me-2"></i>Adicionar Novo Produto
        </h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add_product">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Produto *</label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="Ex: Água Mineral 20L">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Categoria *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Preço (R$) *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required 
                           placeholder="0.00">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL da Imagem</label>
                    <input type="url" name="image_url" class="form-control" 
                           placeholder="https://exemplo.com/imagem.jpg">
                    <small class="text-muted">Cole o link direto da imagem</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Opções</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured">
                            <label class="form-check-label" for="is_featured">
                                Produto em Destaque
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Produto Ativo
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Descrição detalhada do produto..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Adicionar Produto
            </button>
        </form>
    </div>
</div>

<!-- Products List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Lista de Produtos (<?= count($products) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum produto cadastrado</h5>
                <p class="text-muted">Adicione seu primeiro produto usando o formulário acima.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong>#<?= $product['id'] ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($product['image_url']): ?>
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                     class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                            <?php if ($product['description']): ?>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($product['category_name'] ?? 'Sem categoria') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        R$ <?= number_format($product['price'], 2, ',', '.') ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_featured']): ?>
                                            <span class="badge bg-warning">Destaque</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja remover este produto?')">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    data-bs-toggle="tooltip" title="Remover">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
