<?php
/**
 * Debug Category Form Submission
 * Test the exact same process as the admin form
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h2>Category Form Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>🔍 Form Submission Debug</h3>";
    
    // Show all POST data
    echo "<h4>POST Data Received:</h4>";
    echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";
    
    // Show all FILES data
    echo "<h4>FILES Data Received:</h4>";
    echo "<pre>" . htmlspecialchars(print_r($_FILES, true)) . "</pre>";
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_source = $_POST['image_source'] ?? 'url';
        $image_url = trim($_POST['image_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        echo "<h4>Processed Variables:</h4>";
        echo "<ul>";
        echo "<li>Name: '" . htmlspecialchars($name) . "'</li>";
        echo "<li>Description: '" . htmlspecialchars($description) . "'</li>";
        echo "<li>Image Source: '" . htmlspecialchars($image_source) . "'</li>";
        echo "<li>Image URL: '" . htmlspecialchars($image_url) . "'</li>";
        echo "<li>Is Active: " . ($is_active ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        
        if (empty($name)) {
            throw new Exception('Nome da categoria é obrigatório.');
        }
        
        $file_path = '';
        
        echo "<h4>🔄 Processing Image...</h4>";
        
        // Handle image upload or URL
        if ($image_source === 'upload' && isset($_FILES['image_file'])) {
            echo "<p>📁 Processing file upload...</p>";
            
            // Check if file was actually uploaded
            if (empty($_FILES['image_file']['name'])) {
                throw new Exception('Nenhum arquivo foi selecionado para upload.');
            }
            
            echo "<p>✅ File selected: " . htmlspecialchars($_FILES['image_file']['name']) . "</p>";
            
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
            
            echo "<p>✅ No upload errors</p>";

            // File validation
            $upload_dir = __DIR__ . '/uploads/categories/';
            if (!is_dir($upload_dir)) {
                echo "<p>📁 Creating upload directory...</p>";
                mkdir($upload_dir, 0755, true);
            }
            
            echo "<p>✅ Upload directory ready: " . htmlspecialchars($upload_dir) . "</p>";
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_info = $_FILES['image_file'];
            $file_type = $file_info['type'];
            $file_size = $file_info['size'];
            $file_tmp_name = $file_info['tmp_name'];
            
            echo "<p>File type: " . htmlspecialchars($file_type) . "</p>";
            echo "<p>File size: " . number_format($file_size) . " bytes</p>";
            
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
            
            echo "<p>✅ File validation passed</p>";
            
            // Generate unique filename
            $filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = 'uploads/categories/' . $filename;
            $full_path = $upload_dir . $filename;
            
            echo "<p>Target filename: " . htmlspecialchars($filename) . "</p>";
            echo "<p>Full path: " . htmlspecialchars($full_path) . "</p>";
            
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
            
            echo "<p>✅ File uploaded successfully!</p>";
            
        } elseif ($image_source === 'url' && !empty($image_url)) {
            echo "<p>🔗 Processing URL input...</p>";
            // URL input
            if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                throw new Exception('URL da imagem inválida.');
            }
            $file_path = $image_url;
            echo "<p>✅ URL validated: " . htmlspecialchars($image_url) . "</p>";
        } else {
            // Allow empty image for categories
            $file_path = '';
            echo "<p>ℹ️ No image provided (allowed for categories)</p>";
        }
        
        echo "<h4>💾 Database Insert</h4>";
        echo "<p>Final file path: '" . htmlspecialchars($file_path) . "'</p>";
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        echo "<p>Generated slug: '" . htmlspecialchars($slug) . "'</p>";
        
        echo "<p>🔄 Executing SQL query...</p>";
        
        $sql = "INSERT INTO categories (name, slug, description, image, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $params = [$name, $slug, $description, $file_path, $is_active];
        
        echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
        echo "<p>Parameters: " . htmlspecialchars(json_encode($params)) . "</p>";
        
        $stmt = $db->query($sql, $params);
        
        if (!$stmt) {
            throw new Exception('Erro ao salvar categoria no banco de dados.');
        }
        
        $insert_id = $db->lastInsertId();
        echo "<p>✅ <strong>SUCCESS!</strong> Category inserted with ID: $insert_id</p>";
        
        // Show the inserted record
        $inserted = $db->fetch("SELECT * FROM categories WHERE id = ?", [$insert_id]);
        echo "<h4>Inserted Record:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($inserted, true)) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Stack trace:</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<p>Use the form below to test category creation with detailed debugging:</p>";
}
?>

<h3>Test Category Form</h3>
<form method="POST" enctype="multipart/form-data">
    <p>
        <label>Category Name *:</label><br>
        <input type="text" name="name" required value="Test Category <?= time() ?>">
    </p>
    
    <p>
        <label>Description:</label><br>
        <textarea name="description">Test category description</textarea>
    </p>
    
    <p>
        <label>Image Source:</label><br>
        <input type="radio" name="image_source" value="upload" id="upload" checked>
        <label for="upload">Upload do Computador</label><br>
        <input type="radio" name="image_source" value="url" id="url">
        <label for="url">URL da Internet</label>
    </p>
    
    <p id="upload_section">
        <label>Upload File:</label><br>
        <input type="file" name="image_file" accept="image/*">
    </p>
    
    <p id="url_section" style="display:none;">
        <label>Image URL:</label><br>
        <input type="url" name="image_url" placeholder="https://example.com/image.jpg">
    </p>
    
    <p>
        <input type="checkbox" name="is_active" id="active" checked>
        <label for="active">Active</label>
    </p>
    
    <p>
        <button type="submit">Test Add Category</button>
    </p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadRadio = document.getElementById('upload');
    const urlRadio = document.getElementById('url');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');
    
    function toggle() {
        if (uploadRadio.checked) {
            uploadSection.style.display = 'block';
            urlSection.style.display = 'none';
        } else {
            uploadSection.style.display = 'none';
            urlSection.style.display = 'block';
        }
    }
    
    uploadRadio.addEventListener('change', toggle);
    urlRadio.addEventListener('change', toggle);
    toggle();
});
</script>

<p><a href="admin/categories.php">← Back to Categories Admin</a></p>
