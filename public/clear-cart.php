<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('public/cart.php'));
}

verifyCsrfToken();
clearCart();
redirect(url('public/cart.php'));
