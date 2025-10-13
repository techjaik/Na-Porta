<?php
// Simple home page for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Na Porta - Teste</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }";
echo ".btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🏠 Na Porta - E-commerce</h1>";
echo "<p>Bem-vindo ao Na Porta! Essenciais domésticos na sua porta.</p>";

echo "<h2>Categorias</h2>";
echo "<div>";
echo "<a href='#' class='btn'>💧 Água</a>";
echo "<a href='#' class='btn'>🔥 Gás</a>";
echo "<a href='#' class='btn'>🧽 Limpeza</a>";
echo "<a href='#' class='btn'>🛒 Mercearia</a>";
echo "</div>";

echo "<h2>Como Funciona</h2>";
echo "<ol>";
echo "<li><strong>Escolha:</strong> Navegue pelas categorias e escolha os produtos</li>";
echo "<li><strong>Peça:</strong> Adicione ao carrinho e finalize com PIX</li>";
echo "<li><strong>Acompanhe:</strong> Receba notificações em tempo real</li>";
echo "<li><strong>Receba:</strong> Entrega rápida e segura na sua porta</li>";
echo "</ol>";

echo "<h2>Links de Teste</h2>";
echo "<p><a href='test.php' class='btn'>Testar PHP</a></p>";
echo "<p><a href='simple-setup.php' class='btn'>Setup Simples</a></p>";

// Test if we can include config
echo "<h3>Teste de Configuração</h3>";
try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        echo "<p>✅ Arquivo de configuração carregado</p>";
        echo "<p>Nome do Site: " . (defined('SITE_NAME') ? SITE_NAME : 'Não definido') . "</p>";
    } else {
        echo "<p>❌ Arquivo de configuração não encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
