<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(url('public/index.php'));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Evento eliminado correctamente.';
    }

    if ($action === 'toggle_status' && $id > 0) {
        $status = ($_POST['next_status'] ?? '') === 'published' ? 'published' : 'draft';
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = $status === 'published' ? 'Evento publicado.' : 'Evento enviado a borrador.';
    }
}

$events = getAllEvents($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <div class="flex-between">
            <h1>Gestión de Eventos</h1>
            <a href="add-event.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo Evento
            </a>
        </div>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <div class="dashboard-content">
            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3>No hay eventos</h3>
                    <p>Crea tu primer evento para comenzar a vender boletas.</p>
                    <a href="add-event.php" class="btn btn-primary mt-2">Crear Evento</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <strong><?= $event['title'] ?></strong>
                                </td>
                                <td><span class="event-badge"><?= ucfirst($event['category']) ?></span></td>
                                <td><?= formatDateTime($event['event_date']) ?></td>
                                <td><?= $event['venue'] ?>, <?= $event['city'] ?></td>
                                <td>
                                    <span class="status-badge <?= $event['status'] === 'published' ? 'paid' : 'pending' ?>">
                                        <?= $event['status'] === 'published' ? 'Publicado' : 'Borrador' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <a href="edit-event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <input type="hidden" name="next_status" value="<?= $event['status'] === 'published' ? 'draft' : 'published' ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">
                                                <i class="fa-solid fa-<?= $event['status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                            </button>
                                        </form>
                                        <a href="zones.php?event_id=<?= $event['id'] ?>" class="btn btn-sm btn-outline">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('¿Eliminar evento?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
