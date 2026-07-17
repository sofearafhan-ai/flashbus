<?php
// Run this ONCE in your browser: http://localhost/flashbus_db/seed_routes.php
// It inserts sample operators, buses, routes, schedules, and seats using PHP code.
// Delete this file afterwards.

require_once 'db.php';

echo "<h2>Seeding FlashBus sample data...</h2>";

// ---------- Operators ----------
$operators = [
    ['Star Express', '03-12345678'],
    ['Golden Coach', '03-87654321'],
];
$operatorIds = [];
foreach ($operators as $op) {
    $stmt = $conn->prepare("INSERT INTO operators (operator_name, contact_number) VALUES (?, ?)");
    $stmt->bind_param('ss', $op[0], $op[1]);
    $stmt->execute();
    $operatorIds[] = $conn->insert_id;
    echo "Added operator: {$op[0]}<br>";
}

// ---------- Buses ----------
$buses = [
    ['plate' => 'VBU 1234', 'type' => 'Executive', 'seats' => 20, 'operator' => $operatorIds[0]],
    ['plate' => 'VBU 5678', 'type' => 'Standard',  'seats' => 30, 'operator' => $operatorIds[0]],
    ['plate' => 'WXY 9012', 'type' => 'Executive', 'seats' => 20, 'operator' => $operatorIds[1]],
];
$busIds = [];
foreach ($buses as $b) {
    $stmt = $conn->prepare("INSERT INTO buses (operator_id, plate_number, bus_type, total_seats) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $b['operator'], $b['plate'], $b['type'], $b['seats']);
    $stmt->execute();
    $busId = $conn->insert_id;
    $busIds[] = $busId;
    echo "Added bus: {$b['plate']}<br>";

    // generate seats S1..Sn for this bus
    $seatStmt = $conn->prepare("INSERT INTO seats (bus_id, seat_number) VALUES (?, ?)");
    for ($i = 1; $i <= $b['seats']; $i++) {
        $seatNum = 'S' . $i;
        $seatStmt->bind_param('is', $busId, $seatNum);
        $seatStmt->execute();
    }
}

// ---------- Routes ----------
$routes = [
    ['Kuala Lumpur', 'Penang', 350, 45.00],
    ['Kuala Lumpur', 'Johor Bahru', 330, 40.00],
    ['Kuala Lumpur', 'Malacca', 150, 25.00],
    ['Penang', 'Kuala Lumpur', 350, 45.00],
];
$routeIds = [];
foreach ($routes as $r) {
    $stmt = $conn->prepare("INSERT INTO routes (origin, destination, distance_km, base_price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssid', $r[0], $r[1], $r[2], $r[3]);
    $stmt->execute();
    $routeIds[] = $conn->insert_id;
    echo "Added route: {$r[0]} → {$r[1]}<br>";
}

// ---------- Schedules (today's date, using the routes/buses just created) ----------
$today = date('Y-m-d');
$schedules = [
    ['route' => $routeIds[0], 'bus' => $busIds[0], 'dep' => '08:00:00', 'arr' => '13:00:00', 'platform' => 'A1', 'price' => 45.00],
    ['route' => $routeIds[0], 'bus' => $busIds[1], 'dep' => '14:00:00', 'arr' => '19:00:00', 'platform' => 'A2', 'price' => 40.00],
    ['route' => $routeIds[1], 'bus' => $busIds[2], 'dep' => '09:30:00', 'arr' => '13:30:00', 'platform' => 'B1', 'price' => 40.00],
    ['route' => $routeIds[2], 'bus' => $busIds[0], 'dep' => '10:00:00', 'arr' => '12:30:00', 'platform' => 'C1', 'price' => 25.00],
];
foreach ($schedules as $s) {
    $stmt = $conn->prepare("INSERT INTO schedules (route_id, bus_id, travel_date, departure_time, arrival_time, platform_number, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iisssd', $s['route'], $s['bus'], $today, $s['dep'], $s['arr'], $s['platform'], $s['price']);
    $stmt->execute();
    echo "Added schedule for route ID {$s['route']} departing {$s['dep']}<br>";
}

echo "<h3>Done! <a href='index_fb.php'>Go to homepage</a></h3>";
echo "<p><strong>Please delete seed_routes.php now.</strong></p>";