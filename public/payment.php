<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$stmt = $pdo->prepare("SELECT o.*, e.title as event_title, e.event_date, e.venue, e.city 
                      FROM orders o 
                      JOIN events e ON o.event_id = e.id 
                      WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || !canAccessOrder($order)) {
    redirect(url('public/index.php'));
}

$items = getOrderItems($pdo, $orderId);
$mpInitPoint = null;
$mpError = null;

if ($order['payment_method'] === 'mercadopago') {
    $preference = createMercadoPagoPreference($pdo, $order, $items);
    if ($preference && isset($preference['init_point'])) {
        $mpInitPoint = $preference['init_point'];
        $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ? WHERE id = ?");
        $stmt->execute([$preference['id'] ?? '', $orderId]);
    } else {
        $mpError = 'No se pudo generar la preferencia de MercadoPago. Verifique la configuración de MP.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Confirmación de Compra</h1>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <div style="text-align: center; max-width: 600px; margin: 0 auto;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🎉</div>
            <h2 style="margin-bottom: 10px;">Orden Registrada</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">
                Tu solicitud fue registrada correctamente. La orden queda pendiente hasta validar el pago.
            </p>
            
            <div class="alert alert-success" style="text-align: left; margin-bottom: 30px;">
                <strong><i class="fa-solid fa-check-circle"></i> Número de Orden:</strong> <?= e($order['order_number']) ?>
            </div>
            
            <div style="background: var(--bg-card); border-radius: 20px; padding: 30px; text-align: left; margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px;">Detalles del Evento</h3>
                <p><strong>Evento:</strong> <?= e($order['event_title']) ?></p>
                <p><strong>Fecha:</strong> <?= e(formatDateTime($order['event_date'])) ?></p>
                <p><strong>Lugar:</strong> <?= e($order['venue']) ?>, <?= e($order['city']) ?></p>
                <p><strong>Método de Pago:</strong> <?= e(ucfirst($order['payment_method'])) ?></p>
                <p><strong>Estado:</strong> <?= e(ucfirst($order['status'])) ?></p>
                <p><strong>Total Pagado:</strong> <span style="color: var(--success); font-weight: 700;">$<?= number_format($order['total'], 0, ',', '.') ?></span></p>
            </div>
            
            <div style="background: var(--bg-card); border-radius: 20px; padding: 30px; text-align: left;">
                <h3 style="margin-bottom: 20px;">Boletas Generadas</h3>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    Estas boletas quedan reservadas mientras el pago se valida desde administracion.
                </p>
                <?php foreach ($items as $item): ?>
                    <div style="padding: 15px; background: var(--bg-dark); border-radius: 10px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= e($item['zone_name']) ?></strong>
                                <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                    Código: <?= e($item['ticket_code']) ?>
                                    <?php if (!empty($item['seat_row']) && !empty($item['seat_number'])): ?>
                                        • Asiento: <?= e($item['seat_row'] . $item['seat_number']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="status-badge pending">Pendiente</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($order['payment_method'] === 'mercadopago'): ?>
                <div style="background: var(--bg-card); border-radius: 20px; padding: 25px; margin-top: 20px; text-align: center;">
                    <h3 style="margin-bottom: 15px;">Pagar con MercadoPago</h3>
                    <?php if ($mpError): ?>
                        <div class="alert alert-error"><?= e($mpError) ?></div>
                    <?php elseif ($mpInitPoint): ?>
                        <p style="color: var(--text-secondary); margin-bottom: 20px;">
                            Serás redirigido a MercadoPago para completar tu pago seguro.
                        </p>
                        <a href="<?= e($mpInitPoint) ?>" class="btn btn-primary" style="width: 100%;" target="_blank" rel="noreferrer noopener">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Ir a MercadoPago
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning">No se pudo generar la preferencia de pago.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="flex gap-2" style="justify-content: center; margin-top: 30px;">
                <a href="<?= e(url('user/my-tickets.php')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-ticket"></i> Ver Mis Boletas
                </a>
                <a href="<?= e(url('public/index.php')) ?>" class="btn btn-outline">
                    Volver al Inicio
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
