<?php
/**
 * Na Porta - Admin Banners Management
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Banners';
$pageSubtitle = 'Gerenciar banners promocionais';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_banner') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_source = $_POST['image_source'] ?? 'upload';
        $image_url = trim($_POST['image_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title)) {
            $error = 'Título é obrigatório.';
        } else {
            $file_path = '';
            
            try {
                // Handle image upload or URL
                if ($image_source === 'upload' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    // File upload
                    $upload_dir = '../uploads/banners/';
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    $file_info = $_FILES['image_file'];
                    $file_type = $file_info['type'];
                    $file_size = $file_info['size'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
                    }
                    
                    if ($file_size > $max_size) {
                        throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                    $filename = 'banner_' . time() . '_' . uniqid() . '.' . $extension;
                    $file_path = 'uploads/banners/' . $filename;
                    $full_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($file_info['tmp_name'], $full_path)) {
                        throw new Exception('Erro ao fazer upload do arquivo.');
                    }
                } elseif ($image_source === 'url' && !empty($image_url)) {
                    // URL input
                    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                        throw new Exception('URL da imagem inválida.');
                    }
                    $file_path = $image_url;
                }
                
                // Insert banner
                $stmt = $db->query("
                    INSERT INTO promotional_banners (title, description, file_path, file_type, is_active, created_at) 
                    VALUES (?, ?, ?, 'image', ?, NOW())
                ", [$title, $description, $file_path, $is_active]);
                
                $success = "Banner adicionado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao adicionar banner: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete_banner') {
        $banner_id = intval($_POST['banner_id'] ?? 0);
        if ($banner_id > 0) {
            try {
                $db->query("UPDATE promotional_banners SET is_active = 0 WHERE id = ?", [$banner_id]);
                $success = "Banner removido com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao remover banner: " . $e->getMessage();
            }
        }
    }
}

// Get banners
$banners = [];
try {
    $banners = $db->fetchAll("
        SELECT * FROM promotional_banners 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
} catch (Exception $e) {
    $error = "Erro ao carregar banners: " . $e->getMessage();
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

<!-- Add Banner Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus me-2"></i>Adicionar Novo Banner
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_banner">
            
            <div class="mb-3">
                <label class="form-label">Título do Banner *</label>
                <input type="text" name="title" class="form-control" required 
                       placeholder="Ex: Promoção de Verão">
            </div>
            
            <!-- Image Source Selection -->
            <div class="mb-3">
                <label class="form-label">Imagem do Banner</label>
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
            </div>
            
            <!-- File Upload Option -->
            <div class="mb-3" id="upload_section">
                <label class="form-label">Selecionar Arquivo</label>
                <input type="file" name="image_file" class="form-control" 
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <small class="text-muted">
                    Formatos aceitos: JPG, PNG, GIF, WebP. Tamanho máximo: 5MB. 
                    Recomendado: 1200x600px para melhor qualidade.
                </small>
            </div>
            
            <!-- URL Input Option -->
            <div class="mb-3 d-none" id="url_section">
                <label class="form-label">URL da Imagem</label>
                <input type="url" name="image_url" class="form-control" 
                       placeholder="https://exemplo.com/imagem.jpg">
                <small class="text-muted">
                    Cole a URL completa da imagem (deve começar com http:// ou https://)
                </small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Descrição do banner..."></textarea>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                    <label class="form-check-label" for="is_active">
                        Banner Ativo
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Adicionar Banner
            </button>
        </form>
    </div>
</div>

<!-- Banners List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-images me-2"></i>Banners Ativos (<?= count($banners) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($banners)): ?>
            <div class="text-center py-5">
                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum banner cadastrado</h5>
                <p class="text-muted">Adicione seu primeiro banner usando o formulário acima.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagem</th>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><strong>#<?= $banner['id'] ?></strong></td>
                                <td>
                                    <?php if (!empty($banner['file_path'])): ?>
                                        <img src="<?= htmlspecialchars($banner['file_path']) ?>" 
                                             alt="Banner" class="img-thumbnail" 
                                             style="width: 80px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 40px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <h6 class="mb-1"><?= htmlspecialchars($banner['title']) ?></h6>
                                </td>
                                <td>
                                    <?php if ($banner['description']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(substr($banner['description'], 0, 100)) ?>...
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($banner['is_active']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($banner['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja remover este banner?')">
                                            <input type="hidden" name="action" value="delete_banner">
                                            <input type="hidden" name="banner_id" value="<?= $banner['id'] ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const uploadOption = document.getElementById('upload_option');
    const urlOption = document.getElementById('url_option');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');
    
    function toggleImageSource() {
        if (uploadOption.checked) {
            uploadSection.classList.remove('d-none');
            urlSection.classList.add('d-none');
            // Clear URL input when switching to upload
            document.querySelector('input[name="image_url"]').value = '';
        } else {
            uploadSection.classList.add('d-none');
            urlSection.classList.remove('d-none');
            // Clear file input when switching to URL
            document.querySelector('input[name="image_file"]').value = '';
        }
    }
    
    uploadOption.addEventListener('change', toggleImageSource);
    urlOption.addEventListener('change', toggleImageSource);
    
    // File preview
    const fileInput = document.querySelector('input[name="image_file"]');
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Remove existing preview
            const existingPreview = document.getElementById('image_preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Create new preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.id = 'image_preview';
                preview.className = 'mt-2';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" 
                         class="img-thumbnail" style="max-width: 200px; max-height: 100px; object-fit: cover;">
                    <small class="d-block text-muted mt-1">Preview da imagem</small>
                `;
                fileInput.parentNode.appendChild(preview);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
