<?php
// Na Porta - Database Setup Script
// Run this file once to set up the database

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
    
    // Check if tables exist
    $tables_exist = false;
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'users'");
        $tables_exist = $result->rowCount() > 0;
    } catch (Exception $e) {
        $tables_exist = false;
    }
    
    if (!$tables_exist) {
        // Read and execute schema
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        
        if ($schema) {
            // Split by semicolon and execute each statement
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        // Skip if table already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
        }
    } else {
        echo "<p>⚠️ Database tables already exist - skipping schema creation</p>";
    }
        
        echo "<p>✅ Database schema imported successfully</p>";
        
        // Add some sample products
        $sampleProducts = [
            [
                'category_id' => 1, // Água
                'name' => 'Água Mineral 20L',
                'slug' => 'agua-mineral-20l',
                'description' => 'Água mineral natural de fonte, galão de 20 litros para sua casa ou escritório.',
                'short_description' => 'Água mineral natural 20L',
                'price' => 15.90,
                'sku' => 'AGUA-20L-001',
                'stock_quantity' => 50,
                'is_featured' => 1
            ],
            [
                'category_id' => 2, // Gás
                'name' => 'Botijão de Gás 13kg',
                'slug' => 'botijao-gas-13kg',
                'description' => 'Botijão de gás de cozinha 13kg, ideal para uso doméstico.',
                'short_description' => 'Botijão de gás 13kg',
                'price' => 85.00,
                'sku' => 'GAS-13KG-001',
                'stock_quantity' => 25,
                'is_featured' => 1
            ],
            [
                'category_id' => 3, // Limpeza
                'name' => 'Kit Limpeza Completo',
                'slug' => 'kit-limpeza-completo',
                'description' => 'Kit completo com produtos de limpeza: detergente, desinfetante, álcool gel e panos.',
                'short_description' => 'Kit limpeza completo',
                'price' => 45.50,
                'sku' => 'LIMP-KIT-001',
                'stock_quantity' => 30,
                'is_featured' => 1
            ],
            [
                'category_id' => 4, // Mercearia
                'name' => 'Cesta Básica Familiar',
                'slug' => 'cesta-basica-familiar',
                'description' => 'Cesta básica completa com arroz, feijão, óleo, açúcar e outros itens essenciais.',
                'short_description' => 'Cesta básica familiar',
                'price' => 120.00,
                'sku' => 'MERC-CESTA-001',
                'stock_quantity' => 15,
                'is_featured' => 1
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, name, slug, description, short_description, price, sku, stock_quantity, is_featured, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($sampleProducts as $product) {
            $stmt->execute([
                $product['category_id'],
                $product['name'],
                $product['slug'],
                $product['description'],
                $product['short_description'],
                $product['price'],
                $product['sku'],
                $product['stock_quantity'],
                $product['is_featured']
            ]);
        }
        
        echo "<p>✅ Sample products added</p>";
        
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🎉 Setup Complete!</h3>";
        echo "<p><strong>Your Na Porta e-commerce platform is ready!</strong></p>";
        echo "<p><a href='pages/home.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Visit Homepage</a></p>";
        echo "<p><a href='admin/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Panel</a></p>";
        echo "<p><strong>Admin Credentials:</strong><br>";
        echo "Username: <code>admin</code><br>";
        echo "Password: <code>admin123</code></p>";
        echo "<p><em>Remember to change the admin password after first login!</em></p>";
        echo "</div>";
        
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>Delete this setup.php file for security</li>";
        echo "<li>Update config/config.php with your settings</li>";
        echo "<li>Set up payment gateway credentials</li>";
        echo "<li>Configure email settings</li>";
        echo "<li>Add your products and categories</li>";
        echo "</ul>";
        
    } else {
        throw new Exception("Could not read schema file");
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
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

ul {
    background: white;
    padding: 20px;
    border-radius: 5px;
}
</style>
