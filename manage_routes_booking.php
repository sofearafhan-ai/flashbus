<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =====================================================
   GET OPERATOR ID
===================================================== */
$operator_id = (int)($_GET['operator_id'] ?? 0);

if($operator_id <= 0){
    header("Location: manage_bookings.php");
    exit;
}

/* =====================================================
   GET OPERATOR NAME
===================================================== */
$stmt = $conn->prepare("
    SELECT operators_name
    FROM operators
    WHERE operators_id = ?
");
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$operator = $stmt->get_result()->fetch_assoc();

if (!$operator) {
    header("Location: manage_bookings.php");
    exit;
}

/* =====================================================
   GET ROUTES
===================================================== */
$stmt = $conn->prepare("
    SELECT DISTINCT r.routes_id, r.origin, r.destination
    FROM routes r
    JOIN schedules s ON r.routes_id = s.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    WHERE b.operators_id = ?
    ORDER BY r.origin ASC
");
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$routes = $stmt->get_result();

$pageTitle = "Operator Routes";
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING & CORE STYLES
   ========================================================================== */
* { 
    box-sizing: border-box; 
}
body {
    margin: 0;
    overflow-x: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8fafc;
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
.admin-sidebar a:hover, .admin-sidebar a.active { 
    background: #FF6B1A; 
    color: white; 
}

/* Content Area */
.admin-content { 
    margin-left: 240px; 
    padding: 25px 30px; 
    min-height: calc(100vh - 56px); 
}
.booking-container { 
    max-width: 1250px; 
    margin: 0 auto; 
}

/* Headers & Navigation */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    background: #f1f5f9;
    color: #475569;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 20px;
    border: 1px solid #cbd5e1;
    transition: all 0.2s;
}
.back-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.section-title { 
    font-size: 26px; 
    font-weight: 800; 
    color: #0A4DA6; 
    margin: 0 0 4px 0; 
}
.subtitle { 
    color: #64748b; 
    font-size: 15px;
    margin-bottom: 25px; 
}

/* ==========================================================================
   ROUTE CARDS CONTAINER & GRID
   ========================================================================== */
.route-container {
    background: white; 
    padding: 25px; 
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
    border: 1px solid #e2e8f0;
}
.route-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

/* Route Card Style */
.route-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 24px;
    border-radius: 12px;
    text-decoration: none;
    color: #1e293b;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.route-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(10, 77, 166, 0.08);
    border-color: #0A4DA6;
    background: white;
}
.route-icon {
    font-size: 32px;
    margin-bottom: 15px;
}
.route-name {
    font-size: 16px;
    font-weight: 700;
    color: #0A4DA6;
    line-height: 1.4;
    margin-bottom: 18px;
}
.arrow {
    color: #FF6B1A;
    font-weight: bold;
    margin: 0 4px;
}

/* Action Card Button */
.route-button {
    align-self: flex-start;
    background: #0A4DA6;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    transition: background-color 0.2s;
}
.route-card:hover .route-button {
    background: #FF6B1A;
}

/* Empty Box State */
.empty-box {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px 20px;
    color: #64748b;
    font-size: 15px;
}
.empty-box-icon {
    font-size: 40px;
    margin-bottom: 10px;
}

/* ==========================================================================
   RESPONSIVE DESIGN
   ========================================================================== */
@media(max-width: 900px){
    .admin-sidebar { position: static; width: 100%; height: auto; }
    .admin-content { margin-left: 0; padding: 15px; }
}
@media(max-width: 576px){
    .route-grid { grid-template-columns: 1fr; }
    .section-title { font-size: 22px; }
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
        
        <!-- BACK BUTTON -->
        <a href="manage_bookings.php" class="back-btn">
            ← Back to Operators
        </a>

        <!-- HEADER -->
        <h2 class="section-title">
            <?= htmlspecialchars($operator['operators_name']); ?>
        </h2>
        <div class="subtitle">
            Routes operated by this operator
        </div>

        <!-- ROUTES GRID SECTION -->
        <div class="route-container">
            <div class="route-grid">
                <?php if($routes->num_rows > 0): ?>
                    <?php while($r = $routes->fetch_assoc()): ?>
                        <a href="manage_passengers.php?route_id=<?= $r['routes_id']; ?>" class="route-card">
                            <div>
                                <div class="route-icon">🚌</div>
                                <div class="route-name">
                                    <?= htmlspecialchars($r['origin']); ?> 
                                    <span class="arrow">➔</span> 
                                    <?= htmlspecialchars($r['destination']); ?>
                                </div>
                            </div>
                            <div class="route-button">
                                View Passengers →
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-box">
                        <div class="empty-box-icon">🗺️</div>
                        <p>No active routes found for this operator.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>