<?php
require_once __DIR__ . '/../config/database.php';

function getCartCount() {
    return isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($date) {
    return date('d M Y • h:i A', strtotime($date));
}

function getCategories() {
    return ['concierto' => '🎵 Concierto', 'deporte' => '⚽ Deporte', 'teatro' => '🎭 Teatro', 'festival' => '🎪 Festival', 'otro' => '📌 Otro'];
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function canAccessOrder($order) {
    if (!$order) {
        return false;
    }

    if (isAdmin()) {
        return true;
    }

    if (isLoggedIn() && (int) $order['user_id'] === (int) $_SESSION['user_id']) {
        return true;
    }

    return isset($_SESSION['last_order_id']) && (int) $_SESSION['last_order_id'] === (int) $order['id'];
}

function getEvents($pdo, $limit = 10, $offset = 0, $category = null, $search = null) {
    $sql = "SELECT * FROM events WHERE status = 'published'";
    $params = [];
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (title LIKE ? OR venue LIKE ? OR city LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY event_date ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getEventById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEventZones($pdo, $eventId) {
    $stmt = $pdo->prepare("SELECT * FROM event_zones WHERE event_id = ? AND available > 0");
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

function generateSeatLabel($index) {
    $columns = 10;
    $rowIndex = floor($index / $columns);
    $seatNumber = ($index % $columns) + 1;
    $rowLabel = '';

    while ($rowIndex >= 0) {
        $rowLabel = chr(65 + ($rowIndex % 26)) . $rowLabel;
        $rowIndex = intval($rowIndex / 26) - 1;
    }

    return $rowLabel . $seatNumber;
}

function ensureZoneSeatInventory($pdo, $zoneId) {
    $stmt = $pdo->prepare("SELECT event_id, capacity FROM event_zones WHERE id = ?");
    $stmt->execute([$zoneId]);
    $zone = $stmt->fetch();

    if (!$zone) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE zone_id = ?");
    $stmt->execute([$zoneId]);
    $existing = (int) $stmt->fetchColumn();

    $totalSeats = (int) $zone['capacity'];
    if ($existing >= $totalSeats) {
        return [];
    }

    $pdo->beginTransaction();
    try {
        for ($index = $existing; $index < $totalSeats; $index++) {
            $seatLabel = generateSeatLabel($index);
            preg_match('/^([A-Z]+)(\d+)$/', $seatLabel, $matches);
            $seatRow = $matches[1] ?? '';
            $seatNumber = $matches[2] ?? '';

            $stmt = $pdo->prepare("INSERT INTO tickets (ticket_code, event_id, zone_id, status, seat_row, seat_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                generateTicketCode(),
                $zone['event_id'],
                $zoneId,
                'available',
                $seatRow,
                $seatNumber
            ]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

function getZoneAvailableSeats($pdo, $zoneId) {
    ensureZoneSeatInventory($pdo, $zoneId);

    $stmt = $pdo->prepare("SELECT id, ticket_code, seat_row, seat_number FROM tickets WHERE zone_id = ? AND status = 'available' ORDER BY seat_row ASC, CAST(seat_number AS UNSIGNED) ASC");
    $stmt->execute([$zoneId]);
    return $stmt->fetchAll();
}

function getMinPrice($pdo, $eventId) {
    $stmt = $pdo->prepare("SELECT MIN(price) as min_price FROM event_zones WHERE event_id = ? AND available > 0");
    $stmt->execute([$eventId]);
    $result = $stmt->fetch();
    return $result['min_price'] ?? 0;
}

function getUserOrders($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT o.*, e.title as event_title
                          FROM orders o
                          JOIN events e ON o.event_id = e.id
                          WHERE o.user_id = ?
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getOrderItems($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT oi.*, t.ticket_code, t.seat_row, t.seat_number, e.title as event_title, ez.zone_name 
                          FROM order_items oi 
                          JOIN tickets t ON oi.ticket_id = t.id 
                          JOIN events e ON t.event_id = e.id 
                          JOIN event_zones ez ON t.zone_id = ez.id 
                          WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function getPaymentMethods($pdo) {
    $stmt = $pdo->query("SELECT * FROM payment_settings WHERE enabled = 1");
    return $stmt->fetchAll();
}

function mercadoPagoRequest($path, $data = [], $method = 'POST') {
    $accessToken = MP_ACCESS_TOKEN;
    if (!$accessToken) {
        return false;
    }

    $url = 'https://api.mercadopago.com' . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    if ($httpCode >= 400 || !is_array($result)) {
        return false;
    }

    return $result;
}

function createMercadoPagoPreference($pdo, $order, $items) {
    $payer = [
        'name' => $order['customer_name'] ?: 'Cliente MisterBoleta',
        'email' => $order['customer_email'] ?: 'no-reply@example.com'
    ];

    $summary = [];
    foreach ($items as $item) {
        $key = $item['zone_name'] . '_' . $item['price'];

        if (!isset($summary[$key])) {
            $summary[$key] = [
                'title' => $item['zone_name'],
                'quantity' => 0,
                'unit_price' => (float) $item['price']
            ];
        }

        $summary[$key]['quantity']++;
    }

    $preference = [
        'items' => array_values($summary),
        'payer' => $payer,
        'external_reference' => $order['order_number'],
        'back_urls' => [
            'success' => appBaseUrl() . url('public/payment.php?order_id=' . $order['id']),
            'failure' => appBaseUrl() . url('public/payment.php?order_id=' . $order['id']),
            'pending' => appBaseUrl() . url('public/payment.php?order_id=' . $order['id'])
        ],
        'auto_return' => 'approved',
        'notification_url' => appBaseUrl() . url('public/api/mercadopago-webhook.php'),
        'statement_descriptor' => 'MisterBoleta'
    ];

    return mercadoPagoRequest('/checkout/preferences', $preference);
}

function generateOrderNumber() {
    return 'MB-' . date('Ymd') . '-' . strtoupper(uniqid());
}

function addToCart($eventId, $zoneId, $quantity = 1, $selectedSeatIds = []) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $eventId = (int) $eventId;
    $zoneId = (int) $zoneId;
    $selectedSeatIds = array_filter(array_map('intval', (array) $selectedSeatIds));
    $quantity = max(1, min(10, (int) $quantity));

    if (!empty($selectedSeatIds)) {
        $quantity = count($selectedSeatIds);
    }

    foreach ($_SESSION['cart'] as $item) {
        if ((int) $item['event_id'] !== $eventId) {
            return [
                'success' => false,
                'message' => 'Por ahora solo puedes comprar boletas de un evento por orden.'
            ];
        }
    }

    if (!empty($selectedSeatIds)) {
        $ticketPlaceholders = implode(',', array_fill(0, count($selectedSeatIds), '?'));
        $stmt = $GLOBALS['pdo']->prepare("SELECT id, seat_row, seat_number FROM tickets WHERE id IN ($ticketPlaceholders) AND zone_id = ? AND status = 'available'");
        $stmt->execute(array_merge($selectedSeatIds, [$zoneId]));
        $availableTickets = $stmt->fetchAll();

        if (count($availableTickets) !== count($selectedSeatIds)) {
            return ['success' => false, 'message' => 'Algunos asientos ya no están disponibles. Por favor actualiza tu selección.'];
        }

        $selectedLabels = array_map(function ($ticket) {
            return $ticket['seat_row'] . $ticket['seat_number'];
        }, $availableTickets);

        $key = $eventId . '-' . $zoneId . '-' . implode('-', $selectedSeatIds);
        $_SESSION['cart'][$key] = [
            'event_id' => $eventId,
            'zone_id' => $zoneId,
            'quantity' => $quantity,
            'seat_ids' => $selectedSeatIds,
            'seat_labels' => $selectedLabels
        ];

        return ['success' => true];
    }

    $key = $eventId . '-' . $zoneId;

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] = min(10, $_SESSION['cart'][$key]['quantity'] + $quantity);
    } else {
        $_SESSION['cart'][$key] = [
            'event_id' => $eventId,
            'zone_id' => $zoneId,
            'quantity' => $quantity
        ];
    }

    return ['success' => true];
}

function removeFromCart($key) {
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
}

function getCartTotal($pdo) {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("SELECT price FROM event_zones WHERE id = ?");
            $stmt->execute([$item['zone_id']]);
            $zone = $stmt->fetch();
            if ($zone) {
                $total += $zone['price'] * $item['quantity'];
            }
        }
    }
    return $total;
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function loginUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function registerUser($pdo, $name, $email, $password, $phone = null) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $phone]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function getAllEvents($pdo) {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function getAllOrders($pdo) {
    $stmt = $pdo->query("SELECT o.*, COALESCE(o.customer_name, u.name) as customer_name, COALESCE(o.customer_email, u.email) as customer_email, e.title as event_title 
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         JOIN events e ON o.event_id = e.id 
                         ORDER BY o.created_at DESC");
    return $stmt->fetchAll();
}

function getOrderStats($pdo) {
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('confirmed', 'completed')");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE status IN ('confirmed', 'completed')");
    $stats['total_revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'published'");
    $stats['total_events'] = $stmt->fetch()['total'];
    
    return $stats;
}

function createOrder($pdo, $userId, $eventId, $items, $total, $paymentMethod, $customerData) {
    $pdo->beginTransaction();
    
    try {
        $orderNumber = generateOrderNumber();
        $paymentStatus = $paymentMethod === 'cash' ? 'pending' : 'pending';
        $orderStatus = 'pending';
        
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, event_id, total, tickets_count, payment_method, customer_name, customer_email, customer_phone) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $orderNumber,
            $userId,
            $eventId,
            $total,
            $items['total_tickets'],
            $paymentMethod,
            $customerData['name'],
            $customerData['email'],
            $customerData['phone'] ?? null
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        foreach ($items['zones'] as $zoneItem) {
            $stmt = $pdo->prepare("SELECT id, event_id, price, available FROM event_zones WHERE id = ? FOR UPDATE");
            $stmt->execute([$zoneItem['zone_id']]);
            $zone = $stmt->fetch();

            if (!$zone || (int) $zone['event_id'] !== (int) $eventId) {
                throw new RuntimeException('Zona invalida.');
            }

            if ((int) $zone['available'] < (int) $zoneItem['quantity']) {
                throw new RuntimeException('No hay suficientes boletas disponibles.');
            }

            $stmt = $pdo->prepare("UPDATE event_zones SET available = available - ? WHERE id = ?");
            $stmt->execute([$zoneItem['quantity'], $zoneItem['zone_id']]);

            if (!empty($zoneItem['seat_ids'])) {
                $ticketIds = array_filter(array_map('intval', $zoneItem['seat_ids']));
                $ticketPlaceholders = implode(',', array_fill(0, count($ticketIds), '?'));
                $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id IN ($ticketPlaceholders) AND zone_id = ? AND status = 'available' FOR UPDATE");
                $stmt->execute(array_merge($ticketIds, [$zoneItem['zone_id']]));
                $availableTickets = $stmt->fetchAll();

                if (count($availableTickets) !== count($ticketIds)) {
                    throw new RuntimeException('Algunos asientos seleccionados ya no están disponibles.');
                }

                foreach ($ticketIds as $ticketId) {
                    $stmt = $pdo->prepare("UPDATE tickets SET user_id = ?, status = 'reserved', purchase_date = NOW() WHERE id = ?");
                    $stmt->execute([$userId, $ticketId]);

                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, ticket_id, price) VALUES (?, ?, ?)");
                    $stmt->execute([$orderId, $ticketId, $zone['price']]);
                }
            } else {
                for ($i = 0; $i < $zoneItem['quantity']; $i++) {
                    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_code, event_id, zone_id, user_id, status, purchase_date) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        generateTicketCode(),
                        $eventId,
                        $zoneItem['zone_id'],
                        $userId,
                        'reserved'
                    ]);

                    $ticketId = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, ticket_id, price) VALUES (?, ?, ?)");
                    $stmt->execute([$orderId, $ticketId, $zone['price']]);
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, status = ? WHERE id = ?");
        $stmt->execute([$paymentStatus, $orderStatus, $orderId]);
        
        $pdo->commit();
        $_SESSION['last_order_id'] = (int) $orderId;
        return $orderId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateOrderState($pdo, $orderId, $action) {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new RuntimeException('Orden no encontrada.');
        }

        if ($order['status'] !== 'pending') {
            throw new RuntimeException('La orden ya fue procesada.');
        }

        $stmt = $pdo->prepare("SELECT oi.ticket_id, t.zone_id, t.status
                              FROM order_items oi
                              JOIN tickets t ON oi.ticket_id = t.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        if ($action === 'confirm') {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
            $stmt->execute([$orderId]);

            $stmt = $pdo->prepare("UPDATE tickets t
                                  JOIN order_items oi ON oi.ticket_id = t.id
                                  SET t.status = 'sold'
                                  WHERE oi.order_id = ?");
            $stmt->execute([$orderId]);
        } elseif ($action === 'cancel') {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed', status = 'cancelled' WHERE id = ?");
            $stmt->execute([$orderId]);

            $stmt = $pdo->prepare("UPDATE tickets t
                                  JOIN order_items oi ON oi.ticket_id = t.id
                                  SET t.status = 'cancelled'
                                  WHERE oi.order_id = ?");
            $stmt->execute([$orderId]);

            $zoneCounts = [];
            foreach ($items as $item) {
                $zoneId = (int) $item['zone_id'];
                $zoneCounts[$zoneId] = ($zoneCounts[$zoneId] ?? 0) + 1;
            }

            $stmt = $pdo->prepare("UPDATE event_zones SET available = available + ? WHERE id = ?");
            foreach ($zoneCounts as $zoneId => $count) {
                $stmt->execute([$count, $zoneId]);
            }
        } else {
            throw new RuntimeException('Accion no soportada.');
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sendEmail($to, $subject, $body) {
    $headers = "From: noreply@misterboleta.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}
