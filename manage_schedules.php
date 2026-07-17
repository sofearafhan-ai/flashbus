<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =====================================================
   AJAX HANDLER: GET BUSES BY ROUTE
   (Menapis bas berdasarkan Operator yang memegang Route ini)
===================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'get_buses_by_route') {
    header('Content-Type: application/json');
    $routes_id = isset($_GET['routes_id']) ? (int)$_GET['routes_id'] : 0;
    
    // Tarik bas kepunyaan operator yang ditugaskan untuk route ini sahaja
    $stmt = $conn->prepare("
        SELECT b.buses_id, b.plate_number, o.operators_name
        FROM buses b
        INNER JOIN operators o ON b.operators_id = o.operators_id
        INNER JOIN operator_routes or_map ON o.operators_id = or_map.operators_id
        WHERE b.status = 'Available' AND or_map.routes_id = ?
        ORDER BY b.plate_number ASC
    ");
    
    $stmt->bind_param("i", $routes_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $buses_list = [];
    while ($row = $result->fetch_assoc()) {
        $buses_list[] = $row;
    }
    
    echo json_encode($buses_list);
    exit;
}

/* =====================================================
   AJAX HANDLER: GET DESTINATIONS BY ORIGIN
===================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'get_destinations') {
    header('Content-Type: application/json');
    $origin = isset($_GET['origin']) ? trim($_GET['origin']) : '';
    
    $stmt = $conn->prepare("SELECT routes_id, destination FROM routes WHERE origin = ? ORDER BY destination ASC");
    $stmt->bind_param("s", $origin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
    echo json_encode($destinations);
    exit;
}

/* =====================================================
   ADD SCHEDULE
===================================================== */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])){

    $routes_id = (int)$_POST['routes_id'];
    $buses_id = (int)$_POST['buses_id'];
    $travel_date = trim($_POST['travel_date']);
    $departure_time = trim($_POST['departure_time']);
    $arrival_time = trim($_POST['arrival_time']);
    $platform_number = trim($_POST['platform_number']);
    $price = (float)$_POST['price'];

    $check = $conn->prepare("
        SELECT schedules_id
        FROM schedules
        WHERE buses_id = ?
        AND travel_date = ?
        AND status != 'Cancelled'
        AND departure_time < ? 
        AND arrival_time > ?
    ");

    $check->bind_param(
        "isss",
        $buses_id,
        $travel_date,
        $arrival_time,
        $departure_time
    );

    $check->execute();
    $exist = $check->get_result();

    if($exist->num_rows > 0){
        echo "
        <script>
        alert('This bus already has an overlapping schedule on this date/time!');
        window.history.back();
        </script>
        ";
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO schedules (
            routes_id,
            buses_id,
            travel_date,
            departure_time,
            arrival_time,
            platform_number,
            price,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')
    ");

    $stmt->bind_param(
        "iissssd",
        $routes_id,
        $buses_id,
        $travel_date,
        $departure_time,
        $arrival_time,
        $platform_number,
        $price
    );

    $stmt->execute();

    header("Location: manage_schedules.php");
    exit;
}

/* =====================================================
   UPDATE STATUS & PLATFORM (DENGAN EMAIL NOTIFICATION)
===================================================== */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])){

    $id = (int)$_POST['schedules_id'];
    $status = trim($_POST['status']);
    $platform = trim($_POST['platform_number']);

    $sched_query = $conn->prepare("
        SELECT s.*, r.origin, r.destination 
        FROM schedules s
        INNER JOIN routes r ON s.routes_id = r.routes_id
        WHERE s.schedules_id = ?
    ");
    $sched_query->bind_param("i", $id);
    $sched_query->execute();
    $sched_info = $sched_query->get_result()->fetch_assoc();

    if ($sched_info) {
        $old_status = $sched_info['status'];
        $old_platform = $sched_info['platform_number'];
        $route_name = $sched_info['origin'] . " ➔ " . $sched_info['destination'];

        $stmt = $conn->prepare("
            UPDATE schedules
            SET status = ?, platform_number = ?
            WHERE schedules_id = ?
        ");
        $stmt->bind_param("ssi", $status, $platform, $id);
        $stmt->execute();

        // JIKA STATUS ATAU PLATFORM BERUBAH, HANTAR NOTIFIKASI & EMAIL
        if ($old_status !== $status || $old_platform !== $platform) {
            
            $notif_type = 'Platform Info';
            $msg = "Platform for your trip [$route_name] on " . date('d M Y', strtotime($sched_info['travel_date'])) . " has been updated to Platform $platform.";

            if ($old_status !== $status) {
                if ($status === 'Delayed') { 
                    $notif_type = 'Delay';
                    $msg = "Attention: Your trip [$route_name] on " . date('d M Y', strtotime($sched_info['travel_date'])) . " is currently DELAYED.";
                } elseif ($status === 'Cancelled') {
                    $notif_type = 'Cancelled';
                    $msg = "Alert: Your trip [$route_name] on " . date('d M Y', strtotime($sched_info['travel_date'])) . " has been CANCELLED. Please contact counter for your refund.";
                } elseif ($status === 'Arrived') {
                    $notif_type = 'Arrived';
                    $msg = "Your bus for trip [$route_name] has ARRIVED at platform " . ($platform ?: 'N/A') . ". Ready for boarding.";
                } elseif ($status === 'Departed') {
                    $notif_type = 'Reminder';
                    $msg = "Your bus for trip [$route_name] has DEPARTED.";
                } else {
                    $notif_type = 'Reminder';
                    $msg = "Status update for trip [$route_name]: Status changed to $status.";
                }
            }

            // Ambil data penumpang untuk hantar email
            $passenger_query = $conn->prepare("
                SELECT DISTINCT b.bookings_id, u.users_id, u.email 
                FROM bookings b
                INNER JOIN users u ON b.users_id = u.users_id
                WHERE b.schedules_id = ? AND b.booking_status = 'Confirmed'
            ");
            $passenger_query->bind_param("i", $id);
            $passenger_query->execute();
            $passengers = $passenger_query->get_result();

            while ($passenger = $passengers->fetch_assoc()) {
                $bookings_id = $passenger['bookings_id'];
                $user_id = $passenger['users_id'];
                $user_email = $passenger['email'];

                // Elakkan spam duplicate notification
                $check_dup = $conn->prepare("
                    SELECT notifications_id 
                    FROM notifications 
                    WHERE users_id = ? 
                    AND bookings_id = ?
                    AND type = ? 
                    AND message = ? 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ");
                $check_dup->bind_param("iiss", $user_id, $bookings_id, $notif_type, $msg);
                $check_dup->execute();
                $has_dup = $check_dup->get_result();

                if ($has_dup->num_rows == 0) {
                    $insert_notif = $conn->prepare("
                        INSERT INTO notifications (users_id, bookings_id, type, message, is_read, created_at) 
                        VALUES (?, ?, ?, ?, 0, NOW())
                    ");
                    $insert_notif->bind_param("iiss", $user_id, $bookings_id, $notif_type, $msg);
                    $insert_notif->execute();

                    // Fungsi hantar email dipanggil semula di sini
                    sendNotificationEmail($user_email, "FlashBus Update: $notif_type", $msg);
                }
            }
        }
    }
    
    header("Location: manage_schedules.php");
    exit;
}

/* =====================================================
   DELETE SCHEDULE
===================================================== */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM schedules
        WHERE schedules_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manage_schedules.php");
    exit;
}

/* =====================================================
   LOAD DATA QUERIES
===================================================== */
$schedules = $conn->query("
    SELECT s.*, r.origin, r.destination, b.plate_number, o.operators_name
    FROM schedules s
    INNER JOIN routes r ON s.routes_id = r.routes_id
    INNER JOIN buses b ON s.buses_id = b.buses_id
    INNER JOIN operators o ON b.operators_id = o.operators_id
    ORDER BY s.travel_date DESC, s.departure_time ASC
");

$origins = $conn->query("
    SELECT DISTINCT origin
    FROM routes
    ORDER BY origin ASC
");

$pageTitle = "Manage Schedules";
include 'header.php';
?>

<!-- SELECT2 DEPENDENCIES -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<style>
* { box-sizing: border-box; }
body { overflow-x: hidden; background-color: #f8fafc; }
.admin-sidebar { position: fixed; top: 56px; left: 0; width: 240px; height: calc(100vh - 56px); background: #0A4DA6; color: white; z-index: 99; }
.admin-sidebar div { padding: 20px 24px; font-size: 18px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
.admin-sidebar a { display: block; padding: 16px 24px; color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 15px; font-weight: 500; }
.admin-sidebar a:hover, .admin-sidebar a.active { background: #FF6B1A; color: white; }
.admin-content { margin-left: 240px; padding: 25px 30px; min-height: calc(100vh - 56px); }
.schedule-container { max-width: 1250px; margin: 0 auto; background: white; padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
.section-title { font-size: 24px; font-weight: 700; color: #0A4DA6; margin-bottom: 20px; }
.schedule-form { display: grid; grid-template-columns: repeat(12, 1fr); gap: 15px; margin-bottom: 25px; }
.form-group { display: flex; flex-direction: column; }
.form-group.col-6 { grid-column: span 6; }
.form-group.col-4 { grid-column: span 4; }
.form-group.col-3 { grid-column: span 3; }
.form-group.col-2 { grid-column: span 2; }
.form-group label { margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #475569; }
.schedule-form input, .schedule-form select { width: 100%; height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
.form-button-wrap { grid-column: span 12; display: flex; justify-content: flex-end; margin-top: 10px; }
.btn-orange { background: #FF6B1A; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s; }
.btn-orange:hover { background: #e05609; }
.schedule-table-wrap { background: white; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.data-table th { background: #0A4DA6; color: white; font-weight: 600; padding: 14px; }
.data-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.table-inline-form { display: flex; align-items: center; gap: 8px; }
.table-inline-form select, .table-inline-form input { height: 34px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; }
.btn-update-inline { background: #0A4DA6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; height: 34px; }
.btn-update-inline:hover { background: #083d85; }
.bus-plate { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 12px; font-weight: 600; }
.btn-delete-box { background-color: #ef4444; color: white !important; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 6px; display: inline-block; border: none; }
.btn-delete-box:hover { background-color: #dc2626; }
</style>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_schedules.php" class="active">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<!-- MAIN CONTENT -->
<div class="admin-content">
    <div class="schedule-container">
        <h2 class="section-title">Manage Schedules</h2>

        <form method="POST" class="schedule-form" onsubmit="return validateTime()">
            
            <input type="hidden" name="routes_id" id="hidden_routes_id" value="">

            <!-- 1. DROPDOWN DEPARTURE -->
            <div class="form-group col-3">
                <label>Departure (Origin)</label>
                <select id="departure_select" required>
                    <option value="">Select Departure</option>
                    <?php while($o = $origins->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($o['origin']); ?>"><?= htmlspecialchars($o['origin']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- 2. DROPDOWN ARRIVAL -->
            <div class="form-group col-3">
                <label>Arrival (Destination)</label>
                <select id="arrival_select" required disabled>
                    <option value="">Select Arrival</option>
                </select>
            </div>

            <!-- 3. DROPDOWN ASSIGNED BUS -->
            <div class="form-group col-6">
                <label>Assigned Bus</label>
                <select name="buses_id" id="buses_select" required disabled>
                    <option value="">Select Bus (Select Route First)</option>
                </select>
            </div>

            <div class="form-group col-4">
                <label>Travel Date</label>
                <input type="date" name="travel_date" min="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group col-2">
                <label>Departure Time</label>
                <input type="time" id="departure_time" name="departure_time" required>
            </div>

            <div class="form-group col-2">
                <label>Arrival Time</label>
                <input type="time" id="arrival_time" name="arrival_time" required>
            </div>

            <div class="form-group col-2">
                <label>Platform Number</label>
                <input type="text" name="platform_number" placeholder="Platform Number">
            </div>

            <div class="form-group col-2">
                <label>Price (RM)</label>
                <input type="number" step="0.01" name="price" placeholder="0.00" required>
            </div>

            <div class="form-button-wrap">
                <button type="submit" name="add" class="btn-orange">Add Schedule</button>
            </div>
        </form>

        <div class="schedule-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Bus & Operator</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th width="380">Status & Platform Update</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($s = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong style="color: #0A4DA6;"><?= htmlspecialchars($s['origin']); ?></strong> ➔ 
                            <strong><?= htmlspecialchars($s['destination']); ?></strong>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($s['operators_name']); ?></div>
                            <span class="bus-plate"><?= htmlspecialchars($s['plate_number']); ?></span>
                        </td>
                        <td><?= date('d M Y', strtotime($s['travel_date'])); ?></td>
                        <td>
                            <strong><?= date('h:i A', strtotime($s['departure_time'])); ?></strong><br>
                            <span style="color:#64748b; font-size:12px;">to <?= date('h:i A', strtotime($s['arrival_time'])); ?></span>
                        </td>
                        <td>
                            <form method="POST" class="table-inline-form">
                                <input type="hidden" name="schedules_id" value="<?= $s['schedules_id']; ?>">
                                <select name="status" style="width: 120px;">
                                    <?php foreach(['Scheduled', 'Delayed', 'Arrived', 'Departed', 'Cancelled'] as $st): ?>
                                        <option value="<?= $st; ?>" <?= ($s['status'] == $st) ? 'selected' : ''; ?>><?= $st; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="platform_number" style="width: 100px;" value="<?= htmlspecialchars($s['platform_number']); ?>" placeholder="Platform">
                                <button type="submit" name="update_status" class="btn-update-inline">Update</button>
                            </form>
                        </td>
                        <td style="text-align: center;">
                            <a href="?delete=<?= $s['schedules_id']; ?>" onclick="return confirm('Delete schedule?')" class="btn-delete-box">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#departure_select, #arrival_select, #buses_select').select2({ width: '100%' });

    $('#departure_select').on('change', function() {
        let origin = $(this).val();
        
        $('#arrival_select').html('<option value="">Select Arrival</option>').val('').trigger('change').prop('disabled', true);
        $('#buses_select').html('<option value="">Select Bus (Select Route First)</option>').val('').trigger('change').prop('disabled', true);
        $('#hidden_routes_id').val('');

        if (origin) {
            $.ajax({
                url: 'manage_schedules.php',
                type: 'GET',
                data: { action: 'get_destinations', origin: origin },
                success: function(data) {
                    let options = '<option value="">Select Arrival</option>';
                    data.forEach(function(item) {
                        options += `<option value="${item.destination}" data-routeid="${item.routes_id}">${item.destination}</option>`;
                    });
                    $('#arrival_select').html(options).prop('disabled', false).trigger('change');
                }
            });
        }
    });

    $('#arrival_select').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let routesId = selectedOption.data('routeid');

        $('#buses_select').html('<option value="">Select Bus</option>').val('').trigger('change').prop('disabled', true);
        
        if (routesId) {
            $('#hidden_routes_id').val(routesId); 
            
            $.ajax({
                url: 'manage_schedules.php',
                type: 'GET',
                data: { action: 'get_buses_by_route', routes_id: routesId },
                success: function(data) {
                    let options = '<option value="">Select Bus</option>';
                    if(data.length > 0) {
                        data.forEach(function(bus) {
                            options += `<option value="${bus.buses_id}">${bus.operators_name} (${bus.plate_number})</option>`;
                        });
                        $('#buses_select').prop('disabled', false);
                    } else {
                        options = '<option value="">No bus from assigned operator available!</option>';
                    }
                    $('#buses_select').html(options).trigger('change');
                }
            });
        }
    });
});

function validateTime(){
    let departure = document.getElementById("departure_time").value;
    let arrival = document.getElementById("arrival_time").value;
    if(arrival <= departure){
        alert("Arrival time must be later than departure time");
        return false;
    }
    return true;
}
</script>

<?php include 'footer.php'; ?>