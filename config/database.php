[System.Environment]::SetEnvironmentVariable('APP_BASE_URL', 'http://localhost/misterboleta', 'User')
[System.Environment]::SetEnvironmentVariable('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN', 'User')
[System.Environment]::SetEnvironmentVariable('MP_PUBLIC_KEY', 'TU_PUBLIC_KEY', 'User')

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'misterboleta');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('APP_BASE_URL', rtrim(getenv('APP_BASE_URL') ?: '', '/'));
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '');
define('MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY') ?: '');

// Status constants
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_CONFIRMED', 'confirmed');
define('ORDER_STATUS_CANCELLED', 'cancelled');
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('TICKET_STATUS_AVAILABLE', 'available');
define('TICKET_STATUS_RESERVED', 'reserved');
define('TICKET_STATUS_SOLD', 'sold');
define('TICKET_STATUS_USED', 'used');
define('TICKET_STATUS_CANCELLED', 'cancelled');
define('EVENT_STATUS_DRAFT', 'draft');
define('EVENT_STATUS_PUBLISHED', 'published');
define('EVENT_STATUS_CANCELLED', 'cancelled');
define('EVENT_STATUS_COMPLETED', 'completed');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax'
    ]);
    session_start();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de conexión a la base de datos.');
}

function appBasePath() {
    if (APP_BASE_URL !== '') {
        return APP_BASE_URL;
    }

    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $markers = ['/public/', '/user/', '/admin/'];
    $basePath = '';

    foreach ($markers as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            $basePath = substr($scriptName, 0, $position);
            break;
        }
    }

    return rtrim($basePath, '/');
}

function assetUrl($path) {
    return url($path);
}

function url($path = '') {
    $path = ltrim($path, '/');
    $basePath = appBasePath();

    if ($path === '') {
        return $basePath !== '' ? $basePath . '/' : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . $path;
}

function appBaseUrl() {
    if (APP_BASE_URL !== '') {
        return APP_BASE_URL;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . appBasePath();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function redirectBack($fallback = '') {
    $target = $_SERVER['HTTP_REFERER'] ?? $fallback ?: url('public/index.php');
    redirect($target);
}

function generateTicketCode() {
    return 'MB-' . strtoupper(bin2hex(random_bytes(5)));
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!$sessionToken || !$token || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        die('Solicitud no valida.');
    }
}
