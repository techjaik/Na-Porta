<?php
/**
 * Na Porta - Print Order Page
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    die('ID de pedido inválido');
}

try {
    // Get order details
    $order = $db->fetch("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ", [$orderId]);
    
    if (!$order) {
        die('Pedido não encontrado');
    }
    
    // Get order items
    $orderItems = $db->fetchAll("
        SELECT oi.*, p.name as product_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at ASC
    ", [$orderId]);
    
} catch (Exception $e) {
    die('Erro ao carregar pedido: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= $order['id'] ?> - Na Porta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #6366f1;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #d1ecf1; color: #0c5460; }
        .status-shipped { background-color: #cce5ff; color: #004085; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Na Porta</div>
        <div>Delivery de Água, Gás e Mais</div>
    </div>
    
    <div class="order-info">
        <div>
            <strong>Pedido #<?= $order['id'] ?></strong><br>
            Data: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
            Status: <span class="status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
        </div>
        <div style="text-align: right;">
            <strong>Total: R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></strong><br>
            Pagamento: <?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?><br>
            Status Pag.: <span class="status status-<?= $order['payment_status'] ?? 'pending' ?>"><?= ucfirst($order['payment_status'] ?? 'pending') ?></span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Informações do Cliente</div>
        <strong>Nome:</strong> <?= htmlspecialchars($order['user_name'] ?? 'N/A') ?><br>
        <strong>Email:</strong> <?= htmlspecialchars($order['user_email'] ?? 'N/A') ?><br>
        <?php if ($order['user_phone']): ?>
        <strong>Telefone:</strong> <?= htmlspecialchars($order['user_phone']) ?><br>
        <?php endif; ?>
    </div>
    
    <?php if ($order['delivery_address']): ?>
    <div class="section">
        <div class="section-title">Endereço de Entrega</div>
        <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <div class="section-title">Itens do Pedido</div>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Preço Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name'] ?? 'Produto removido') ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Total do Pedido</td>
                    <td>R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if ($order['notes']): ?>
    <div class="section">
        <div class="section-title">Observações</div>
        <?= nl2br(htmlspecialchars($order['notes'])) ?>
    </div>
    <?php endif; ?>
    
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Imprimir Pedido
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Fechar
        </button>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
