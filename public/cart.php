<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect(url('public/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    verifyCsrfToken();
    removeFromCart($_POST['remove']);
    redirect(url('public/cart.php'));
}

$cartItems = [];
$total = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    $stmt = $pdo->prepare("SELECT ez.*, e.title as event_title, e.event_date, e.image 
                          FROM event_zones ez 
                          JOIN events e ON ez.event_id = e.id 
                          WHERE ez.id = ?");
    $stmt->execute([$item['zone_id']]);
    $zone = $stmt->fetch();
    
    if ($zone) {
        $cartItems[$key] = [
            'zone' => $zone,
            'quantity' => $item['quantity'],
            'subtotal' => $zone['price'] * $item['quantity'],
            'seat_labels' => $item['seat_labels'] ?? []
        ];
        $total += $zone['price'] * $item['quantity'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Carrito de Compras</h1>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <h3>Tu carrito está vacío</h3>
                <p>Explora nuestros eventos y encuentra tu próxima experiencia.</p>
                <a href="<?= e(url('public/index.php')) ?>" class="btn btn-primary mt-2">Ver Eventos</a>
            </div>
        <?php else: ?>
            <div class="flex-between mb-3">
                <h2 class="section-title"><span>Resumen del Carrito</span></h2>
                <form method="POST" action="<?= e(url('public/clear-cart.php')) ?>" style="display: inline;">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-outline" onclick="return confirm('¿Vaciar carrito?')">
                        <i class="fa-solid fa-trash"></i> Vaciar Carrito
                    </button>
                </form>
            </div>
            
            <div class="cart-items">
                <?php foreach ($cartItems as $key => $item): ?>
                    <div class="cart-item">
                        <img src="<?= e($item['zone']['image'] ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=200') ?>" 
                             alt="<?= e($item['zone']['event_title']) ?>" class="cart-item-image">
                        <div class="cart-item-info">
                            <h4><?= e($item['zone']['event_title']) ?></h4>
                            <p><?= e($item['zone']['zone_name']) ?> • <?= e(formatDateTime($item['zone']['event_date'])) ?></p>
                            <p><?= $item['quantity'] ?> boleta(s) × $<?= number_format($item['zone']['price'], 0, ',', '.') ?></p>
                            <?php if (!empty($item['seat_labels'])): ?>
                                <p style="margin-top: 6px; font-size: 0.95rem; color: var(--text-secondary);">
                                    Asientos: <?= e(implode(', ', $item['seat_labels'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-price">$<?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                        <div class="cart-item-actions">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="remove" value="<?= e($key) ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex-between">
                <a href="<?= e(url('public/index.php')) ?>" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Seguir Comprando
                </a>
                
                <div class="cart-summary" style="max-width: 400px;">
                    <h3>Total a Pagar</h3>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="price">$<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <a href="<?= e(url('public/checkout.php')) ?>" class="btn btn-primary mt-2" style="width: 100%;">
                        Proceder al Pago <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
