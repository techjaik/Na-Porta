<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$consent = $input['consent'] ?? false;

// Set LGPD consent in session
set_lgpd_consent($consent);

// If consent is given, set a cookie for future visits
if ($consent) {
    setcookie('lgpd_consent', '1', time() + (365 * 24 * 60 * 60), '/'); // 1 year
} else {
    // Clear consent cookie if declined
    setcookie('lgpd_consent', '', time() - 3600, '/');
}

echo json_encode([
    'success' => true,
    'message' => $consent ? 'Consentimento registrado' : 'Consentimento recusado',
    'consent' => $consent
]);
?>
