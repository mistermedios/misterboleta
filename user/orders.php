<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$orders = getUserOrders($pdo, $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Mis Órdenes</h1>
    </div>
</section>

<section class="user-dashboard">
    <div class="container">
        <div class="dashboard-grid">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="profile.php"><i class="fa-solid fa-user"></i> Mi Perfil</a></li>
                    <li><a href="my-tickets.php"><i class="fa-solid fa-ticket"></i> Mis Boletas</a></li>
                    <li><a href="orders.php" class="active"><i class="fa-solid fa-receipt"></i> Mis Órdenes</a></li>
                    <li>
                        <form method="POST" action="<?= e(url('user/logout.php')) ?>">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-outline" style="width: 100%;">
                                <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesion
                            </button>
                        </form>
                    </li>
                </ul>
            </aside>
            
            <div class="dashboard-content">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No tienes órdenes</h3>
                        <p>Tu historial de compras aparecerá aquí.</p>
                        <a href="<?= e(url('public/index.php')) ?>" class="btn btn-primary mt-2">Ver Eventos</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Evento</th>
                                <th>Boletas</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><code><?= e($order['order_number']) ?></code></td>
                                    <td><?= e($order['event_title']) ?></td>
                                    <td><?= $order['tickets_count'] ?></td>
                                    <td>$<?= number_format($order['total'], 0, ',', '.') ?></td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <span class="status-badge <?= e($order['payment_status']) ?>">
                                            <?php switch($order['payment_status']):
                                                case 'paid': echo 'Pagado'; break;
                                                case 'pending': echo 'Pendiente'; break;
                                                case 'failed': echo 'Fallido'; break;
                                                case 'refunded': echo 'Reembolsado'; break;
                                                default: echo $order['payment_status']; endswitch; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="viewOrderDetails(<?= $order['id'] ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
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
async function viewOrderDetails(orderId) {
    const response = await fetch(`../public/api/order-details.php?id=${orderId}`);
    const data = await response.json();

    if (data.error) {
        alert(data.error);
        return;
    }
    
    let html = `
        <p><strong>Orden:</strong> ${data.order_number}</p>
        <p><strong>Evento:</strong> ${data.event_title}</p>
        <p><strong>Fecha:</strong> ${data.event_date}</p>
        <p><strong>Lugar:</strong> ${data.venue}, ${data.city}</p>
        <p><strong>Total:</strong> $${data.total.toLocaleString('es-CO')}</p>
        <hr style="border-color: var(--border); margin: 15px 0;">
        <h4>Boletas:</h4>
    `;
    
    data.items.forEach(item => {
        html += `
            <div style="padding: 10px; background: var(--bg-dark); border-radius: 8px; margin-top: 10px;">
                <strong>${item.zone_name}</strong><br>
                <small>Código: ${item.ticket_code}${item.seat ? ' • Asiento: ' + item.seat : ''}</small>
            </div>
        `;
    });
    
    document.getElementById('orderDetails').innerHTML = html;
    document.getElementById('orderModal').classList.add('active');
}

function closeModal() {
    document.getElementById('orderModal').classList.remove('active');
}

document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
