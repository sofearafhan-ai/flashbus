<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =====================================================
   ASSIGN BUS TO ROUTE
===================================================== */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])){

    $routes_id = (int)$_POST['routes_id'];
    $buses_id = (int)$_POST['buses_id'];

    if($routes_id > 0 && $buses_id > 0){

        $stmt = $conn->prepare("
            INSERT INTO route_buses (routes_id, buses_id) 
            VALUES (?, ?)
        ");

        if(!$stmt){
            die("Prepare Error : ".$conn->error);
        }

        $stmt->bind_param("ii", $routes_id, $buses_id);

        if(!$stmt->execute()){
            die("Insert Error : ".$stmt->error);
        }
    }

    header("Location: manage_route_buses.php");
    exit;
}

/* =====================================================
   DELETE ASSIGNMENT
===================================================== */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM route_buses 
        WHERE route_buses_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manage_route_buses.php");
    exit;
}

/* =====================================================
   GET DATA QUERIES
===================================================== */
// Ambil senarai laluan
$routes = $conn->query("
    SELECT * 
    FROM routes 
    ORDER BY origin ASC
");

// Ambil senarai bas
$buses = $conn->query("
    SELECT b.buses_id, b.plate_number, o.operators_name
    FROM buses b
    INNER JOIN operators o ON b.operators_id = o.operators_id
    ORDER BY o.operators_name ASC
");

// Ambil senarai pemetaan (assignment) bas ke laluan
$route_buses = $conn->query("
    SELECT rb.route_buses_id, r.origin, r.destination, b.plate_number, o.operators_name
    FROM route_buses rb
    INNER JOIN routes r ON rb.routes_id = r.routes_id
    INNER JOIN buses b ON rb.buses_id = b.buses_id
    INNER JOIN operators o ON b.operators_id = o.operators_id
    ORDER BY rb.route_buses_id DESC
");

$pageTitle = "Manage Route Buses";
include 'header.php';
?>

<!-- SELECT2 DEPENDENCIES -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

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
.admin-sidebar a:hover, .admin-sidebar a.active { 
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
.container-box { 
    max-width: 1250px; 
    margin: 0 auto; 
}
.section-title { 
    font-size: 24px; 
    font-weight: 700; 
    color: #0A4DA6; 
    margin-bottom: 20px; 
}

/* ==========================================================================
   FORM DESIGN (GRID SYSTEM)
   ========================================================================== */
.route-form-card {
    background: white; 
    padding: 25px; 
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
    border: 1px solid #e2e8f0;
    margin-bottom: 30px;
}
.route-form {
    display: grid; 
    grid-template-columns: repeat(12, 1fr); 
    gap: 15px; 
    align-items: end;
}
.form-group { 
    display: flex; 
    flex-direction: column; 
}
.form-group.col-5 { grid-column: span 5; }
.form-group.col-2 { grid-column: span 2; }

.form-group label { 
    margin-bottom: 6px; 
    font-weight: 600; 
    font-size: 13px; 
    color: #475569; 
}
.route-form select {
    width: 100%; 
    height: 40px; 
    padding: 8px 12px;
    border: 1px solid #cbd5e1; 
    border-radius: 6px; 
    font-size: 14px; 
    outline: none;
}

/* Button Assign */
.btn-orange {
    background: #FF6B1A; 
    color: white; 
    border: none; 
    height: 40px;
    border-radius: 6px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 14px; 
    transition: background 0.2s;
    width: 100%;
}
.btn-orange:hover { 
    background: #e05609; 
}

/* ==========================================================================
   SELECT2 CUSTOM STYLING
   ========================================================================== */
.select2-container--default .select2-selection--single {
    height: 40px !important; 
    border: 1px solid #cbd5e1 !important; 
    border-radius: 6px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important; 
    padding-left: 12px !important; 
    font-size: 14px; 
    color: #334155;
}
.select2-container--default .select2-selection--single .select2-selection__arrow { 
    height: 38px !important; 
}

/* ==========================================================================
   DATA TABLE DESIGN
   ========================================================================== */
.table-wrap {
    background: white; 
    border-radius: 10px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0; 
    overflow: hidden;
}
.data-table { 
    width: 100%; 
    border-collapse: collapse; 
    text-align: left; 
    font-size: 14px; 
}
.data-table th { 
    background: #f1f5f9; 
    color: #475569; 
    font-weight: 600; 
    padding: 12px 16px; 
    border-bottom: 1px solid #e2e8f0; 
}
.data-table td { 
    padding: 14px 16px; 
    border-bottom: 1px solid #f1f5f9; 
    color: #334155; 
    vertical-align: middle; 
}
.data-table tr:hover td { 
    background: #f8fafc; 
}

/* Label & Typography Kemasan */
.station-text { 
    font-weight: 600; 
    color: #1e293b; 
}
.arrow-sep { 
    color: #94a3b8; 
    font-size: 12px; 
    margin: 0 6px;
}
.bus-plate { 
    background: #f1f5f9; 
    padding: 3px 8px; 
    border-radius: 4px; 
    font-family: monospace; 
    font-size: 12px; 
    font-weight: 600; 
    color: #0f172a;
    border: 1px solid #e2e8f0;
    display: inline-block;
}

/* Action Button Box */
.btn-delete-box {
    background-color: #ef4444;
    color: white !important;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    display: inline-block;
    transition: background-color 0.2s, transform 0.1s;
    border: none;
    cursor: pointer;
}
.btn-delete-box:hover {
    background-color: #dc2626;
    text-decoration: none;
}
.btn-delete-box:active {
    transform: scale(0.96);
}

/* ==========================================================================
   RESPONSIVE MEDIA QUERIES
   ========================================================================== */
@media(max-width: 992px){ 
    .admin-sidebar { position: static; width: 100%; height: auto; } 
    .admin-content { margin-left: 0; padding: 15px; } 
    .route-form { grid-template-columns: 1fr; }
    .form-group.col-5, .form-group.col-2 { grid-column: span 1; }
}
</style>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div style="padding:20px; font-size:18px; font-weight:bold;">
        🚌 FlashBus Admin
    </div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_route_buses.php" class="active">Route Buses</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<!-- MAIN CONTENT -->
<div class="admin-content">
    <div class="container-box">
        <h2 class="section-title">Manage Route Buses</h2>

        <!-- FORM INPUT CARD -->
        <div class="route-form-card">
            <form method="POST" class="route-form">
                
                <div class="form-group col-5">
                    <label>Assign Route</label>
                    <select name="routes_id" id="routes_select" required>
                        <option value="">Select Route</option>
                        <?php while($r = $routes->fetch_assoc()): ?>
                            <option value="<?= $r['routes_id']; ?>">
                                <?= htmlspecialchars($r['origin']); ?> ➔ <?= htmlspecialchars($r['destination']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group col-5">
                    <label>Assign Bus & Operator</label>
                    <select name="buses_id" id="buses_select" required>
                        <option value="">Select Bus</option>
                        <?php while($b = $buses->fetch_assoc()): ?>
                            <option value="<?= $b['buses_id']; ?>">
                                <?= htmlspecialchars($b['operators_name']); ?> - [<?= htmlspecialchars($b['plate_number']); ?>]
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group col-2">
                    <button type="submit" name="assign" class="btn-orange">Assign Bus</button>
                </div>

            </form>
        </div>

        <!-- JADUAL DATA -->
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Operator Name</th>
                        <th>Bus Plate Number</th>
                        <th width="150" style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($route_buses && $route_buses->num_rows > 0): ?>
                        <?php while($rb = $route_buses->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="station-text"><?= htmlspecialchars($rb['origin']); ?></span>
                                    <span class="arrow-sep">➔</span>
                                    <span class="station-text"><?= htmlspecialchars($rb['destination']); ?></span>
                                </td>
                                <td style="font-weight: 600; color: #0A4DA6;">
                                    <?= htmlspecialchars($rb['operators_name']); ?>
                                </td>
                                <td>
                                    <span class="bus-plate"><?= htmlspecialchars($rb['plate_number']); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="?delete=<?= $rb['route_buses_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this bus assignment?')"
                                       class="btn-delete-box">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">
                                No route assignments found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Membuka fungsi carian dinamik Select2 untuk kedua-dua dropdown
    $('#routes_select').select2({
        placeholder: "Search & Select Route",
        allowClear: true,
        width: '100%'
    });
    
    $('#buses_select').select2({
        placeholder: "Search & Select Bus",
        allowClear: true,
        width: '100%'
    });
});
</script>

<?php include 'footer.php'; ?>