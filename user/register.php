<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('profile.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos requeridos.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        if (registerUser($pdo, $name, $email, $password, $phone)) {
            // Send welcome email
            $subject = "Bienvenido a MisterBoleta";
            $body = "
            <h2>Bienvenido, $name</h2>
            <p>Tu cuenta en MisterBoleta ha sido creada exitosamente.</p>
            <p>Ahora puedes comprar boletas para tus eventos favoritos.</p>
            <p>Si tienes alguna pregunta, contáctanos.</p>
            ";
            sendEmail($email, $subject, $body);
            
            loginUser($pdo, $email, $password);
            redirect('profile.php');
        } else {
            $error = 'El correo electrónico ya está registrado.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-title">
    <div class="container">
        <h1>Crear Cuenta</h1>
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
                    <label for="name">Nombre Completo</label>
                    <input type="text" id="name" name="name" required placeholder="Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono (opcional)</label>
                    <input type="tel" id="phone" name="phone" placeholder="300 123 4567">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Crear Cuenta
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
                ¿Ya tienes cuenta? <a href="login.php" style="color: var(--accent);">Inicia sesión aquí</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
