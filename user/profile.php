<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        $success = 'Perfil actualizado correctamente.';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword && strlen($newPassword) >= 6) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            $success = 'Contraseña cambiada correctamente.';
        } else {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres y coincidir.';
        }
    } else {
        $error = 'La contraseña actual es incorrecta.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Mi Perfil</h1>
    </div>
</section>

<section class="user-dashboard">
    <div class="container">
        <div class="dashboard-grid">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Mi Perfil</a></li>
                    <li><a href="my-tickets.php"><i class="fa-solid fa-ticket"></i> Mis Boletas</a></li>
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
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 20px;">Información Personal</h3>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nombre Completo</label>
                            <input type="text" id="name" name="name" value="<?= $user['name'] ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" value="<?= $user['email'] ?>" disabled style="background: var(--bg-dark);">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" value="<?= $user['phone'] ?? '' ?>" placeholder="300 123 4567">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
                
                <hr style="border-color: var(--border); margin: 30px 0;">
                
                <h3 style="margin-bottom: 20px;">Cambiar Contraseña</h3>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Contraseña Actual</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
