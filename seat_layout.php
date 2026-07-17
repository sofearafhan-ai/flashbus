<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';
requireAdmin();

/* =========================
   GET BUS ID
========================= */

$bus_id = 0;

// support URL baru
if(isset($_GET['buses_id'])){
    $bus_id = (int)$_GET['buses_id'];
}
// support URL lama
elseif(isset($_GET['bus'])){
    $bus_id = (int)$_GET['bus'];
}

if($bus_id <= 0){
    die("Invalid Bus ID");
}

/* =========================
   GET BUS INFO
========================= */

$stmt = $conn->prepare("
    SELECT
    b.buses_id,
    b.bus_code,
    b.plate_number,
    b.total_seats,
    o.operators_name
    FROM buses b
    JOIN operators o ON b.operators_id = o.operators_id
    WHERE b.buses_id = ?
");

$stmt->bind_param("i", $bus_id);
$stmt->execute();
$result = $stmt->get_result();
$bus = $result->fetch_assoc();

if(!$bus){
    die("Bus not found");
}

/* =========================
   GET SEATS
========================= */

$seatStmt = $conn->prepare("
    SELECT
    seats_id,
    seat_number
    FROM seats
    WHERE buses_id = ?
    ORDER BY seats_id ASC
");

$seatStmt->bind_param("i", $bus_id);
$seatStmt->execute();
$seats = $seatStmt->get_result();

$pageTitle = "Seat Layout";
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING (INTEGRATED WITH FLASHBUS SIDEBAR)
   ========================================================================== */
* {
    box-sizing: border-box;
}

/* Sidebar Asal FlashBus - Diperbetulkan sepenuhnya */
.admin-sidebar {
    position: fixed;
    top: 56px;
    left: 0;
    width: 240px;
    height: calc(100vh - 56px);
    background: #0A4DA6;
    color: white;
    z-index: 99;
}
.admin-sidebar div { 
    padding: 20px 24px; 
    font-size: 18px; 
    font-weight: 700; 
    border-bottom: 1px solid rgba(255,255,255,0.1); 
}
.admin-sidebar a {
    display: block;
    padding: 16px 24px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: background 0.2s;
}
.admin-sidebar a:hover,
.admin-sidebar a.active {
    background: #FF6B1A;
    color: white;
    font-weight: 600;
}

/* Content Area Fixes */
.admin-content {
    margin-left: 240px; 
    padding: 25px 30px;
    background-color: #f8fafc;
    min-height: calc(100vh - 56px);
}
.seat-page {
    max-width: 1000px;
    margin: 0 auto;
}

/* HEADER BUTTON ATAS */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #0A4DA6;
    margin: 0;
}
.back-btn {
    display: inline-flex;
    align-items: center;
    background: #FF6B1A;
    color: #fff;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: background 0.2s;
}
.back-btn:hover {
    background: #e0560f;
}

/* ==========================================================================
   BUS INFO (KECIKKAN COMPONENT)
   ========================================================================== */
.bus-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    background: #fff;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
}
.info-box {
    background: #f8fafc;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #edf2f7;
}
.info-box span {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.info-box strong {
    font-size: 16px;
    color: #0A4DA6;
}

/* ==========================================================================
   SEAT CONTAINER (SLIMMER & COMPACT)
   ========================================================================== */
.seat-container {
    max-width: 500px; /* Kecikkan saiz container bas */
    margin: auto;
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.driver {
    width: 110px;
    margin: 0 auto 25px;
    background: #334155;
    color: #fff;
    text-align: center;
    padding: 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0.5px;
}

/* SEAT GRID COMPACT */
.seat-grid {
    display: grid;
    grid-template-columns: 65px 30px 65px 65px; /* Dikecilkan */
    justify-content: center;
    gap: 12px;
}
.seat {
    height: 45px; /* Dipendekkan dari 55px */
    background: #0A4DA6;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
}
.aisle {
    background: transparent;
}

/* LEGEND */
.legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 25px;
    font-size: 13px;
    color: #475569;
}
.legend div {
    display: flex;
    align-items: center;
    gap: 6px;
}
.box {
    width: 14px;
    height: 14px;
    border-radius: 3px;
}
.available { background: #0A4DA6; }
.driver-box { background: #334155; }

/* RESPONSIVE FLUID */
@media(max-width: 992px){
    .admin-sidebar { position: static; width: 100%; height: auto; }
    .admin-sidebar a { display: inline-block; padding: 10px 15px; }
    .admin-content { margin-left: 0; padding: 15px; }
    .bus-info { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width: 500px){
    .bus-info { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .seat-grid { grid-template-columns: 55px 20px 55px 55px; gap: 8px; }
    .seat { height: 40px; font-size: 12px; }
}
</style>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php"class="active">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<div class="admin-content">
    <div class="seat-page">

        <!-- HEADER BUTTON DI ATAS -->
        <div class="page-header">
            <h1 class="page-title">Bus Seat Layout</h1>
            <a href="manage_buses.php" class="back-btn">← Back To Buses</a>
        </div>

        <!-- BUS INFO SECTION -->
        <div class="bus-info">
            <div class="info-box">
                <span>Bus Code</span>
                <strong><?=htmlspecialchars($bus['bus_code']);?></strong>
            </div>
            <div class="info-box">
                <span>Operator</span>
                <strong><?=htmlspecialchars($bus['operators_name']);?></strong>
            </div>
            <div class="info-box">
                <span>Plate Number</span>
                <strong><?=htmlspecialchars($bus['plate_number']);?></strong>
            </div>
            <div class="info-box">
                <span>Total Seats</span>
                <strong><?=$bus['total_seats'];?> Seats</strong>
            </div>
        </div>

        <!-- SEAT COMPACT CONTAINER -->
        <div class="seat-container">
            <div class="driver">🚍 DRIVER</div>
            
            <div class="seat-grid">
                <?php
                $count = 0;
                while($seat = $seats->fetch_assoc()){
                    $count++;
                    
                    // Format: 1 Seat Kiri, Aisle (Laluan), 2 Seat Kanan
                    if($count % 3 == 1){
                        echo '<div class="seat">'.$seat['seat_number'].'</div>';
                        echo '<div class="aisle"></div>';
                    } else {
                        echo '<div class="seat">'.$seat['seat_number'].'</div>';
                    }
                }
                ?>
            </div>

            <div class="legend">
                <div>
                    <div class="box available"></div>
                    Available Seat
                </div>
                <div>
                    <div class="box driver-box"></div>
                    Driver
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>