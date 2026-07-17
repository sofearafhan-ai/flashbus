<?php

require_once 'db.php';
require_once 'functions.php';

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   GET BOOKING ID
========================= */
$booking_id = (int)($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['users_id'] ?? 0;

if(!$booking_id || !$user_id){
    die("Invalid request.");
}

/* =========================
   GET BOOKING DATA
========================= */
$stmt=$conn->prepare("
    SELECT
        bk.*,
        s.travel_date,
        s.departure_time,
        s.arrival_time,
        s.platform_number,
        s.status AS schedule_status,
        r.origin,
        r.destination,
        b.plate_number,
        b.total_seats,
        o.operators_name
    FROM bookings bk
    JOIN schedules s ON bk.schedules_id=s.schedules_id
    JOIN routes r ON s.routes_id=r.routes_id
    JOIN buses b ON s.buses_id=b.buses_id
    JOIN operators o ON b.operators_id=o.operators_id
    WHERE bk.bookings_id=? AND bk.users_id=?
");

$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking=$stmt->get_result()->fetch_assoc();

if(!$booking){
    die("Booking not found.");
}

/* =========================
   GET PASSENGERS
========================= */
$pstmt=$conn->prepare("
    SELECT
        p.passenger_name,
        p.gender,
        p.phone,
        s.seat_number
    FROM passengers p
    JOIN seats s ON p.seats_id=s.seats_id
    WHERE p.bookings_id=?
");

$pstmt->bind_param("i", $booking_id);
$pstmt->execute();
$passengers=$pstmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   QR DATA
========================= */
$qrRawData = "Booking: " . $booking['booking_code'] . " | Route: " . $booking['origin'] . " to " . $booking['destination'] . " | Date: " . $booking['travel_date'];

$pageTitle="E-Ticket";
include 'header.php';
?>

<div class="ticket">
    <div class="ticket-header">
        <div class="brand">🚌 FlashBus</div>
        <div class="status">
            <?=htmlspecialchars($booking['booking_status'])?>
        </div>
    </div>

    <div class="ticket-content">
        <!-- BAHAGIAN HALUAN (ROUTE) -->
        <div class="route">
            <div class="route-city"><?=htmlspecialchars($booking['origin'])?></div>
            <span class="route-arrow">➔</span>
            <div class="route-city"><?=htmlspecialchars($booking['destination'])?></div>
        </div>

        <!-- MAKLUMAT PERJALANAN (GRID RINGKAS) -->
        <div class="info-grid">
            <div>
                <label>Booking Code</label>
                <p><?=htmlspecialchars($booking['booking_code'])?></p>
            </div>
            <div>
                <label>Operator</label>
                <p><?=htmlspecialchars($booking['operators_name'])?></p>
            </div>
            <div>
                <label>Travel Date</label>
                <p><?=date('d M Y', strtotime($booking['travel_date']))?></p>
            </div>
            <div>
                <label>Departure</label>
                <p><?=date('H:i', strtotime($booking['departure_time']))?></p>
            </div>
            <div>
                <label>Arrival</label>
                <p><?=date('H:i', strtotime($booking['arrival_time']))?></p>
            </div>
            <div>
                <label>Platform</label>
                <p><?=htmlspecialchars($booking['platform_number'] ?? 'TBA')?></p>
            </div>
            <div style="grid-column: span 2;">
                <label>Bus Plate Number</label>
                <p><?=htmlspecialchars($booking['plate_number'])?></p>
            </div>
        </div>

        <div class="divider"></div>

        <!-- MAKLUMAT PENUMPANG -->
        <h3 class="section-title">Passenger Details</h3>

        <?php foreach($passengers as $p): ?>
            <div class="passenger-card">
                <div class="passenger-info">
                    <span class="p-name"><?=htmlspecialchars($p['passenger_name'])?></span>
                    <span class="p-meta"><?=htmlspecialchars($p['gender'])?> • <?=htmlspecialchars($p['phone'])?></span>
                </div>
                <div class="seat-badge">
                    Seat <?=htmlspecialchars($p['seat_number'])?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="divider"></div>

        <!-- QR CODE -->
        <div class="qr-section">
            <div id="qrcode"></div>
            <p class="qr-tip">Scan QR code at boarding gate</p>
        </div>

        <!-- BUTANG NAVIGASI -->
        <div class="buttons no-print">
            <a href="my_bookings.php" class="btn btn-back">Back</a>
            <button onclick="window.print()" class="btn btn-print">Print Ticket</button>
        </div>
    </div>
</div>

<style>
/* CSS DENGAN REKABENTUK KOMPAK (STYLE PHONE BOARDING PASS) */
body { 
    background: #f1f3f6; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.ticket { 
    max-width: 460px; /* Diturunkan ke saiz poket/boarding pass */
    margin: 30px auto; 
    background: white; 
    border-radius: 16px; 
    overflow: hidden; 
    box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
    border: 1px solid #eaeaea;
}

.ticket-header { 
    background: #ff9800; 
    color: white; 
    padding: 16px 20px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}

.brand { 
    font-size: 20px; 
    font-weight: 700; 
    letter-spacing: -0.5px;
}

.status { 
    background: rgba(255, 255, 255, 0.2); 
    color: white; 
    padding: 5px 12px; 
    border-radius: 50px; 
    font-size: 12px;
    font-weight: bold; 
    text-transform: uppercase;
    border: 1px solid rgba(255, 255, 255, 0.4);
}

.ticket-content { 
    padding: 20px; 
}

/* Haluan Perjalanan */
.route { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
    padding: 0 5px;
}

.route-city { 
    font-size: 18px; 
    font-weight: 700; 
    color: #2b3a4a;
}

.route-arrow { 
    font-size: 16px; 
    color: #ff9800; 
}

/* Grid Maklumat */
.info-grid { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 12px; 
    background: #fffcf8; 
    padding: 16px; 
    border-radius: 12px; 
    border: 1px solid #fff3e0;
}

.info-grid label { 
    color: #888; 
    font-size: 11px; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 2px;
}

.info-grid p { 
    font-weight: 600; 
    color: #333; 
    font-size: 13.5px;
    margin: 0;
}

.divider { 
    border-top: 1px dashed #ddd; 
    margin: 20px 0; 
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    margin-bottom: 12px;
    letter-spacing: 0.5px;
}

/* Passenger Card */
.passenger-card { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: #f8f9fa; 
    padding: 12px 14px; 
    border-radius: 10px; 
    margin-bottom: 8px; 
    border: 1px solid #eee;
}

.passenger-info {
    display: flex;
    flex-direction: column;
}

.p-name {
    font-weight: 600;
    color: #333;
    font-size: 13.5px;
}

.p-meta {
    font-size: 11.5px;
    color: #777;
    margin-top: 2px;
}

.seat-badge { 
    background: #e3f2fd; 
    color: #0d47a1; 
    padding: 5px 12px; 
    border-radius: 8px; 
    font-weight: bold; 
    font-size: 12px;
}

/* QR Code */
.qr-section { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    text-align: center; 
    margin-top: 15px;
}

#qrcode {
    padding: 10px;
    background: white;
    border: 1px solid #eee;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

#qrcode img {
    display: block;
}

.qr-tip { 
    color: #777; 
    font-size: 12px; 
    margin-top: 10px;
}

/* Butang Kawalan */
.buttons { 
    display: flex;
    gap: 10px;
    justify-content: center; 
    margin-top: 20px; 
}

.btn { 
    flex: 1;
    padding: 10px 0; 
    border-radius: 8px; 
    text-decoration: none; 
    border: none; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 13px;
    text-align: center;
    transition: background 0.2s;
}

.btn-back { 
    background: transparent;
    border: 1.5px solid #ccc; 
    color: #555; 
}

.btn-back:hover {
    background: #f5f5f5;
}

.btn-print { 
    background: #2196f3; 
    color: white; 
}

.btn-print:hover {
    background: #1e88e5;
}

/* Responsif & Cetakan */
@media print {
    .no-print { 
        display: none !important; 
    }
    body {
        background: white;
    }
    .ticket { 
        box-shadow: none; 
        border: none;
        margin: 0;
    }
}
</style>

<!-- Pustaka JavaScript Generasi QR Code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    window.onload = function() {
        document.getElementById("qrcode").innerHTML = "";
        
        new QRCode(document.getElementById("qrcode"), {
            text: <?php echo json_encode($qrRawData); ?>,
            width: 140, // Saiz QR diubah ke 140 agar padan dengan kontena kecil
            height: 140
        });
    };
</script>

<?php include 'footer.php'; ?>