<?php
// Na Porta - Database Setup Script (Fixed Version)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'na_porta_db';

echo "<h1>Na Porta - Database Setup</h1>";

try {
    // Connect to MySQL (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$database' created/verified</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin user exists
    $admin_exists = false;
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
        $admin_exists = $result->fetchColumn() > 0;
    } catch (Exception $e) {
        // Table doesn't exist yet
        $admin_exists = false;
    }
    
    if (!$admin_exists) {
        // Create basic tables if they don't exist
        
        // Users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            cpf VARCHAR(14) UNIQUE,
            phone VARCHAR(20),
            birth_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            email_verified BOOLEAN DEFAULT FALSE,
            verification_token VARCHAR(255),
            reset_token VARCHAR(255),
            reset_token_expires TIMESTAMP NULL
        )");
        
        // Admin users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'manager', 'editor') DEFAULT 'editor',
            name VARCHAR(100) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )");
        
        // Categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            image VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Products table
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) UNIQUE NOT NULL,
            description TEXT,
            short_description VARCHAR(500),
            price DECIMAL(10,2) NOT NULL,
            compare_price DECIMAL(10,2),
            sku VARCHAR(100) UNIQUE,
            stock_quantity INT DEFAULT 0,
            min_stock_level INT DEFAULT 5,
            weight DECIMAL(8,3),
            dimensions VARCHAR(50),
            is_active BOOLEAN DEFAULT TRUE,
            is_featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        
        echo "<p>✅ Basic tables created</p>";
        
        // Insert default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin_users (username, email, password, role, name) VALUES (?, ?, ?, ?, ?)")
            ->execute(['admin', 'admin@naporta.com', $admin_password, 'super_admin', 'Administrator']);
        
        echo "<p>✅ Admin user created</p>";
        
        // Insert default categories
        $categories = [
            ['Água', 'agua', 'Água mineral e galões'],
            ['Gás', 'gas', 'Botijões de gás de cozinha'],
            ['Limpeza', 'limpeza', 'Produtos de limpeza doméstica'],
            ['Mercearia', 'mercearia', 'Itens básicos de mercearia']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, is_active, sort_order) VALUES (?, ?, ?, 1, ?)");
        foreach ($categories as $index => $category) {
            $stmt->execute([$category[0], $category[1], $category[2], $index + 1]);
        }
        
        echo "<p>✅ Default categories created</p>";
        
        // Insert sample products
        $products = [
            [1, 'Água Mineral 20L', 'agua-mineral-20l', 'Água mineral natural 20L', 15.90, 'AGUA-001', 50, 1],
            [2, 'Botijão de Gás 13kg', 'botijao-gas-13kg', 'Botijão de gás 13kg', 85.00, 'GAS-001', 25, 1],
            [3, 'Kit Limpeza Completo', 'kit-limpeza-completo', 'Kit limpeza completo', 45.50, 'LIMP-001', 30, 1],
            [4, 'Cesta Básica Familiar', 'cesta-basica-familiar', 'Cesta básica familiar', 120.00, 'MERC-001', 15, 1]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, short_description, price, sku, stock_quantity, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        
        echo "<p>✅ Sample products created</p>";
        
    } else {
        echo "<p>⚠️ Database already set up - skipping initialization</p>";
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p><strong>Your Na Porta e-commerce platform is ready!</strong></p>";
    echo "<p><a href='simple-home.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Visit Simple Homepage</a></p>";
    echo "<p><a href='pages/home.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Visit Full Homepage</a></p>";
    echo "<p><a href='admin/login.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Panel</a></p>";
    echo "<p><strong>Admin Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>admin123</code></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h1 {
    color: #333;
    text-align: center;
}

p {
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
