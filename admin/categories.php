<?php
/**
 * Na Porta - Admin Categories Management
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Categorias';
$pageSubtitle = 'Gerenciar categorias de produtos';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Nome da categoria é obrigatório.';
        } else {
            try {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                $stmt = $db->query("
                    INSERT INTO categories (name, slug, description, image, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ", [$name, $slug, $description, $image_url, $is_active]);
                
                $success = "Categoria adicionada com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao adicionar categoria: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete_category') {
        $category_id = intval($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            try {
                $db->query("UPDATE categories SET is_active = 0 WHERE id = ?", [$category_id]);
                $success = "Categoria removida com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao remover categoria: " . $e->getMessage();
            }
        }
    }
}

// Get categories
$categories = [];
try {
    $categories = $db->fetchAll("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        WHERE c.is_active = 1 
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
} catch (Exception $e) {
    $error = "Erro ao carregar categorias: " . $e->getMessage();
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

<!-- Add Category Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus me-2"></i>Adicionar Nova Categoria
        </h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add_category">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome da Categoria *</label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="Ex: Água Mineral">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL da Imagem</label>
                    <input type="url" name="image_url" class="form-control" 
                           placeholder="https://exemplo.com/categoria.jpg">
                    <small class="text-muted">Cole o link direto da imagem da categoria</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Categoria Ativa
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Descrição da categoria..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Adicionar Categoria
            </button>
        </form>
    </div>
</div>

<!-- Categories Grid -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tags me-2"></i>Categorias (<?= count($categories) ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhuma categoria cadastrada</h5>
                <p class="text-muted">Adicione sua primeira categoria usando o formulário acima.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($category['image']): ?>
                                                <img src="<?= htmlspecialchars($category['image']) ?>" 
                                                     alt="<?= htmlspecialchars($category['name']) ?>"
                                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <?php
                                                $icons = [
                                                    'agua' => 'fas fa-tint text-primary',
                                                    'gas' => 'fas fa-fire text-danger',
                                                    'limpeza' => 'fas fa-spray-can text-success',
                                                    'mercearia' => 'fas fa-shopping-basket text-warning'
                                                ];
                                                $icon = $icons[$category['slug']] ?? 'fas fa-tag text-secondary';
                                                ?>
                                                <div class="p-3 rounded-circle bg-light">
                                                    <i class="<?= $icon ?> fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($category['name']) ?></h6>
                                            <small class="text-muted">
                                                <?= $category['product_count'] ?> produto(s)
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   data-bs-toggle="tooltip" title="Editar categoria">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja remover esta categoria?')">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-2"></i>Remover
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <?php if ($category['description']): ?>
                                    <p class="card-text text-muted small">
                                        <?= htmlspecialchars($category['description']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($category['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
