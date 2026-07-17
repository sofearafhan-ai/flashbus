<?php
session_start();
$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* ==========================================================================
   UPDATE BUS STATUS
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $buses_id = (int)$_POST['buses_id'];
    $status = clean($conn, $_POST['status']);

    $stmt = $conn->prepare("UPDATE buses SET status = ? WHERE buses_id = ?");
    $stmt->bind_param("si", $status, $buses_id);
    $stmt->execute();

    if ($status !== "Available") {
        $release = $conn->prepare("UPDATE operator_routes SET buses_id = NULL WHERE buses_id = ?");
        $release->bind_param("i", $buses_id);
        $release->execute();
        autoAssignBusToRoutes($conn);
    }

    $_SESSION['success'] = "Bus status successfully updated.";
    header("Location: manage_buses.php");
    exit();
}

/* ==========================================================================
   ADD BUS (With Safe Transactions)
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $operators_id = (int)$_POST['operators_id'];
    $plate_number = clean($conn, $_POST['plate_number']);
    $total_seats = (int)$_POST['total_seats'];

    $check = $conn->prepare("SELECT buses_id FROM buses WHERE plate_number = ?");
    $check->bind_param("s", $plate_number);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "⚠️ Plate number already exists.";
        header("Location: manage_buses.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        $result = $conn->query("SHOW TABLE STATUS LIKE 'buses'");
        $row = $result->fetch_assoc();
        $next_id = $row['Auto_increment'] ?? 1;
        $bus_code = "FB-B" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO buses (bus_code, operators_id, plate_number, total_seats, status) VALUES (?, ?, ?, ?, 'Available')");
        $stmt->bind_param("sisi", $bus_code, $operators_id, $plate_number, $total_seats);
        $stmt->execute();
        $bus_id = $stmt->insert_id;

        $seatStmt = $conn->prepare("INSERT INTO seats (buses_id, seat_number) VALUES (?, ?)");
        for ($i = 1; $i <= $total_seats; $i++) {
            $seat_number = "S" . $i;
            $seatStmt->bind_param("is", $bus_id, $seat_number);
            $seatStmt->execute();
        }

        $assign = $conn->prepare("SELECT operator_routes_id FROM operator_routes WHERE operators_id = ? AND buses_id IS NULL ORDER BY operator_routes_id ASC LIMIT 1");
        $assign->bind_param("i", $operators_id);
        $assign->execute();
        $assignResult = $assign->get_result();

        if ($assignResult->num_rows > 0) {
            $slot = $assignResult->fetch_assoc();
            $fill = $conn->prepare("UPDATE operator_routes SET buses_id = ? WHERE operator_routes_id = ?");
            $fill->bind_param("ii", $bus_id, $slot['operator_routes_id']);
            $fill->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "✅ Bus $bus_code successfully added.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Unable to add bus. System changes reverted.";
    }
    header("Location: manage_buses.php");
    exit();
}

/* ==========================================================================
   DELETE BUS
   ========================================================================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $conn->begin_transaction();
    try {
        $release = $conn->prepare("UPDATE operator_routes SET buses_id = NULL WHERE buses_id = ?");
        $release->bind_param("i", $id);
        $release->execute();

        $seatDelete = $conn->prepare("DELETE FROM seats WHERE buses_id = ?");
        $seatDelete->bind_param("i", $id);
        $seatDelete->execute();

        $delete = $conn->prepare("DELETE FROM buses WHERE buses_id = ?");
        $delete->bind_param("i", $id);
        $delete->execute();

        $conn->commit();
        $_SESSION['success'] = "✅ Bus successfully deleted.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Error occurred while deleting the bus.";
    }
    header("Location: manage_buses.php");
    exit();
}

/* ==========================================================================
   DATA FETCHING
   ========================================================================== */
$buses = $conn->query("
    SELECT b.buses_id, b.bus_code, b.plate_number, b.total_seats, b.status, o.operators_name
    FROM buses b
    JOIN operators o ON b.operators_id = o.operators_id
    ORDER BY b.buses_id DESC
");
$operators = $conn->query("SELECT * FROM operators");

function autoAssignBusToRoutes($conn) {
    $emptySlots = $conn->query("SELECT operator_routes_id, operators_id FROM operator_routes WHERE buses_id IS NULL");
    while ($slot = $emptySlots->fetch_assoc()) {
        $findBus = $conn->prepare("
            SELECT b.buses_id FROM buses b
            WHERE b.operators_id = ? AND b.status = 'Available'
              AND b.buses_id NOT IN (SELECT buses_id FROM operator_routes WHERE buses_id IS NOT NULL)
            LIMIT 1
        ");
        $findBus->bind_param("i", $slot['operators_id']);
        $findBus->execute();
        $busResult = $findBus->get_result();

        if ($busResult->num_rows > 0) {
            $bus = $busResult->fetch_assoc();
            $fill = $conn->prepare("UPDATE operator_routes SET buses_id = ? WHERE operator_routes_id = ?");
            $fill->bind_param("ii", $bus['buses_id'], $slot['operator_routes_id']);
            $fill->execute();
        }
    }
}

$pageTitle = "Manage Buses";
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING (SIDEBAR + CONTENT WRAPPER)
   ========================================================================== */
* {
    box-sizing: border-box;
}

/* Sidebar Asal - Menggunakan Style Khas FlashBus */
.admin-sidebar {
    position: fixed;
    top: 56px; /* Turun sikit dari top navigation bar FlashBus */
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
}

/* Content Area - Ditolak ke kanan supaya tak bertindih */
.admin-content {
    margin-left: 240px; 
    padding: 25px 30px;
    background-color: #f8fafc;
    min-height: calc(100vh - 56px);
}
.bus-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* ==========================================================================
   ELEMENT CONTROLS (FORM & JADUAL)
   ========================================================================== */
.section-header {
    margin-bottom: 25px;
}
.section-title {
    font-size: 26px;
    font-weight: 700;
    color: #0A4DA6;
    margin: 0 0 5px 0;
}

/* NOTIFICATIONS / ALERTS */
.alert {
    padding: 14px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: 500;
    font-size: 14px;
    border-left: 4px solid transparent;
}
.alert-success {
    background: #ecfdf5;
    color: #065f46;
    border-left-color: #10b981;
}
.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border-left-color: #ef4444;
}

/* FORM REGISTER CARD */
.bus-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}
.form-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #4a5568;
}
.bus-form-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1.2fr 1fr;
    gap: 15px;
    align-items: center;
}
.bus-card input,
.bus-card select {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
}
.btn-add {
    background: #FF6B1A;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    height: 100%;
}
.btn-add:hover { background: #e0560f; }

/* DATA TABLE FIXES */
.table-responsive {
    overflow-x: auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 14px;
}
.data-table th {
    background: #f8fafc;
    color: #475569;
    padding: 16px 20px;
    font-weight: 600;
    border-bottom: 2px solid #edf2f7;
}
.data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

/* FIX CODE BADGE TERPOTONG */
.bus-code {
    background: rgba(10, 77, 166, 0.08);
    color: #0A4DA6;
    padding: 6px 14px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    display: inline-block;
    white-space: nowrap; 
}

.status-badge {
    display: inline-flex;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-available { background: #dcfce7; color: #15803d; }
.status-maintenance { background: #fef3c7; color: #b45309; }
.status-inactive { background: #f1f5f9; color: #475569; }

/* ACTION CONTROL BOX */
.action-box {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-form {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
}
.status-select {
    padding: 6px 10px !important;
    font-size: 13px !important;
    width: 140px;
    border-radius: 6px !important;
}
.btn-save-status {
    padding: 7px 12px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-action {
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
}
.btn-seat { background: #0A4DA6; color: white; }
.btn-delete { background: #ef4444; color: white; }

/* ==========================================================================
   RESPONSIVE MEDIA QUERIES
   ========================================================================== */
@media(max-width: 992px) {
    .admin-sidebar { position: static; width: 100%; height: auto; }
    .admin-sidebar a { display: inline-block; padding: 10px 15px; }
    .admin-content { margin-left: 0; padding: 15px; }
    .bus-form-grid { grid-template-columns: 1fr; }
}
</style>

<!-- SIDEBAR DIKEMBALIKAN SEMULA -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php" class="active">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<!-- CONTAINER UTAMA -->
<div class="admin-content">
    <div class="bus-container">
        <div class="section-header">
            <h1 class="section-title">Bus Management</h1>
            <small style="color: #64748b;">Manage fleet data and registration operations.</small>
        </div>

        <!-- NOTIFICATIONS -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- REGISTER BUS FORM -->
        <div class="bus-card">
            <div class="form-title">Register New Fleet Unit</div>
            <form method="POST">
                <div class="bus-form-grid">
                    <div>
                        <select name="operators_id" required>
                            <option value="">Select Company Operator</option>
                            <?php while($o=$operators->fetch_assoc()): ?>
                                <option value="<?= $o['operators_id']; ?>">
                                    <?= htmlspecialchars($o['operators_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <input type="text" name="plate_number" placeholder="e.g. WQY 8824" required>
                    </div>
                    <div>
                        <input type="number" name="total_seats" placeholder="Seats (1-60)" min="1" max="60" required>
                    </div>
                    <div>
                        <button class="btn-add" name="add" type="submit">+ Add Bus</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RE-DESIGNED LIVE DATA TABLE -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bus Code</th>
                        <th>Operator Profile</th>
                        <th>Plate Number</th>
                        <th>Capacity</th>
                        <th>Live Status</th>
                        <th>Control Panel Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($b=$buses->fetch_assoc()): ?>
                        <tr>
                            <td><span class="bus-code"><?= htmlspecialchars($b['bus_code']); ?></span></td>
                            <td><strong><?= htmlspecialchars($b['operators_name']); ?></strong></td>
                            <td><span style="font-family: monospace; font-size: 15px; font-weight: 600;"><?= htmlspecialchars($b['plate_number']); ?></span></td>
                            <td><span class="seat-total"><?= $b['total_seats']; ?> Max Seats</span></td>
                            <td>
                                <?php if($b['status']=="Available"): ?>
                                    <span class="status-badge status-available">Available</span>
                                <?php elseif($b['status']=="Under Maintenance"): ?>
                                    <span class="status-badge status-maintenance">Maintenance</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-box">
                                    <a href="seat_layout.php?buses_id=<?= $b['buses_id']; ?>" class="btn-action btn-seat">View Seats</a>
                                    
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="buses_id" value="<?= $b['buses_id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="Available" <?= $b['status']=="Available"?"selected":"";?>>Available</option>
                                            <option value="Under Maintenance" <?= $b['status']=="Under Maintenance"?"selected":"";?>>Maintenance</option>
                                            <option value="Inactive" <?= $b['status']=="Inactive"?"selected":"";?>>Inactive</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-save-status">Save</button>
                                    </form>

                                    <a href="?delete=<?= $b['buses_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Confirm deletion of this fleet unit?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>