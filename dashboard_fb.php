<?php
$inAdmin = true;
require_once 'db.php';
require_once 'functions.php';
requireAdmin();

/* ==========================================================================
   KPI: TOTAL BOOKINGS
   ========================================================================== */
$totalBookings   = $conn->query("SELECT COUNT(*) total FROM bookings")->fetch_assoc()['total'] ?? 0;
$dailyBookings   = $conn->query("SELECT COUNT(*) total FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$weeklyBookings  = $conn->query("SELECT COUNT(*) total FROM bookings WHERE YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'] ?? 0;
$monthlyBookings = $conn->query("SELECT COUNT(*) total FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
$yearlyBookings  = $conn->query("SELECT COUNT(*) total FROM bookings WHERE YEAR(booking_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

/* ==========================================================================
   KPI: REVENUE
   ========================================================================== */
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM bookings WHERE booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$dailyRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM bookings WHERE DATE(booking_date) = CURDATE() AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$weeklyRevenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM bookings WHERE YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$monthlyRevenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE()) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$yearlyRevenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM bookings WHERE YEAR(booking_date) = YEAR(CURDATE()) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;

/* ==========================================================================
   KPI: PASSENGERS
   ========================================================================== */
$totalPassengers   = $conn->query("SELECT COUNT(*) total FROM bookings WHERE booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$dailyPassengers   = $conn->query("SELECT COUNT(*) total FROM bookings WHERE DATE(booking_date) = CURDATE() AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$weeklyPassengers  = $conn->query("SELECT COUNT(*) total FROM bookings WHERE YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$monthlyPassengers = $conn->query("SELECT COUNT(*) total FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE()) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$yearlyPassengers  = $conn->query("SELECT COUNT(*) total FROM bookings WHERE YEAR(booking_date) = YEAR(CURDATE()) AND booking_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;

/* ==========================================================================
   KPI: TRIPS
   ========================================================================== */
$totalTrips   = $conn->query("SELECT COUNT(*) total FROM schedules")->fetch_assoc()['total'] ?? 0;
$dailyTrips   = $conn->query("SELECT COUNT(*) total FROM schedules WHERE travel_date = CURDATE()")->fetch_assoc()['total'] ?? 0;
$weeklyTrips  = $conn->query("SELECT COUNT(*) total FROM schedules WHERE YEARWEEK(travel_date, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'] ?? 0;
$monthlyTrips = $conn->query("SELECT COUNT(*) total FROM schedules WHERE MONTH(travel_date) = MONTH(CURDATE()) AND YEAR(travel_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
$yearlyTrips  = $conn->query("SELECT COUNT(*) total FROM schedules WHERE YEAR(travel_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

/* ==========================================================================
   GRAPH DATA: BOOKING PERFORMANCE
   ========================================================================== */
$bookingChart = [];
$result = $conn->query("SELECT DATE(booking_date) date, COUNT(*) total FROM bookings GROUP BY DATE(booking_date) ORDER BY date ASC LIMIT 7");
while ($row = $result->fetch_assoc()) {
    $bookingChart[] = $row;
}

$pageTitle = "Admin Dashboard";
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING (SIDEBAR + CONTENT WRAPPER)
   ========================================================================== */
* {
    box-sizing: border-box;
}

.flashbus-admin-dashboard {
    background: #f8fafc;
    min-height: calc(100vh - 56px);
}

.admin-layout {
    display: flex;
}

/* Sidebar Standard FlashBus */
.admin-sidebar {
    position: fixed;
    top: 56px; /* Diturunkan untuk mengelakkan overlap header */
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

/* Content Area */
.admin-content {
    margin-left: 240px; /* Lebar diubahsuai ke 240px mengikut lebar sidebar */
    padding: 25px 30px;
    width: calc(100% - 240px);
}

.section-title {
    font-size: 26px;
    font-weight: 700;
    color: #0A4DA6;
    margin: 0 0 20px 0;
}

/* ==========================================================================
   KPI CARDS (COMPACT & MODERN)
   ========================================================================== */
.admin-stat-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.admin-stat-card {
    background: white;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 110px;
}

.admin-stat-card:hover {
    transform: translateY(-2px);
    border-color: #0A4DA6;
}

.admin-stat-card h3 {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 6px 0;
    font-weight: 600;
}

.stat-value {
    font-size: 22px;
    font-weight: 700;
    color: #0A4DA6;
    margin: 4px 0;
}

.click-info {
    font-size: 12px;
    color: #FF6B1A;
    margin: 0;
    font-weight: 500;
}

/* ==========================================================================
   MODAL WINDOWS (COMPACT MATCH)
   ========================================================================== */
.booking-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(2px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.booking-modal-content {
    background: white;
    width: 400px;
    padding: 24px;
    border-radius: 12px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

.close-modal {
    position: absolute;
    top: 14px;
    right: 18px;
    font-size: 22px;
    cursor: pointer;
    color: #94a3b8;
}
.close-modal:hover { color: #334155; }

.modal-buttons {
    margin: 15px 0;
    display: flex;
    gap: 8px;
    justify-content: center;
}

.modal-buttons button {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.15s;
}

.modal-buttons button:hover {
    background: #FF6B1A;
    color: white;
    border-color: #FF6B1A;
}

.modal-result {
    margin-top: 15px;
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.modal-number {
    font-size: 28px;
    font-weight: bold;
    color: #FF6B1A;
}

/* ==========================================================================
   CHART CARDS
   ========================================================================== */
.dashboard-card,
.chart-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
}

.dashboard-card h3, .chart-card h3 {
    font-size: 15px;
    color: #334155;
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 700;
}

.dashboard-charts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

canvas {
    max-height: 220px;
}

/* ==========================================================================
   RESPONSIVE MEDIA QUERIES
   ========================================================================== */
@media(max-width: 1200px) {
    .admin-stat-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 900px) {
    .dashboard-charts {
        grid-template-columns: 1fr;
    }
    .admin-sidebar {
        position: static;
        width: 100%;
        height: auto;
    }
    .admin-sidebar a {
        display: inline-block;
        padding: 10px 15px;
    }
    .admin-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    .admin-stat-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flashbus-admin-dashboard">
    <div class="admin-layout">

        <!-- ==========================================================================
             SIDEBAR (SERAGAM & TIDAK TERPOTONG)
             ========================================================================== -->
        <div class="admin-sidebar">
            <div>FlashBus Admin</div>
            <a href="dashboard_fb.php" class="active">Dashboard</a>
            <a href="manage_operators.php">Operators</a>
            <a href="manage_buses.php">Buses</a>
            <a href="manage_routes.php">Routes</a>
            <a href="manage_schedules.php">Schedules</a>
            <a href="manage_bookings.php">Bookings</a>
        </div>

        <div class="admin-content">
            <div class="section-title">Dashboard Overview</div>

            <!-- ==========================================================================
                 KPI STAT CARDS
                 ========================================================================== -->
            <div class="admin-stat-cards">
                <!-- BOOKINGS -->
                <div class="admin-stat-card" onclick="openModal('booking')">
                    <h3>Total Bookings</h3>
                    <div class="stat-value"><?= number_format($totalBookings) ?></div>
                    <p class="click-info">Click to view details</p>
                </div>

                <!-- REVENUE -->
                <div class="admin-stat-card" onclick="openModal('revenue')">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">RM <?= number_format($totalRevenue, 2) ?></div>
                    <p class="click-info">Click to view details</p>
                </div>

                <!-- PASSENGERS -->
                <div class="admin-stat-card" onclick="openModal('passenger')">
                    <h3>Total Passengers</h3>
                    <div class="stat-value"><?= number_format($totalPassengers) ?></div>
                    <p class="click-info">Click to view details</p>
                </div>

                <!-- TRIPS -->
                <div class="admin-stat-card" onclick="openModal('trip')">
                    <h3>Total Trips</h3>
                    <div class="stat-value"><?= number_format($totalTrips) ?></div>
                    <p class="click-info">Click to view details</p>
                </div>
            </div>

            <!-- ==========================================================================
                 MODALS OVERLAYS
                 ========================================================================== -->
            <!-- BOOKING MODAL -->
            <div id="bookingModal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <h3 style="margin: 0; font-size: 15px; color: #0A4DA6; font-weight:700;">Booking Overview</h3>
                    <div class="modal-buttons">
                        <button onclick="showBooking('daily')">Daily</button>
                        <button onclick="showBooking('weekly')">Weekly</button>
                        <button onclick="showBooking('monthly')">Monthly</button>
                        <button onclick="showBooking('yearly')">Yearly</button>
                    </div>
                    <div class="modal-result">
                        <div class="modal-number" id="modalNumber">0</div>
                    </div>
                </div>
            </div>

            <!-- REVENUE MODAL -->
            <div id="revenueModal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <h3 style="margin: 0; font-size: 15px; color: #0A4DA6; font-weight:700;">Revenue Overview</h3>
                    <div class="modal-buttons">
                        <button onclick="showRevenue('daily')">Daily</button>
                        <button onclick="showRevenue('weekly')">Weekly</button>
                        <button onclick="showRevenue('monthly')">Monthly</button>
                        <button onclick="showRevenue('yearly')">Yearly</button>
                    </div>
                    <div class="modal-result">
                        <div class="modal-number" id="revenueNumber">RM 0.00</div>
                    </div>
                </div>
            </div>

            <!-- PASSENGER MODAL -->
            <div id="passengerModal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <h3 style="margin: 0; font-size: 15px; color: #0A4DA6; font-weight:700;">Passenger Overview</h3>
                    <div class="modal-buttons">
                        <button onclick="showPassenger('daily')">Daily</button>
                        <button onclick="showPassenger('weekly')">Weekly</button>
                        <button onclick="showPassenger('monthly')">Monthly</button>
                        <button onclick="showPassenger('yearly')">Yearly</button>
                    </div>
                    <div class="modal-result">
                        <div class="modal-number" id="passengerNumber">0</div>
                    </div>
                </div>
            </div>

            <!-- TRIPS MODAL -->
            <div id="tripModal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <h3 style="margin: 0; font-size: 15px; color: #0A4DA6; font-weight:700;">Trips Overview</h3>
                    <div class="modal-buttons">
                        <button onclick="showTrip('daily')">Daily</button>
                        <button onclick="showTrip('weekly')">Weekly</button>
                        <button onclick="showTrip('monthly')">Monthly</button>
                        <button onclick="showTrip('yearly')">Yearly</button>
                    </div>
                    <div class="modal-result">
                        <div class="modal-number" id="tripNumber">0</div>
                    </div>
                </div>
            </div>

            <!-- ==========================================================================
                 PERFORMANCE CHART
                 ========================================================================== -->
            <div class="dashboard-card">
                <h3>Booking Performance (Last 7 Days)</h3>
                <canvas id="performanceChart"></canvas>
            </div>

            <div class="dashboard-charts">
                <div class="chart-card">
                    <h3>Booking Status</h3>
                    <canvas id="bookingStatusChart"></canvas>
                </div>

                <div class="chart-card">
                    <h3>Schedule Status</h3>
                    <canvas id="scheduleStatusChart"></canvas>
                </div>
            </div>

        </div> <!-- /.admin-content -->
    </div> <!-- /.admin-layout -->
</div> <!-- /.flashbus-admin-dashboard -->

<script>
/* ==========================================================================
   MODAL CONTROLLER
   ========================================================================== */
function openModal(type) {
    document.getElementById(type + "Modal").style.display = "flex";
}

function closeModal() {
    document.querySelectorAll(".booking-modal").forEach(function(modal) {
        modal.style.display = "none";
    });
}

/* ==========================================================================
   KPI EVENT DATA DISPATCHERS
   ========================================================================== */
function showBooking(type) {
    let data = {
        daily: <?= $dailyBookings ?>,
        weekly: <?= $weeklyBookings ?>,
        monthly: <?= $monthlyBookings ?>,
        yearly: <?= $yearlyBookings ?>
    };
    document.getElementById("modalNumber").innerHTML = data[type];
}

function showRevenue(type) {
    let data = {
        daily: <?= $dailyRevenue ?>,
        weekly: <?= $weeklyRevenue ?>,
        monthly: <?= $monthlyRevenue ?>,
        yearly: <?= $yearlyRevenue ?>
    };
    document.getElementById("revenueNumber").innerHTML = "RM " + Number(data[type]).toFixed(2);
}

function showPassenger(type) {
    let data = {
        daily: <?= $dailyPassengers ?>,
        weekly: <?= $weeklyPassengers ?>,
        monthly: <?= $monthlyPassengers ?>,
        yearly: <?= $yearlyPassengers ?>
    };
    document.getElementById("passengerNumber").innerHTML = data[type];
}

function showTrip(type) {
    let data = {
        daily: <?= $dailyTrips ?>,
        weekly: <?= $weeklyTrips ?>,
        monthly: <?= $monthlyTrips ?>,
        yearly: <?= $yearlyTrips ?>
    };
    document.getElementById("tripNumber").innerHTML = data[type];
}

/* ==========================================================================
   CHART CONTROLLERS (CHART.JS)
   ========================================================================== */
let bookingLabels = [];
let bookingValues = [];

<?php foreach ($bookingChart as $row) { ?>
    bookingLabels.push("<?= $row['date'] ?>");
    bookingValues.push(<?= $row['total'] ?>);
<?php } ?>

// 1. Line Chart: Booking Performance
new Chart(document.getElementById('performanceChart'), {
    type: 'line',
    data: {
        labels: bookingLabels,
        datasets: [{
            label: 'Bookings',
            data: bookingValues,
            borderColor: '#0A4DA6',
            backgroundColor: 'rgba(10, 77, 166, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// 2. Pie Chart: Booking Status
<?php
$bookingStatus = [];
$statusQuery = $conn->query("SELECT booking_status, COUNT(*) total FROM bookings GROUP BY booking_status");
while ($row = $statusQuery->fetch_assoc()) {
    $bookingStatus[] = $row;
}
?>

let statusLabels = [];
let statusValues = [];

<?php foreach ($bookingStatus as $row) { ?>
    statusLabels.push("<?= $row['booking_status'] ?>");
    statusValues.push(<?= $row['total'] ?>);
<?php } ?>

new Chart(document.getElementById('bookingStatusChart'), {
    type: 'pie',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#64748b']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// 3. Bar Chart: Schedule Status
<?php
$scheduleStatus = [];
$scheduleStatusQuery = $conn->query("SELECT status, COUNT(*) total FROM schedules GROUP BY status");
while ($row = $scheduleStatusQuery->fetch_assoc()) {
    $scheduleStatus[] = $row;
}
?>

let scheduleLabels = [];
let scheduleValues = [];

<?php foreach ($scheduleStatus as $row) { ?>
    scheduleLabels.push("<?= $row['status'] ?>");
    scheduleValues.push(<?= $row['total'] ?>);
<?php } ?>

new Chart(document.getElementById('scheduleStatusChart'), {
    type: 'bar',
    data: {
        labels: scheduleLabels,
        datasets: [{
            label: 'Schedules',
            data: scheduleValues,
            backgroundColor: '#FF6B1A'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php include 'footer.php'; ?>