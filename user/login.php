<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('profile.php');
}

$attempts = $_SESSION['login_attempts'] ?? 0;
$lastAttempt = $_SESSION['last_attempt'] ?? 0;

if ($attempts >= 5 && time() - $lastAttempt < 900) { // 5 attempts, 15 min lock
    $error = 'Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor complete todos los campos.';
        } elseif (loginUser($pdo, $email, $password)) {
            unset($_SESSION['login_attempts'], $_SESSION['last_attempt']);
            redirect('profile.php');
        } else {
            $attempts++;
            $_SESSION['login_attempts'] = $attempts;
            $_SESSION['last_attempt'] = time();
            $error = 'Credenciales incorrectas. Intente nuevamente.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Iniciar Sesión</h1>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <div class="form-container" style="max-width: 450px;">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Iniciar Sesión
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
                ¿No tienes cuenta? <a href="register.php" style="color: var(--accent);">Regístrate aquí</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
