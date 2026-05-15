<?php 
$pageTitle = "MisterBoleta - Venta de Boletas";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$categories = getCategories();
$events = getEvents($pdo, 12, 0);
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

if ($search || $category) {
    $events = getEvents($pdo, 20, 0, $category, $search);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="<?= e(url('public/index.php')) ?>" class="logo">
                    <i class="fa-solid fa-ticket"></i>
                    Mister<span>Boleta</span>
                </a>
                
                <nav>
                    <ul class="nav-links">
                        <li><a href="<?= e(url('public/index.php')) ?>">Inicio</a></li>
                        <li><a href="<?= e(url('public/index.php?category=concierto')) ?>">Conciertos</a></li>
                        <li><a href="<?= e(url('public/index.php?category=deporte')) ?>">Deportes</a></li>
                        <li><a href="<?= e(url('public/index.php?category=teatro')) ?>">Teatro</a></li>
                        <li><a href="<?= e(url('public/index.php?category=festival')) ?>">Festivales</a></li>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= e(url('user/profile.php')) ?>" class="btn btn-outline">
                            <i class="fa-solid fa-user"></i> <?= e($_SESSION['user_name']) ?>
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-secondary">Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= e(url('user/login.php')) ?>" class="btn btn-outline">Iniciar Sesion</a>
                        <a href="<?= e(url('user/register.php')) ?>" class="btn btn-primary">Registrarse</a>
                    <?php endif; ?>
                    
                    <a href="<?= e(url('public/cart.php')) ?>" class="cart-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <?php $count = getCartCount(); if ($count > 0): ?>
                            <span class="cart-count"><?= $count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main>
