<?php
// Site Configuration
define('SITE_NAME', 'Na Porta');
define('SITE_URL', 'http://localhost/Na%20Porta');
define('SITE_EMAIL', 'contato@naporta.com.br');

// Payment Configuration
define('MERCADO_PAGO_ACCESS_TOKEN', 'YOUR_MERCADO_PAGO_ACCESS_TOKEN');
define('MERCADO_PAGO_PUBLIC_KEY', 'YOUR_MERCADO_PAGO_PUBLIC_KEY');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Security
define('JWT_SECRET', 'your-jwt-secret-key-here');
define('ENCRYPTION_KEY', 'your-encryption-key-here');

// File Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', 'uploads/');

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Currency
define('CURRENCY', 'BRL');
define('CURRENCY_SYMBOL', 'R$');

// Tax Rate (%)
define('TAX_RATE', 0.00); // No tax for essentials in Brazil

// Delivery
define('FREE_DELIVERY_THRESHOLD', 50.00);
define('DELIVERY_FEE', 8.00);
?>
