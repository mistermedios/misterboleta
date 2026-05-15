<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(url('public/index.php'));
}

$event = null;
$error = '';
$success = false;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? 'otro');
    $venue = sanitize($_POST['venue'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $image = $event['image'] ?? '';
    $organizerName = sanitize($_POST['organizer_name'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] < 5000000) { // 5MB
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $image = 'uploads/' . $fileName;
            }
        }
    }
    
    if (empty($title) || empty($venue) || empty($city) || empty($eventDate)) {
        $error = 'Por favor complete los campos requeridos.';
    } else {
        if ($event) {
            $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, category=?, venue=?, address=?, city=?, event_date=?, image=?, organizer_name=?, status=?, featured=? WHERE id=?");
            $stmt->execute([$title, $description, $category, $venue, $address, $city, $eventDate, $image, $organizerName, $status, $featured, $event['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, category, venue, address, city, event_date, image, organizer_name, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $venue, $address, $city, $eventDate, $image, $organizerName, $status, $featured]);
            $eventId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eventId, 'General', 'Zona general', 50000, 100, 100, '#e94560']);
        }
        $success = true;
        header("Location: events.php");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1><?= $event ? 'Editar' : 'Crear' ?> Evento</h1>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="title">Título del Evento *</label>
                    <input type="text" id="title" name="title" required value="<?= $event['title'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="4"><?= $event['description'] ?? '' ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Categoría</label>
                        <select id="category" name="category">
                            <option value="concierto" <?= ($event['category'] ?? '') === 'concierto' ? 'selected' : '' ?>>Concierto</option>
                            <option value="deporte" <?= ($event['category'] ?? '') === 'deporte' ? 'selected' : '' ?>>Deporte</option>
                            <option value="teatro" <?= ($event['category'] ?? '') === 'teatro' ? 'selected' : '' ?>>Teatro</option>
                            <option value="festival" <?= ($event['category'] ?? '') === 'festival' ? 'selected' : '' ?>>Festival</option>
                            <option value="otro" <?= ($event['category'] ?? 'otro') === 'otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_date">Fecha y Hora *</label>
                        <input type="datetime-local" id="event_date" name="event_date" required value="<?= $event ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : '' ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="venue">Lugar/Venue *</label>
                        <input type="text" id="venue" name="venue" required value="<?= $event['venue'] ?? '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">Ciudad *</label>
                        <input type="text" id="city" name="city" required value="<?= $event['city'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <input type="text" id="address" name="address" value="<?= $event['address'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Imagen del Evento</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if ($event && $event['image']): ?>
                        <p>Imagen actual: <img src="<?= e(url($event['image'])) ?>" alt="Current" style="max-width: 100px;"></p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="organizer_name">Nombre del Organizador</label>
                    <input type="text" id="organizer_name" name="organizer_name" value="<?= $event['organizer_name'] ?? '' ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="draft" <?= ($event['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                            <option value="published" <?= ($event['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 30px;">
                        <input type="checkbox" id="featured" name="featured" <?= ($event['featured'] ?? 0) ? 'checked' : '' ?>>
                        <label for="featured">Evento Destacado</label>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><?= $event ? 'Actualizar' : 'Crear' ?> Evento</button>
                    <a href="events.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
