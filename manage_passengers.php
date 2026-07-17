<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =====================================================
   GET SCHEDULE ID
===================================================== */
$schedules_id = (int)($_GET['schedules_id'] ?? 0);

if($schedules_id <= 0){
    header("Location: manage_bookings.php");
    exit;
}

/* =====================================================
   GET TRIP INFO
===================================================== */
$stmt = $conn->prepare("
    SELECT
        r.origin,
        r.destination,
        s.travel_date,
        s.departure_time,
        b.bus_code,
        b.total_seats,
        o.operators_name
    FROM schedules s
    JOIN routes r ON s.routes_id = r.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    JOIN operators o ON b.operators_id = o.operators_id
    WHERE s.schedules_id=?
");

$stmt->bind_param("i", $schedules_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    header("Location: manage_bookings.php");
    exit;
}

/* =====================================================
   GET PASSENGERS
===================================================== */
$stmt = $conn->prepare("
    SELECT
        p.passenger_name,
        p.gender,
        p.phone,
        st.seat_number,
        bk.booking_code,
        bk.booking_status,
        s.travel_date,
        s.departure_time
    FROM bookings bk
    JOIN passengers p ON bk.bookings_id=p.bookings_id
    JOIN seats st ON p.seats_id=st.seats_id
    JOIN schedules s ON bk.schedules_id=s.schedules_id
    WHERE bk.schedules_id=?
    ORDER BY st.seat_number ASC
");

$stmt->bind_param("i", $schedules_id);
$stmt->execute();
$passengers = $stmt->get_result();

/* =====================================================
   COUNT PASSENGER & AVAILABLE SEATS
===================================================== */
$totalPassengers = $passengers->num_rows;
$availableSeat = $trip['total_seats'] - $totalPassengers;

$pageTitle = "Passenger List - " . htmlspecialchars($trip['bus_code']);
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING & CORE STYLES
   ========================================================================== */
* { 
    box-sizing: border-box; 
}

/* Sidebar Asal FlashBus */
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
}
.admin-sidebar a:hover, 
.admin-sidebar a.active { 
    background: #FF6B1A; 
    color: white; 
}

/* Content Area */
.admin-content { 
    margin-left: 240px; 
    padding: 25px 30px; 
    background-color: #f8fafc;
    min-height: calc(100vh - 56px); 
}
.booking-container { 
    max-width: 1250px; 
    margin: 0 auto; 
}

/* Pengurusan Atas & Butang Action */
.action-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.btn-group {
    display: flex;
    gap: 10px;
}
.back-btn {
    display: inline-flex;
    align-items: center;
    background: #64748b; 
    color: white !important; 
    padding: 9px 16px; 
    border-radius: 6px; 
    text-decoration: none; 
    font-size: 13px; 
    font-weight: 600; 
    transition: background 0.2s;
}
.back-btn:hover { background: #475569; }

.print-btn {
    display: inline-flex;
    align-items: center;
    background: #10b981; 
    color: white !important; 
    padding: 9px 16px; 
    border-radius: 6px; 
    border: none;
    cursor: pointer;
    font-size: 13px; 
    font-weight: 600; 
    transition: background 0.2s;
}
.print-btn:hover { background: #059669; }

/* ==========================================================================
   TRIP INFO CARD DESIGN
   ========================================================================== */
.trip-card {
    background: white; 
    padding: 25px; 
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
}
.route-title { 
    font-size: 20px; 
    font-weight: 700; 
    color: #0A4DA6; 
    margin-bottom: 20px; 
    display: flex;
    align-items: center;
    gap: 10px;
}
.route-arrow { color: #94a3b8; font-size: 16px; }

/* Flex Susunan Ringkasan */
.summary {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
}
.summary-box {
    background: #f8fafc;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}
.summary-box span {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    display: block;
    margin-bottom: 4px;
}
.summary-box strong {
    font-size: 14px;
    color: #1e293b;
    font-weight: 700;
}

/* ==========================================================================
   DATA TABLE DESIGN
   ========================================================================== */
.table-box {
    background: white; 
    border-radius: 10px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0; 
    overflow: hidden; 
    width: 100%;
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    text-align: left; 
    font-size: 13px; 
}
th { 
    background: #f1f5f9; 
    color: #475569; 
    font-weight: 600; 
    padding: 14px 16px; 
    border-bottom: 1px solid #e2e8f0; 
}
td { 
    padding: 14px 16px; 
    border-bottom: 1px solid #f1f5f9; 
    color: #334155; 
    vertical-align: middle; 
}
tr:hover td { 
    background: #f8fafc; 
}

/* Penanda Tempat Duduk Moden */
.seat-badge {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12px;
    display: inline-block;
}

/* Kod Booking */
.code-text {
    font-family: monospace;
    font-weight: 600;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

/* Status Booking Badges */
.status-badge {
    padding: 4px 10px; 
    border-radius: 20px; 
    font-size: 11px; 
    font-weight: 700;
    display: inline-block;
}
.status-confirmed { background-color: #dcfce7; color: #15803d; }
.status-pending { background-color: #fef9c3; color: #a16207; }

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #64748b;
    font-weight: 500;
}

/* ==========================================================================
   CSS PRINTING ADJUSTMENTS (Khas ketika cetak fizikal/PDF)
   ========================================================================== */
@media print {
    body { background: white; color: black; }
    .admin-sidebar, .btn-group, header, footer { display: none !important; }
    .admin-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .trip-card { box-shadow: none !important; border: 1px solid #000 !important; padding: 15px !important; }
    .table-box { box-shadow: none !important; border: none !important; }
    table { width: 100% !important; border: 1px solid #000 !important; }
    th { background: #e2e8f0 !important; color: black !important; border-bottom: 1px solid #000 !important; }
    td { border-bottom: 1px solid #ddd !important; }
    .seat-badge { background: none !important; color: black !important; padding: 0 !important; font-weight: bold; }
    .code-text { background: none !important; padding: 0 !important; }
    .status-badge { background: none !important; color: black !important; padding: 0 !important; }
    .summary { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
    .summary-box { border: 1px solid #ccc !important; background: none !important; }
}

/* Responsive Grid untuk skrin kecil */
@media(max-width: 992px) { 
    .admin-sidebar { position: static; width: 100%; height: auto; } 
    .admin-content { margin-left: 0; padding: 15px; width: 100%; } 
    .summary { grid-template-columns: repeat(3, 1fr); }
}
@media(max-width: 576px) {
    .action-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .summary { grid-template-columns: repeat(2, 1fr); }
    .table-box { overflow-x: auto; }
}
</style>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php" class="active">Bookings</a>
</div>

<!-- MAIN CONTENT -->
<div class="admin-content">
    <div class="booking-container">
        
        <!-- HEADER BUTTONS -->
        <div class="action-header">
            <h2 style="margin:0; font-size:22px; color:#1e293b; font-weight:700;">Passenger Management</h2>
            <div class="btn-group">
                <a href="manage_bookings.php" class="back-btn">
                    <span style="margin-right:6px;">←</span> Back to Bookings
                </a>
                <button onclick="window.print()" class="print-btn">
                    <span style="margin-right:6px;"></span> Print List
                </button>
            </div>
        </div>

        <!-- TRIP INFO DETAILS CARD -->
        <div class="trip-card">
            <div class="route-title">
                <span><?php echo htmlspecialchars($trip['origin']); ?></span>
                <span class="route-arrow">➔</span>
                <span><?php echo htmlspecialchars($trip['destination']); ?></span>
            </div>
            
            <div class="summary">
                <div class="summary-box">
                    <span>Bus Code</span>
                    <strong><?php echo htmlspecialchars($trip['bus_code']); ?></strong>
                </div>
                <div class="summary-box">
                    <span>Operator</span>
                    <strong><?php echo htmlspecialchars($trip['operators_name']); ?></strong>
                </div>
                <div class="summary-box">
                    <span>Travel Date</span>
                    <strong><?php echo date("d M Y", strtotime($trip['travel_date'])); ?></strong>
                </div>
                <div class="summary-box">
                    <span>Departure</span>
                    <strong style="color: #0A4DA6;"><?php echo date("h:i A", strtotime($trip['departure_time'])); ?></strong>
                </div>
                <div class="summary-box">
                    <span>Total Passengers</span>
                    <strong><?php echo $totalPassengers; ?> / <?php echo $trip['total_seats']; ?></strong>
                </div>
                <div class="summary-box">
                    <span>Available Seats</span>
                    <strong style="color: #10b981;"><?php echo $availableSeat; ?> Seats</strong>
                </div>
            </div>
        </div>

        <!-- PASSENGER LIST TABLE -->
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No</th>
                        <th style="width: 25%;">Passenger Name</th>
                        <th style="width: 10%;">Gender</th>
                        <th style="width: 15%;">Phone Number</th>
                        <th style="width: 10%; text-align: center;">Seat No</th>
                        <th style="width: 20%;">Booking Code</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($totalPassengers > 0) { ?>
                        <?php 
                        $no = 1; 
                        while($p = $passengers->fetch_assoc()) { 
                            $statusClass = (strtolower($p['booking_status']) == 'confirmed') ? 'status-confirmed' : 'status-pending';
                        ?>
                        <tr>
                            <td style="text-align: center; color: #64748b; font-weight: 600;"><?php echo $no++; ?></td>
                            <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($p['passenger_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['gender']); ?></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td style="text-align: center;">
                                <span class="seat-badge"><?php echo htmlspecialchars($p['seat_number']); ?></span>
                            </td>
                            <td>
                                <span class="code-text"><?php echo htmlspecialchars($p['booking_code']); ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($p['booking_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                📑 No passengers have booked seats for this schedule yet.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>