<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT o.*, e.title as event_title, e.event_date, e.venue, e.city 
                      FROM orders o 
                      JOIN events e ON o.event_id = e.id 
                      WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || !canAccessOrder($order)) {
    echo json_encode(['error' => 'Orden no encontrada']);
    exit;
}

$items = getOrderItems($pdo, $orderId);

echo json_encode([
    'order_number' => $order['order_number'],
    'event_title' => $order['event_title'],
    'event_date' => formatDateTime($order['event_date']),
    'venue' => $order['venue'],
    'city' => $order['city'],
    'total' => (float)$order['total'],
    'customer_name' => $order['customer_name'],
    'customer_email' => $order['customer_email'],
    'payment_status' => $order['payment_status'],
    'status' => $order['status'],
    'items' => array_map(function($item) {
        return [
            'zone_name' => $item['zone_name'],
            'ticket_code' => $item['ticket_code'],
            'seat' => trim($item['seat_row'] . $item['seat_number']),
            'price' => (float)$item['price']
        ];
    }, $items)
]);
