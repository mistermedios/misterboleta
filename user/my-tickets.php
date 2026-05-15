<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$stmt = $pdo->prepare("SELECT t.*, e.title, e.event_date, e.venue, e.city, ez.zone_name 
                      FROM tickets t 
                      JOIN events e ON t.event_id = e.id 
                      JOIN event_zones ez ON t.zone_id = ez.id 
                      WHERE t.user_id = ? 
                      ORDER BY e.event_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Mis Boletas</h1>
    </div>
</section>

<section class="user-dashboard">
    <div class="container">
        <div class="dashboard-grid">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="profile.php"><i class="fa-solid fa-user"></i> Mi Perfil</a></li>
                    <li><a href="my-tickets.php" class="active"><i class="fa-solid fa-ticket"></i> Mis Boletas</a></li>
                    <li><a href="orders.php"><i class="fa-solid fa-receipt"></i> Mis Órdenes</a></li>
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
                <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🎫</div>
                        <h3>No tienes boletas</h3>
                        <p>Explora nuestros eventos y compra tus primeras boletas.</p>
                        <a href="<?= e(url('public/index.php')) ?>" class="btn btn-primary mt-2">Ver Eventos</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Zona</th>
                                <th>Fecha</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($ticket['title']) ?></strong><br>
                                        <small style="color: var(--text-secondary);"><?= e($ticket['venue']) ?>, <?= e($ticket['city']) ?></small>
                                    </td>
                                    <td><?= e($ticket['zone_name']) ?></td>
                                    <td><?= e(formatDateTime($ticket['event_date'])) ?></td>
                                    <td><code style="background: var(--bg-dark); padding: 3px 8px; border-radius: 4px;"><?= e($ticket['ticket_code']) ?></code><br>
                                        <?php if ($ticket['seat_row'] && $ticket['seat_number']): ?>
                                            <small style="color: var(--text-secondary);">Asiento: <?= e($ticket['seat_row'] . $ticket['seat_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= e($ticket['status']) ?>">
                                            <?php switch($ticket['status']):
                                                case 'sold': echo 'Confirmada'; break;
                                                case 'reserved': echo 'Pendiente'; break;
                                                case 'used': echo 'Usada'; break;
                                                case 'available': echo 'Disponible'; break;
                                                default: echo $ticket['status']; endswitch; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ticket['status'] === 'sold'): ?>
                                            <button type="button" class="btn btn-sm btn-outline" onclick="showTicket('<?= e($ticket['ticket_code']) ?>')">
                                                <i class="fa-solid fa-qrcode"></i> Ver
                                            </button>
                                        <?php endif; ?>
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

<div class="modal" id="ticketModal">
    <div class="modal-content">
        <h3 style="text-align: center; margin-bottom: 20px;">Tu Boleta</h3>
        <div id="ticketDisplay"></div>
        <button class="btn btn-outline" style="width: 100%; margin-top: 20px;" onclick="closeModal()">Cerrar</button>
    </div>
</div>

<script>
function showTicket(code) {
    document.getElementById('ticketDisplay').innerHTML = `
        <div style="text-align: center;">
            <div style="width: 150px; height: 150px; margin: 0 auto; background: var(--bg-dark); display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 3rem;">🎫</div>
            <p style="margin-top: 20px; font-family: monospace; font-size: 1.2rem; letter-spacing: 2px;">${code}</p>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 10px;">Presenta este código en la entrada del evento</p>
        </div>
    `;
    document.getElementById('ticketModal').classList.add('active');
}

function closeModal() {
    document.getElementById('ticketModal').classList.remove('active');
}

document.getElementById('ticketModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
