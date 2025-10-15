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
        $image_source = $_POST['image_source'] ?? 'url';
        $image_url = trim($_POST['image_url'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || $price <= 0 || $category_id <= 0) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
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
                    $upload_dir = __DIR__ . '/../uploads/products/';
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
                    $filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $file_path = 'uploads/products/' . $filename;
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
                    // No file uploaded and no URL provided
                    if ($image_source === 'upload') {
                        throw new Exception('Por favor, selecione um arquivo para upload.');
                    } else {
                        throw new Exception('Por favor, forneça uma URL válida para a imagem.');
                    }
                }

                // Ensure we have a file path before inserting
                if (empty($file_path)) {
                    throw new Exception('Caminho do arquivo não foi definido corretamente.');
                }

                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

                // Ensure unique slug
                $original_slug = $slug;
                $counter = 1;
                while (true) {
                    $existing = $db->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
                    if (!$existing) {
                        break; // Slug is unique
                    }
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                }

                $stmt = $db->query("
                    INSERT INTO products (name, slug, description, price, category_id, image_url, is_featured, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ", [$name, $slug, $description, $price, $category_id, $file_path, $is_featured, $is_active]);

                if (!$stmt) {
                    throw new Exception('Erro ao salvar produto no banco de dados.');
                }

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
        <form method="POST" enctype="multipart/form-data">
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
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Imagem do Produto</label>
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
                               placeholder="https://exemplo.com/imagem.jpg">
                        <small class="text-muted">Cole o link direto da imagem</small>
                    </div>
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
