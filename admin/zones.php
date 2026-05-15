<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(url('public/index.php'));
}

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (!$eventId) {
    redirect(url('admin/events.php'));
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    redirect(url('admin/events.php'));
}

$stmt = $pdo->prepare("SELECT * FROM event_zones WHERE event_id = ?");
$stmt->execute([$eventId]);
$zones = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    if (isset($_POST['delete_zone'])) {
        $zoneId = (int) $_POST['zone_id'];
        $stmt = $pdo->prepare("DELETE FROM event_zones WHERE id = ? AND event_id = ?");
        $stmt->execute([$zoneId, $eventId]);
        $success = 'Zona eliminada correctamente.';

        $stmt = $pdo->prepare("SELECT * FROM event_zones WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $zones = $stmt->fetchAll();
    }

    if (isset($_POST['add_zone'])) {
        $zoneName = sanitize($_POST['zone_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)$_POST['price'];
        $capacity = (int)$_POST['capacity'];
        $color = sanitize($_POST['color'] ?? '#e94560');
        
        if (empty($zoneName) || $price <= 0 || $capacity <= 0) {
            $error = 'Por favor complete los campos correctamente.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eventId, $zoneName, $description, $price, $capacity, $capacity, $color]);
            $success = 'Zona agregada correctamente.';
            
            $stmt = $pdo->prepare("SELECT * FROM event_zones WHERE event_id = ?");
            $stmt->execute([$eventId]);
            $zones = $stmt->fetchAll();
        }
    }
    
    if (isset($_POST['update_zone'])) {
        $zoneId = (int)$_POST['zone_id'];
        $zoneName = sanitize($_POST['zone_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)$_POST['price'];
        $color = sanitize($_POST['color'] ?? '#e94560');
        
        $stmt = $pdo->prepare("UPDATE event_zones SET zone_name=?, description=?, price=?, color=? WHERE id=? AND event_id=?");
        $stmt->execute([$zoneName, $description, $price, $color, $zoneId, $eventId]);
        $success = 'Zona actualizada correctamente.';
        
        $stmt = $pdo->prepare("SELECT * FROM event_zones WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $zones = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <div class="flex-between">
            <div>
                <h1>Gestión de Zonas</h1>
                <p style="color: var(--text-secondary);"><?= e($event['title']) ?></p>
            </div>
            <a href="<?= e(url('admin/events.php')) ?>" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div class="dashboard-content">
                <h3 style="margin-bottom: 20px;">Zonas Existentes</h3>
                
                <?php if (empty($zones)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🎫</div>
                        <h3>No hay zonas</h3>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Zona</th>
                                <th>Precio</th>
                                <th>Disponibles/Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
        <tbody>
            <?php foreach ($zones as $zone): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 20px; height: 20px; border-radius: 4px; background: <?= e($zone['color']) ?>;"></div>
                            <strong><?= e($zone['zone_name']) ?></strong>
                        </div>
                    </td>
                    <td>$<?= number_format($zone['price'], 0, ',', '.') ?></td>
                    <td>
                        <?= $zone['available'] ?> / <?= $zone['capacity'] ?>
                        <?php if ($zone['available'] < $zone['capacity'] * 0.2): ?>
                            <span style="color: var(--warning);">(¡Últimas!)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline" onclick='editZone(<?= json_encode((int) $zone['id']) ?>, <?= json_encode($zone["zone_name"]) ?>, <?= json_encode($zone["description"] ?? "") ?>, <?= json_encode((float) $zone["price"]) ?>, <?= json_encode($zone["color"]) ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <?php if ($zone['available'] == $zone['capacity']): ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_zone" value="1">
                                    <input type="hidden" name="zone_id" value="<?= $zone['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('¿Eliminar zona?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
                <?php endif; ?>
            </div>
            
            <div class="sidebar" style="position: sticky; top: 100px; height: fit-content;">
                <h3 style="margin-bottom: 20px;">Agregar Nueva Zona</h3>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="add_zone" value="1">
                    
                    <div class="form-group">
                        <label for="zone_name">Nombre de Zona</label>
                        <input type="text" id="zone_name" name="zone_name" required placeholder="VIP, General, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <input type="text" id="description" name="description" placeholder="Descripción de la zona">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Precio ($)</label>
                        <input type="number" id="price" name="price" required min="1000" step="1000" placeholder="50000">
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacidad</label>
                        <input type="number" id="capacity" name="capacity" required min="1" placeholder="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="color" id="color" name="color" value="#e94560">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Agregar Zona
                    </button>
                </form>
                
                <div id="editZoneForm" style="display: none; margin-top: 30px;">
                    <h3 style="margin-bottom: 20px;">Editar Zona</h3>
                    
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="update_zone" value="1">
                        <input type="hidden" id="edit_zone_id" name="zone_id">
                        
                        <div class="form-group">
                            <label>Nombre de Zona</label>
                            <input type="text" id="edit_zone_name" name="zone_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <input type="text" id="edit_description" name="description">
                        </div>
                        
                        <div class="form-group">
                            <label>Precio ($)</label>
                            <input type="number" id="edit_price" name="price" required min="1000" step="1000">
                        </div>
                        
                        <div class="form-group">
                            <label>Color</label>
                            <input type="color" id="edit_color" name="color">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Actualizar Zona
                        </button>
                        <button type="button" class="btn btn-outline" style="width: 100%; margin-top: 10px;" onclick="cancelEdit()">
                            Cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function editZone(id, name, description, price, color) {
    document.getElementById('edit_zone_id').value = id;
    document.getElementById('edit_zone_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_color').value = color;
    document.getElementById('editZoneForm').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('editZoneForm').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
