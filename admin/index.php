<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(url('public/index.php'));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($orderId > 0 && in_array($action, ['confirm', 'cancel'], true)) {
        if (updateOrderState($pdo, $orderId, $action)) {
            $message = $action === 'confirm' ? 'Orden confirmada correctamente.' : 'Orden cancelada y cupos restaurados.';
        } else {
            $error = 'No fue posible actualizar la orden.';
        }
    }
}

$stats = getOrderStats($pdo);
$recentOrders = getAllOrders($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <div class="flex-between">
            <h1>Panel de Administración</h1>
            <a href="add-event.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo Evento
            </a>
        </div>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;"><?= e($error) ?></div>
        <?php endif; ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total de Órdenes</h4>
                <div class="value"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h4>Ingresos Totales</h4>
                <div class="value">$<?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <h4>Eventos Activos</h4>
                <div class="value"><?= $stats['total_events'] ?></div>
            </div>
        </div>
        
        <div class="dashboard-content mt-3">
            <h3 style="margin-bottom: 20px;">Órdenes Recientes</h3>
            
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No hay órdenes</h3>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Evento</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentOrders, 0, 10) as $order): ?>
                            <tr>
                                <td><code><?= e($order['order_number']) ?></code></td>
                                <td><?= e($order['customer_name']) ?></td>
                                <td><?= e($order['event_title']) ?></td>
                                <td>$<?= number_format($order['total'], 0, ',', '.') ?></td>
                                <td><?= formatDate($order['created_at']) ?></td>
                                <td>
                                    <span class="status-badge <?= e($order['payment_status']) ?>">
                                        <?= e(ucfirst($order['payment_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="viewOrder(<?= $order['id'] ?>)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn btn-sm btn-secondary">Confirmar</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('¿Cancelar esta orden?')">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="modal" id="orderModal">
    <div class="modal-content">
        <h3 style="margin-bottom: 20px;">Detalles de la Orden</h3>
        <div id="orderDetails"></div>
        <button class="btn btn-outline" style="width: 100%; margin-top: 20px;" onclick="closeModal()">Cerrar</button>
    </div>
</div>

<script>
async function viewOrder(orderId) {
    const response = await fetch('../public/api/order-details.php?id=' + orderId);
    const data = await response.json();

    if (data.error) {
        alert(data.error);
        return;
    }
    
    document.getElementById('orderDetails').innerHTML = `
        <p><strong>Orden:</strong> ${data.order_number}</p>
        <p><strong>Cliente:</strong> ${data.customer_name}</p>
        <p><strong>Email:</strong> ${data.customer_email}</p>
        <p><strong>Evento:</strong> ${data.event_title}</p>
        <p><strong>Total:</strong> $${data.total.toLocaleString('es-CO')}</p>
        <p><strong>Estado de pago:</strong> ${data.payment_status}</p>
        <p><strong>Estado interno:</strong> ${data.status}</p>
    `;
    document.getElementById('orderModal').classList.add('active');
}

function closeModal() {
    document.getElementById('orderModal').classList.remove('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
