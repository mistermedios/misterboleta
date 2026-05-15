<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect(url('public/index.php'));
}

$error = '';
$success = false;
$paymentMethods = getPaymentMethods($pdo);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');

    if (empty($name) || empty($email) || empty($paymentMethod)) {
        $error = 'Por favor complete todos los campos requeridos.';
    } else {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if (!$existingUser) {
                $plainPassword = bin2hex(random_bytes(4));
                $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $phone]);
                $userId = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'user';

                $subject = "Tu cuenta en MisterBoleta ha sido creada";
                $body = "
                <h2>Bienvenido a MisterBoleta</h2>
                <p>Tu cuenta ha sido creada automáticamente para tu compra.</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Contraseña:</strong> $plainPassword</p>
                <p>Puedes cambiar tu contraseña en tu perfil después de iniciar sesión.</p>
                <p>Gracias por tu compra.</p>
                ";
                sendEmail($email, $subject, $body);
            } else {
                $error = 'Ese correo ya existe. Inicia sesión para continuar con esa cuenta.';
            }
        }

        if (!$error) {
            $cartPayload = [
                'zones' => [],
                'total_tickets' => 0
            ];
            $eventId = null;

            foreach ($_SESSION['cart'] as $item) {
                $stmt = $pdo->prepare("SELECT ez.*, e.id as event_id
                                      FROM event_zones ez
                                      JOIN events e ON ez.event_id = e.id
                                      WHERE ez.id = ?");
                $stmt->execute([$item['zone_id']]);
                $zone = $stmt->fetch();

                if (!$zone) {
                    $error = 'Una de las zonas del carrito ya no existe.';
                    break;
                }

                if (!$eventId) {
                    $eventId = (int) $zone['event_id'];
                }

                if ($eventId !== (int) $zone['event_id']) {
                    $error = 'Solo puedes finalizar una orden por evento.';
                    break;
                }

                if ((int) $item['quantity'] > (int) $zone['available']) {
                    $error = 'La disponibilidad de una zona cambió. Revisa tu carrito.';
                    break;
                }

                $cartPayload['zones'][] = [
                    'zone_id' => (int) $zone['id'],
                    'quantity' => (int) $item['quantity'],
                    'seat_ids' => $item['seat_ids'] ?? []
                ];
                $cartPayload['total_tickets'] += (int) $item['quantity'];
            }

            if (!$error && empty($cartPayload['zones'])) {
                $error = 'No hay boletas disponibles para esta selección.';
            }

            if (!$error) {
                $orderId = createOrder($pdo, $userId, $eventId, $cartPayload, $total, $paymentMethod, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]);

                if ($orderId) {
                    $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $orderInfo = $stmt->fetch();
                    $orderNumber = $orderInfo['order_number'] ?? '';

                    $subject = "Confirmación de Orden - MisterBoleta";
                    $body = "
                    <h2>Orden Registrada</h2>
                    <p><strong>Número de Orden:</strong> " . e($orderNumber) . "</p>
                    <p><strong>Total:</strong> $" . number_format($total, 0, ',', '.') . "</p>
                    <p>Tu orden fue registrada correctamente y está pendiente de pago.</p>
                    ";
                    sendEmail($email, $subject, $body);

                    clearCart();
                    redirect(url("public/payment.php?order_id=$orderId"));
                } else {
                    $error = 'No fue posible crear la orden. Intente nuevamente.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Finalizar Compra</h1>
    </div>
</section>

<section class="checkout-section">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div class="checkout-form">
                <h3 style="margin-bottom: 20px;">Datos del Comprador</h3>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="name">Nombre Completo *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?= e($_SESSION['user_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?= e($_SESSION['user_email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" placeholder="300 123 4567" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="payment-methods">
                        <h4>Método de Pago</h4>
                        <div class="payment-options">
                            <?php foreach ($paymentMethods as $method): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" 
                                           value="<?= e($method['method_name']) ?>" required>
                                    <span class="payment-icon">
                                        <?php switch($method['method_name']):
                                            case 'mercadopago': echo '<i class="fa-solid fa-wallet"></i>'; break;
                                            case 'nequi': echo '<i class="fa-solid fa-mobile-screen"></i>'; break;
                                            case 'daviplata': echo '<i class="fa-solid fa-money-bill"></i>'; break;
                                            case 'breeze': echo '<i class="fa-solid fa-building-columns"></i>'; break;
                                            default: echo '<i class="fa-solid fa-money-bills"></i>'; endswitch; ?>
                                    </span>
                                    <div class="payment-info">
                                        <h5><?= e($method['display_name']) ?></h5>
                                        <p><?= e($method['instructions']) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                        Confirmar Compra
                    </button>
                </form>
            </div>
            
            <div class="order-summary">
                <h3>Resumen de la Orden</h3>
                
                <?php foreach ($cartItems as $item): ?>
                    <div style="padding: 15px 0; border-bottom: 1px solid var(--border);">
                        <h4><?= e($item['zone']['event_title']) ?></h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <?= e($item['zone']['zone_name']) ?> × <?= $item['quantity'] ?>
                        </p>
                        <?php if (!empty($item['seat_labels'])): ?>
                            <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 4px 0 0;">
                                Asientos: <?= e(implode(', ', $item['seat_labels'])) ?>
                            </p>
                        <?php endif; ?>
                        <p style="text-align: right; font-weight: 600; margin-top: 8px;">
                            $<?= number_format($item['subtotal'], 0, ',', '.') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-row total">
                    <span>Total a Pagar</span>
                    <span class="price">$<?= number_format($total, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
