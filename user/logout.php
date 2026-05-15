<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('public/index.php'));
}

verifyCsrfToken();

$_SESSION = [];
session_destroy();
redirect(url('public/index.php'));
