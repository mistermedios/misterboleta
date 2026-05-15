<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$zoneId = isset($_GET['zone_id']) ? (int) $_GET['zone_id'] : 0;

if ($zoneId <= 0) {
    echo json_encode(['error' => 'Zona invalida']);
    exit;
}

$seats = getZoneAvailableSeats($pdo, $zoneId);

echo json_encode([
    'seats' => array_map(function ($seat) {
        return [
            'id' => (int) $seat['id'],
            'ticket_code' => $seat['ticket_code'],
            'seat_row' => $seat['seat_row'],
            'seat_number' => $seat['seat_number']
        ];
    }, $seats)
]);
