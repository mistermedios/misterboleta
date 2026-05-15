<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = getEventById($pdo, $eventId);
$error = '';

if (!$event || ($event['status'] ?? 'draft') !== 'published') {
    redirect(url('public/index.php'));
}

$zones = getEventZones($pdo, $eventId);
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verifyCsrfToken();
    $zoneId = (int)$_POST['zone_id'];
    $quantity = (int)$_POST['quantity'];
    $selectedSeatIds = isset($_POST['selected_seat_ids']) ? array_map('intval', $_POST['selected_seat_ids']) : [];

    $selectedZone = null;
    foreach ($zones as $zone) {
        if ((int) $zone['id'] === $zoneId) {
            $selectedZone = $zone;
            break;
        }
    }

    if (!$selectedZone) {
        $error = 'Selecciona una zona valida.';
    } elseif ($quantity < 1 || $quantity > min(10, (int) $selectedZone['available'])) {
        $error = 'La cantidad seleccionada no está disponible.';
    } else {
        $result = addToCart($eventId, $zoneId, $quantity, $selectedSeatIds);
        if ($result['success']) {
            redirect(url('public/cart.php'));
        }
        $error = $result['message'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1><?= $event['title'] ?></h1>
        <p style="color: var(--text-secondary);"><?= e($categories[$event['category']] ?? 'Evento') ?></p>
    </div>
</section>

<section class="event-detail">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;"><?= e($error) ?></div>
        <?php endif; ?>
        <div class="event-detail-grid">
            <div class="event-info">
                <div class="event-image-large">
                    <img src="<?= e($event['image'] ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800') ?>" alt="<?= e($event['title']) ?>">
                </div>
                
                <div class="event-meta">
                    <div class="event-meta-item">
                        <i class="fa-regular fa-calendar"></i>
                        <span><?= e(formatDateTime($event['event_date'])) ?></span>
                    </div>
                    <div class="event-meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= e($event['venue']) ?>, <?= e($event['city']) ?></span>
                    </div>
                    <?php if ($event['organizer_name']): ?>
                    <div class="event-meta-item">
                        <i class="fa-solid fa-user"></i>
                        <span><?= e($event['organizer_name']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="event-description">
                    <h3 style="margin-bottom: 15px;">Acerca del evento</h3>
                    <p><?= nl2br(e($event['description'] ?: 'No hay descripcion disponible para este evento.')) ?></p>
                </div>
            </div>
            
            <div class="zone-selection">
                <h3>Selecciona tu zona</h3>
                
                <?php if (empty($zones)): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i> No hay zonas disponibles para este evento.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="zones-list">
                            <?php foreach ($zones as $zone): ?>
                                <label class="zone-item">
                                    <input type="radio" name="zone_id" value="<?= $zone['id'] ?>" 
                                           data-price="<?= $zone['price'] ?>" 
                                           data-available="<?= $zone['available'] ?>"
                                           required>
                                    <div class="zone-info">
                                        <h4><?= e($zone['zone_name']) ?></h4>
                                        <p><?= e($zone['description'] ?? '') ?></p>
                                    </div>
                                    <div class="zone-right">
                                        <div class="zone-price">$<?= number_format($zone['price'], 0, ',', '.') ?></div>
                                        <div class="zone-availability <?= $zone['available'] < 20 ? 'low' : '' ?>">
                                            <?= $zone['available'] > 0 ? $zone['available'] . ' disponibles' : 'Agotado' ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="ticket-quantity">
                            <h4>Cantidad de boletas</h4>
                            <div class="quantity-selector">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(-1)">-</button>
                                <span class="quantity-value" id="quantity">1</span>
                                <button type="button" class="quantity-btn" onclick="updateQuantity(1)">+</button>
                            </div>
                            <input type="hidden" name="quantity" id="quantity-input" value="1">
                        </div>
                        
                        <div class="seat-selection" id="seat-selection" style="display: none; margin-top: 20px;">
                            <h4>Selecciona tus asientos</h4>
                            <div class="seat-grid" id="seat-grid"></div>
                            <div class="seat-legend" style="margin-top: 10px; font-size: 0.9rem; color: var(--text-secondary);">
                                <span style="display: inline-block; margin-right: 12px;"><span style="display:inline-block;width:12px;height:12px;background:#2a9d8f;margin-right:6px;"></span>Disponible</span>
                                <span style="display: inline-block; margin-right: 12px;"><span style="display:inline-block;width:12px;height:12px;background:#e76f51;margin-right:6px;"></span>Seleccionado</span>
                                <span style="display: inline-block; margin-right: 12px;"><span style="display:inline-block;width:12px;height:12px;background:#6c757d;margin-right:6px;"></span>No disponible</span>
                            </div>
                            <div id="seat-inputs"></div>
                        </div>
                        
                        <div class="total-price">
                            <span>Total</span>
                            <span class="price" id="total-price">$0</span>
                        </div>
                        
                        <button type="submit" name="add_to_cart" class="btn btn-primary">
                            <i class="fa-solid fa-cart-plus"></i> Agregar al Carrito
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
let selectedZonePrice = 0;
let selectedSeatIds = [];
let quantity = 1;

const seatGrid = document.getElementById('seat-grid');
const seatSelectionContainer = document.getElementById('seat-selection');
const seatInputsContainer = document.getElementById('seat-inputs');

async function renderSeatMap(zoneId) {
    selectedSeatIds = [];
    seatInputsContainer.innerHTML = '';
    seatGrid.innerHTML = '';

    try {
        const response = await fetch('<?= e(url('public/api/zone-seats.php')) ?>?zone_id=' + zoneId);
        const data = await response.json();

        if (data.error) {
            seatSelectionContainer.style.display = 'none';
            return;
        }

        const seats = data.seats || [];
        if (!seats.length) {
            seatSelectionContainer.style.display = 'none';
            return;
        }

        seatSelectionContainer.style.display = 'block';

        if (seats.length > 120) {
            seatGrid.innerHTML = '<p style="color: var(--text-secondary);">Este sector tiene muchos asientos. Selecciona la cantidad y los asientos se asignarán automáticamente en el checkout.</p>';
            return;
        }

        seats.forEach(seat => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'seat-button available';
            button.textContent = seat.seat_row + seat.seat_number;
            button.dataset.seatId = seat.id;
            button.addEventListener('click', function() {
                toggleSeatSelection(seat.id, button);
            });
            seatGrid.appendChild(button);
        });
    } catch (error) {
        seatSelectionContainer.style.display = 'none';
    }

function toggleSeatSelection(seatId, button) {
    const index = selectedSeatIds.indexOf(seatId);
    if (index >= 0) {
        selectedSeatIds.splice(index, 1);
        button.classList.remove('selected');
    } else {
        if (selectedSeatIds.length >= 10) {
            return;
        }
        selectedSeatIds.push(seatId);
        button.classList.add('selected');
    }
    quantity = selectedSeatIds.length || quantity;
    document.getElementById('quantity').textContent = quantity;
    document.getElementById('quantity-input').value = quantity;
    updateSelectedSeatInputs();
    updateTotal();
}

function updateSelectedSeatInputs() {
    seatInputsContainer.innerHTML = '';
    selectedSeatIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_seat_ids[]';
        input.value = id;
        seatInputsContainer.appendChild(input);
    });
}

document.querySelectorAll('.zone-item input').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.zone-item').forEach(item => item.classList.remove('selected'));
        this.closest('.zone-item').classList.add('selected');
        selectedZonePrice = Number(this.dataset.price);
        quantity = 1;
        document.getElementById('quantity').textContent = '1';
        document.getElementById('quantity-input').value = '1';
        selectedSeatIds = [];
        updateSelectedSeatInputs();
        renderSeatMap(this.value);
        updateTotal();
    });
});

function updateQuantity(delta) {
    selectedSeatIds = [];
    updateSelectedSeatInputs();
    quantity = Math.max(1, Math.min(10, quantity + delta));
    document.getElementById('quantity').textContent = quantity;
    document.getElementById('quantity-input').value = quantity;
    updateTotal();
}

function updateTotal() {
    if (selectedZonePrice) {
        const total = selectedZonePrice * quantity;
        document.getElementById('total-price').textContent = '$' + total.toLocaleString('es-CO');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
