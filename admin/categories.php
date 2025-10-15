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
        $image_source = $_POST['image_source'] ?? 'url';
        $image_url = trim($_POST['image_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Nome da categoria é obrigatório.';
        } else {
            $file_path = '';

            try {
                // Handle image upload or URL
                if ($image_source === 'upload' && isset($_FILES['image_file'])) {
                    // Check if file was actually uploaded
                    if (empty($_FILES['image_file']['name'])) {
                        throw new Exception('Nenhum arquivo foi selecionado para upload.');
                    }

                    // Check for upload errors
                    $upload_error = $_FILES['image_file']['error'];
                    if ($upload_error !== UPLOAD_ERR_OK) {
                        $error_messages = [
                            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor).',
                            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário).',
                            UPLOAD_ERR_PARTIAL => 'Upload incompleto.',
                            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo selecionado.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado.',
                            UPLOAD_ERR_CANT_WRITE => 'Erro de escrita no disco.',
                            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão.'
                        ];
                        throw new Exception($error_messages[$upload_error] ?? 'Erro desconhecido no upload.');
                    }

                    // File validation
                    $upload_dir = __DIR__ . '/../uploads/categories/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    $file_info = $_FILES['image_file'];
                    $file_type = $file_info['type'];
                    $file_size = $file_info['size'];
                    $file_tmp_name = $file_info['tmp_name'];

                    // Additional validation for file type using file extension
                    $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (!in_array($file_extension, $allowed_extensions)) {
                        throw new Exception('Extensão de arquivo não permitida. Use JPG, PNG, GIF ou WebP.');
                    }

                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
                    }

                    if ($file_size > $max_size) {
                        throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
                    }

                    // Validate that the uploaded file is actually an image
                    if (!getimagesize($file_tmp_name)) {
                        throw new Exception('O arquivo enviado não é uma imagem válida.');
                    }

                    // Generate unique filename
                    $filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $file_path = 'uploads/categories/' . $filename;
                    $full_path = $upload_dir . $filename;

                    // Attempt to move the uploaded file
                    if (!move_uploaded_file($file_tmp_name, $full_path)) {
                        // More detailed error message
                        $error_details = error_get_last();
                        $error_msg = 'Erro ao fazer upload do arquivo.';
                        if ($error_details) {
                            $error_msg .= ' Detalhes: ' . $error_details['message'];
                        }
                        throw new Exception($error_msg);
                    }

                    // Verify the file was actually created
                    if (!file_exists($full_path)) {
                        throw new Exception('Arquivo não foi salvo corretamente no servidor.');
                    }
                } elseif ($image_source === 'url' && !empty($image_url)) {
                    // URL input
                    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                        throw new Exception('URL da imagem inválida.');
                    }
                    $file_path = $image_url;
                } else {
                    // Allow empty image for categories
                    $file_path = '';
                }

                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

                // Ensure unique slug
                $original_slug = $slug;
                $counter = 1;
                while (true) {
                    $existing = $db->fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
                    if (!$existing) {
                        break; // Slug is unique
                    }
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                }

                $stmt = $db->query("
                    INSERT INTO categories (name, slug, description, image, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ", [$name, $slug, $description, $file_path, $is_active]);

                if (!$stmt) {
                    throw new Exception('Erro ao salvar categoria no banco de dados.');
                }

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
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_category">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome da Categoria *</label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="Ex: Água Mineral">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Imagem da Categoria</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="image_source"
                                       id="upload_option" value="upload" checked>
                                <label class="form-check-label" for="upload_option">
                                    <i class="fas fa-upload me-1"></i>Upload do Computador
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="image_source"
                                       id="url_option" value="url">
                                <label class="form-check-label" for="url_option">
                                    <i class="fas fa-link me-1"></i>URL da Internet
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Upload File Option -->
                    <div id="upload_section" class="mt-3">
                        <input type="file" name="image_file" class="form-control"
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, WebP (máx. 5MB)</small>
                    </div>

                    <!-- URL Option -->
                    <div id="url_section" class="mt-3" style="display: none;">
                        <input type="url" name="image_url" class="form-control"
                               placeholder="https://exemplo.com/categoria.jpg">
                        <small class="text-muted">Cole o link direto da imagem da categoria</small>
                    </div>
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
                                                <?php
                                                // Handle both uploaded files and URLs
                                                $image_src = $category['image'];
                                                if (!filter_var($image_src, FILTER_VALIDATE_URL)) {
                                                    // It's a relative path, make it absolute
                                                    $image_src = '../' . $category['image'];
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($image_src) ?>"
                                                     alt="<?= htmlspecialchars($category['name']) ?>"
                                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
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

<script>
// Toggle between upload and URL options
document.addEventListener('DOMContentLoaded', function() {
    const uploadOption = document.getElementById('upload_option');
    const urlOption = document.getElementById('url_option');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');

    function toggleImageSource() {
        if (uploadOption.checked) {
            uploadSection.style.display = 'block';
            urlSection.style.display = 'none';
        } else {
            uploadSection.style.display = 'none';
            urlSection.style.display = 'block';
        }
    }

    uploadOption.addEventListener('change', toggleImageSource);
    urlOption.addEventListener('change', toggleImageSource);

    // Initialize
    toggleImageSource();
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
