-- Na Porta Database Schema
-- Brazilian E-commerce Platform for Household Essentials

CREATE DATABASE IF NOT EXISTS na_porta_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE na_porta_db;

-- Users table
CREATE TABLE users (
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
);

-- User addresses
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    street VARCHAR(200) NOT NULL,
    number VARCHAR(10) NOT NULL,
    complement VARCHAR(100),
    neighborhood VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(2) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin users
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'manager', 'editor') DEFAULT 'editor',
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE products (
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
    meta_title VARCHAR(200),
    meta_description VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Product images
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(200),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Shopping cart
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_id VARCHAR(255),
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Delivery address
    delivery_name VARCHAR(100) NOT NULL,
    delivery_cep VARCHAR(9) NOT NULL,
    delivery_street VARCHAR(200) NOT NULL,
    delivery_number VARCHAR(10) NOT NULL,
    delivery_complement VARCHAR(100),
    delivery_neighborhood VARCHAR(100) NOT NULL,
    delivery_city VARCHAR(100) NOT NULL,
    delivery_state VARCHAR(2) NOT NULL,
    
    notes TEXT,
    estimated_delivery DATE,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Coupons
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    usage_limit INT,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    starts_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coupon usage tracking
CREATE TABLE coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Support tickets
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Site settings
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Newsletter subscriptions
CREATE TABLE newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Promotional banners
CREATE TABLE promotional_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
    link_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Água', 'agua', 'Água mineral e galões para sua casa', 1),
('Gás', 'gas', 'Botijões de gás para cozinha', 2),
('Limpeza', 'limpeza', 'Produtos de limpeza doméstica', 3),
('Mercearia', 'mercearia', 'Itens básicos de mercearia', 4);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, email, password, role, name) VALUES
('admin', 'admin@naporta.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Administrador');

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Na Porta', 'text', 'Nome do site'),
('site_description', 'Essenciais domésticos na sua porta', 'text', 'Descrição do site'),
('contact_email', 'contato@naporta.com.br', 'text', 'Email de contato'),
('contact_phone', '(11) 99999-9999', 'text', 'Telefone de contato'),
('delivery_fee', '8.00', 'number', 'Taxa de entrega'),
('free_delivery_threshold', '50.00', 'number', 'Valor mínimo para frete grátis'),
('lgpd_enabled', 'true', 'boolean', 'LGPD habilitado'),
('maintenance_mode', 'false', 'boolean', 'Modo de manutenção');
