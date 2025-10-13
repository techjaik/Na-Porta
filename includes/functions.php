<?php
require_once __DIR__ . '/../config/config.php';

// Security Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication Functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit();
    }
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
}

// Validation Functions
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Check for known invalid CPFs
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validate CPF algorithm
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

function validate_cep($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    return strlen($cep) == 8;
}

function format_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function format_cep($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
}

function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    } elseif (strlen($phone) == 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

// Utility Functions
function format_currency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function generate_order_number() {
    return 'NP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . ' h atrás';
    if ($time < 2592000) return floor($time/86400) . ' dias atrás';
    if ($time < 31536000) return floor($time/2592000) . ' meses atrás';
    
    return floor($time/31536000) . ' anos atrás';
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function flash_message($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// File Upload Functions
function upload_image($file, $directory = 'products') {
    $upload_dir = UPLOAD_PATH . $directory . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Arquivo muito grande'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $directory . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Erro ao fazer upload'];
}

// Cart Functions
function get_cart_count() {
    global $pdo;
    
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    
    return $stmt->fetchColumn() ?: 0;
}

function get_cart_total() {
    global $pdo;
    
    if (is_logged_in()) {
        $stmt = $pdo->prepare("
            SELECT SUM(ci.quantity * p.price) 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT SUM(ci.quantity * p.price) 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.session_id = ?
        ");
        $stmt->execute([$session_id]);
    }
    
    return $stmt->fetchColumn() ?: 0;
}

// Email Functions
function send_email($to, $subject, $body, $is_html = true) {
    // This would integrate with a proper email service
    // For now, we'll just log the email
    error_log("Email to: $to, Subject: $subject");
    return true;
}

// Brazilian Address Functions
function get_address_by_cep($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    
    if (strlen($cep) != 8) {
        return false;
    }
    
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!isset($data['erro'])) {
            return [
                'cep' => $data['cep'],
                'street' => $data['logradouro'],
                'neighborhood' => $data['bairro'],
                'city' => $data['localidade'],
                'state' => $data['uf']
            ];
        }
    }
    
    return false;
}

// LGPD Compliance
function get_lgpd_consent() {
    return isset($_SESSION['lgpd_consent']) && $_SESSION['lgpd_consent'] === true;
}

function set_lgpd_consent($consent = true) {
    $_SESSION['lgpd_consent'] = $consent;
}

function require_lgpd_consent() {
    if (!get_lgpd_consent()) {
        return false;
    }
    return true;
}
?>
