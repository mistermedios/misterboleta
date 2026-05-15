<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['type']) || !isset($data['data']['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook payload']);
    exit;
}

$type = $data['type'];
$resourceId = $data['data']['id'];

if ($type !== 'payment') {
    http_response_code(200);
    echo json_encode(['message' => 'Ignored non-payment notification']);
    exit;
}

$payment = mercadoPagoRequest('/v1/payments/' . intval($resourceId), [], 'GET');

if (!$payment || !isset($payment['external_reference'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to retrieve payment details']);
    exit;
}

$orderNumber = $payment['external_reference'];
$paymentStatus = $payment['status'] ?? '';

$stmt = $pdo->prepare('SELECT id, payment_status, status FROM orders WHERE order_number = ?');
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

try {
    if ($paymentStatus === 'approved') {
        updateOrderState($pdo, $order['id'], 'confirm');
    } elseif (in_array($paymentStatus, ['cancelled', 'rejected', 'refunded'], true)) {
        updateOrderState($pdo, $order['id'], 'cancel');
    }
} catch (Exception $e) {
    // If order was already processed, return success to avoid repeated deliveries
}

http_response_code(200);
echo json_encode(['success' => true]);
