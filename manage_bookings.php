<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =====================================================
   GET DEPARTURE STATION
===================================================== */
$stations = $conn->query("
    SELECT DISTINCT origin FROM routes ORDER BY origin ASC
");

/* =====================================================
   GET ARRIVAL STATION
===================================================== */
$destinations = $conn->query("
    SELECT DISTINCT destination FROM routes ORDER BY destination ASC
");

/* =====================================================
   FILTER VALUE
===================================================== */
$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['date'] ?? '';

if($date != ''){
    $date = date("Y-m-d", strtotime($date));
}

$time = $_GET['time'] ?? '';

/* =====================================================
   GET TIME BASED ON FILTER
===================================================== */
$times=[];

if($origin != '' && $destination != '' && $date != ''){
    $stmt=$conn->prepare("
        SELECT DISTINCT TIME(s.departure_time) AS departure_time
        FROM schedules s
        JOIN routes r ON s.routes_id=r.routes_id
        WHERE r.origin=? AND r.destination=? AND s.travel_date=?
        ORDER BY s.departure_time ASC
    ");
    $stmt->bind_param("sss", $origin, $destination, $date);
    $stmt->execute();
    $result=$stmt->get_result();
    while($row=$result->fetch_assoc()){
        $times[]=$row['departure_time'];
    }
}

$pageTitle="Manage Bookings";
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
.booking-container { 
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
   SEARCH BOX DESIGN
   ========================================================================== */
.search-box {
    background: white; 
    padding: 25px; 
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
    border: 1px solid #e2e8f0;
    margin-bottom: 30px;
}
.form-grid { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 20px; 
}
.form-group { 
    display: flex; 
    flex-direction: column; 
}
.form-group label { 
    margin-bottom: 6px; 
    font-weight: 600; 
    font-size: 13px; 
    color: #475569; 
}
.form-group input, .form-group select {
    width: 100%; 
    height: 40px; 
    padding: 8px 12px;
    border: 1px solid #cbd5e1; 
    border-radius: 6px; 
    font-size: 14px; 
    outline: none;
}
.form-group input:focus, .form-group select:focus { 
    border-color: #0A4DA6; 
}
.form-button-wrap { 
    grid-column: span 2; 
    display: flex; 
    justify-content: flex-end; 
    margin-top: 15px; 
}

/* Button Carian */
.search-btn {
    background: #0A4DA6; 
    color: white; 
    border: none; 
    padding: 10px 24px;
    border-radius: 6px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 14px; 
    transition: background 0.2s;
}
.search-btn:hover { 
    background: #073873; 
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
    table-layout: fixed; 
}
th { 
    background: #f1f5f9; 
    color: #475569; 
    font-weight: 600; 
    padding: 12px 10px; 
    border-bottom: 1px solid #e2e8f0; 
}
td { 
    padding: 14px 10px; 
    border-bottom: 1px solid #f1f5f9; 
    color: #334155; 
    vertical-align: middle; 
    word-wrap: break-word; 
    overflow-wrap: break-word;
}
tr:hover td { 
    background: #f8fafc; 
}

/* Lebar Kolum Diperkemaskan Semula (Total: 100%) */
table th:nth-child(1), table td:nth-child(1) { width: 4%; text-align: center; }  /* No */
table th:nth-child(2), table td:nth-child(2) { width: 14%; } /* Bus & Operator */
table th:nth-child(3), table td:nth-child(3) { width: 30%; } /* Route */
table th:nth-child(4), table td:nth-child(4) { width: 11%; } /* Travel Date */
table th:nth-child(5), table td:nth-child(5) { width: 11%; } /* Time */
table th:nth-child(6), table td:nth-child(6) { width: 8%; }  /* Passenger */
table th:nth-child(7), table td:nth-child(7) { width: 10%; } /* Available Seat */
table th:nth-child(8), table td:nth-child(8) { width: 12%; text-align: center; } /* Action (Dinaikkan ke 12%) */

/* Elemen Teks & Label */
.bus-code {
    background: #f1f5f9; 
    padding: 2px 6px; 
    border-radius: 4px; 
    font-family: monospace; 
    font-size: 11px; 
    font-weight: 600; 
    display: inline-block;
    margin-top: 4px;
}
.station-text { 
    font-weight: 600; 
    color: #1e293b;
    display: block;
    line-height: 1.4;
}
.arrow-sep { 
    color: #94a3b8; 
    font-size: 11px; 
    margin: 2px 0; 
    display: block; 
}
.empty { 
    text-align: center; 
    padding: 40px; 
    color: #64748b; 
    font-weight: 500;
}

/* Badges Status Kekosongan */
.badge-status {
    padding: 4px 10px; 
    border-radius: 20px; 
    font-size: 11px; 
    font-weight: 700;
    display: inline-block;
}
.badge-full {
    background-color: #fee2e2;
    color: #ef4444;
}
.badge-available {
    background-color: #dcfce7;
    color: #15803d;
}

/* Button Tindakan - Teks Bertingkat Tanpa Terpotong */
.view-btn { 
    background: #FF6B1A; 
    color: white !important; 
    padding: 6px 8px; 
    border-radius: 6px; 
    text-decoration: none; 
    font-size: 10.5px; 
    font-weight: 600; 
    display: inline-block;
    text-align: center;
    line-height: 1.3;
    width: 100%;
    max-width: 95px; 
    transition: background-color 0.2s, transform 0.1s;
}
.view-btn:hover { 
    background: #e05609; 
}
.view-btn:active {
    transform: scale(0.96);
}

/* ==========================================================================
   RESPONSIVE MEDIA QUERIES
   ========================================================================== */
@media(max-width: 992px) { 
    .admin-sidebar { position: static; width: 100%; height: auto; } 
    .admin-content { margin-left: 0; padding: 15px; width: 100%; } 
}
@media(max-width: 768px) { 
    .form-grid { grid-template-columns: 1fr; } 
    .form-button-wrap { grid-column: span 1; } 
    table { table-layout: auto; } 
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

<!-- CONTENT -->
<div class="admin-content">
    <div class="booking-container">
        <div class="section-title">Manage Bookings</div>

        <!-- SEARCH FORM -->
        <div class="search-box">
            <form method="GET">
                <div class="form-grid">
                    <!-- DEPARTURE STATION -->
                    <div class="form-group">
                        <label>Departure Station</label>
                        <select name="origin" id="origin" required>
                            <option value="">Select Departure Station</option>
                            <?php while($s=$stations->fetch_assoc()){ ?>
                                <option value="<?php echo htmlspecialchars($s['origin']); ?>" <?php if($origin == $s['origin']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($s['origin']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- ARRIVAL STATION -->
                    <div class="form-group">
                        <label>Arrival Station</label>
                        <select name="destination" id="destination" required>
                            <option value="">Select Arrival Station</option>
                            <?php while($d=$destinations->fetch_assoc()){ ?>
                                <option value="<?php echo htmlspecialchars($d['destination']); ?>" <?php if($destination == $d['destination']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($d['destination']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- DATE -->
                    <div class="form-group">
                        <label>Departure Date</label>
                        <input type="date" name="date" value="<?php echo $date; ?>" required onchange="this.form.submit()">
                    </div>

                    <!-- TIME -->
                    <div class="form-group">
                        <label>Departure Time</label>
                        <select name="time" required>
                            <option value="">Select Time</option>
                            <?php foreach($times as $t){ ?>
                                <option value="<?php echo $t; ?>" <?php if($time==$t) echo "selected"; ?>>
                                    <?php echo date("h:i A", strtotime($t)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-button-wrap">
                    <button class="search-btn" type="submit">Search Bus</button>
                </div>
            </form>
        </div>

        <?php
        /* =====================================================
           GET TRIP LIST
        ===================================================== */
        if($origin != '' && $destination != '' && $date != '' && $time != ''){
            $stmt=$conn->prepare("
                SELECT
                    s.schedules_id,
                    r.origin,
                    r.destination,
                    s.travel_date,
                    s.departure_time,
                    s.arrival_time,
                    b.bus_code,
                    b.total_seats,
                    o.operators_name,
                    COUNT(DISTINCT p.seats_id) AS total_passengers
                FROM schedules s
                JOIN routes r ON s.routes_id=r.routes_id
                JOIN buses b ON s.buses_id=b.buses_id
                JOIN operators o ON b.operators_id=o.operators_id
                LEFT JOIN bookings bk ON s.schedules_id=bk.schedules_id AND bk.booking_status='Confirmed'
                LEFT JOIN passengers p ON bk.bookings_id=p.bookings_id
                WHERE r.origin=? AND r.destination=? AND s.travel_date=? AND s.departure_time=?
                GROUP BY s.schedules_id, b.buses_id
                ORDER BY s.departure_time ASC
            ");

            $stmt->bind_param("ssss", $origin, $destination, $date, $time);
            $stmt->execute();
            $trips=$stmt->get_result();
        } else {
            $trips=null;
        }
        ?>

        <!-- TRIP TABLE -->
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Bus & Operator</th>
                        <th>Route</th>
                        <th>Travel Date</th>
                        <th>Time</th>
                        <th>Passenger</th>
                        <th>Available Seat</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($trips && $trips->num_rows>0){ ?>
                        <?php
                        $no=1;
                        while($trip=$trips->fetch_assoc()){
                            $available = $trip['total_seats'] - $trip['total_passengers'];
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($trip['operators_name']); ?></div>
                                <span class="bus-code"><?php echo htmlspecialchars($trip['bus_code']); ?></span>
                            </td>
                            <td>
                                <span class="station-text"><?php echo htmlspecialchars($trip['origin']); ?></span>
                                <span class="arrow-sep">➔</span>
                                <span class="station-text"><?php echo htmlspecialchars($trip['destination']); ?></span>
                            </td>
                            <td><?php echo date("d M Y", strtotime($trip['travel_date'])); ?></td>
                            <td>
                                <span style="font-weight:600; color:#0A4DA6;"><?php echo date("h:i A", strtotime($trip['departure_time'])); ?></span>
                                <span class="arrow-sep">to</span>
                                <span style="color:#64748b;"><?php echo date("h:i A", strtotime($trip['arrival_time'])); ?></span>
                            </td>
                            <td>
                                <strong><?php echo $trip['total_passengers']; ?></strong> <span style="color:#64748b;">/ <?php echo $trip['total_seats']; ?></span>
                            </td>
                            <td>
                                <?php if($available<=0){ ?>
                                    <span class="badge-status badge-full">FULL</span>
                                <?php } else { ?>
                                    <span class="badge-status badge-available"><?php echo $available; ?> Seats</span>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <a class="view-btn" href="manage_passengers.php?schedules_id=<?php echo $trip['schedules_id']; ?>">View Passengers</a>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="empty">
                                <?php
                                if($origin=='' || $destination=='' || $date=='' || $time==''){
                                    echo "💡 Please select all search criteria above to load data.";
                                } else {
                                    echo "🔍 No trip matches the selected filtering parameters.";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#origin").select2({ placeholder: "Search Departure", allowClear: true, width: "100%" });
    $("#destination").select2({ placeholder: "Search Arrival", allowClear: true, width: "100%" });
});
</script>

<?php include 'footer.php'; ?>