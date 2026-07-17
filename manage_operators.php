<?php
$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';
requireAdmin();


/* =========================
   ADD OPERATOR
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {

    $name = clean($conn, $_POST['operator_name']);
    $contact = clean($conn, $_POST['contact_number']);
    $frequency = clean($conn, $_POST['trip_frequency']);

    $stmt = $conn->prepare("
     INSERT INTO operators
    (operators_name, contact_number, trip_frequency)
    VALUES (?,?,?)
    ");

$stmt->bind_param(
"sss",
$name,
$contact,
$frequency
);

    $stmt->execute();
    $operator_id = $conn->insert_id;


    /* ADD ROUTES */

    if(isset($_POST['routes'])){
        foreach($_POST['routes'] as $route){
            $stmt2 = $conn->prepare("
                INSERT INTO operator_routes
                (operators_id, routes_id)
                VALUES (?,?)
            ");

            $stmt2->bind_param(
                "ii",
                $operator_id,
                $route
            );

            $stmt2->execute();
        }

    }

    header("Location: manage_operators.php");
    exit;

}

/* =========================
   DELETE
========================= */

if(isset($_GET['delete'])){


    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM operators
        WHERE operators_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();

    header("Location: manage_operators.php");
    exit;
}

/* =========================
   GET ROUTES
========================= */

$routeList = $conn->query("
    SELECT *
    FROM routes
    ORDER BY origin ASC
");

/* =========================
   GET OPERATORS
========================= */

$operators = $conn->query("

SELECT
o.*,
COUNT(oroutes.routes_id) AS total_routes,
GROUP_CONCAT(
    CONCAT(r.origin,' → ',r.destination)
    SEPARATOR '<br>'
) AS route_list
FROM operators o
LEFT JOIN operator_routes oroutes
ON o.operators_id = oroutes.operators_id
LEFT JOIN routes r
ON oroutes.routes_id = r.routes_id
GROUP BY o.operators_id
ORDER BY o.operators_id DESC
");

$pageTitle='Manage Operators';
include 'header.php';
?>

<!-- SIDEBAR DIKEMBALIKAN SEMULA -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php" class="active">Operators</a>
    <a href="manage_buses.php">Buses</a>
    <a href="manage_routes.php">Routes</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<!-- CONTENT -->
<div class="admin-content">

<h2 class="section-title">Bus Operators</h2>

<div class="operator-card">
<form method="POST">

<div class="form-grid">
    <input type="text" name="operator_name" placeholder="Operator Name" required>
    <input type="text" name="contact_number" placeholder="Contact Number">
    <input type="text" name="trip_frequency" placeholder="Trip Frequency (e.g. 5 times/day)" required>
</div>

<h4 style="font-size: 13px; font-weight: 700; color: #0A4DA6; margin: 15px 0 8px 0;">Select Route Covered</h4>

<input type="text" id="routeSearch" placeholder="Cari laluan..." class="route-search">

<div class="route-box" id="routeBox">
<?php while($r=$routeList->fetch_assoc()): ?>
<label class="route-item">
    <input type="checkbox" name="routes[]" value="<?php echo $r['routes_id']; ?>">
    <?php
    $origin = explode(',', $r['origin'])[0];
    $destination = explode(',', $r['destination'])[0];
    ?>
    <div class="route-text">
        <strong><?= htmlspecialchars($origin); ?></strong>
        <span style="color:#888; margin: 0 4px;">→</span>
        <strong><?= htmlspecialchars($destination); ?></strong>
    </div>
</label>
<?php endwhile; ?>
</div>

<div style="margin-top: 15px;">
    <button class="btn-orange" name="add">Add Operator</button>
</div>

</form>
</div>

<div class="table-wrapper">
<table class="data-table">
<thead>
    <tr>
        <th style="width: 60px; text-align: center;">ID</th>
        <th>Operator</th>
        <th>Frequency</th>
        <th>Routes</th>
        <th style="width: 180px; text-align: center;">Action</th>
    </tr>
</thead>
<tbody>
<?php while($o=$operators->fetch_assoc()): ?>
    <tr>
        <td style="text-align: center; font-weight: bold; color: #0A4DA6;"><?= $o['operators_id']; ?></td>
        <td style="font-weight: 600;"><?= htmlspecialchars($o['operators_name']); ?></td>
        <td style="color: #E0550A; font-weight: 600;"><?= htmlspecialchars($o['trip_frequency']); ?></td>
        <td style="font-size: 12px; line-height: 1.5; color: #4b5563;"><?= $o['route_list'] ?: '<span style="color:#aaa; font-style: italic;">No Route</span>'; ?></td>
        <td style="text-align: center;">
            <button class="detail-btn" onclick='showDetail(
                <?= json_encode($o["operators_name"]); ?>,
                <?= json_encode($o["contact_number"]); ?>,
                <?= json_encode($o["trip_frequency"]); ?>,
                <?= json_encode($o["route_list"]); ?>
            )'>View</button>
            <a class="delete" href="?delete=<?= $o['operators_id']; ?>" onclick="return confirm('Delete operator?')">Delete</a>
        </td>
    </tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- POPUP DETAIL -->
<div id="detailModal" class="modal">
<div class="modal-box">
    <span class="close" onclick="closeDetail()">&times;</span>
    <h3 style="margin-top: 0; font-size: 16px; color: #0A4DA6; border-bottom: 1px solid #eee; padding-bottom: 8px;">Operator Detail</h3>
    
    <div style="font-size: 13px; line-height: 1.6; margin-top: 10px;">
        <p style="margin: 4px 0;"><b>Operator:</b> <span id="d_name"></span></p>
        <p style="margin: 4px 0;"><b>Contact Number:</b> <span id="d_contact"></span></p>
        <p style="margin: 4px 0;"><b>Trip Frequency:</b> <span id="d_frequency"></span></p>
        <p style="margin: 10px 0 4px 0;"><b>Route Covered:</b></p>
        <div id="d_route" style="background: #f9fafb; padding: 10px; border-radius: 6px; border: 1px solid #eaecf0; font-size: 12px; max-height: 150px; overflow-y: auto;"></div>
    </div>
</div>
</div>

<script>
function showDetail(name, contact, frequency, route){
    document.getElementById("detailModal").style.display="flex";
    document.getElementById("d_name").innerHTML=name;
    document.getElementById("d_contact").innerHTML=contact ? contact : '-';
    document.getElementById("d_frequency").innerHTML=frequency;
    document.getElementById("d_route").innerHTML=route ? route : '<span style="color:#aaa;">No Route</span>';
}

function closeDetail(){
    document.getElementById("detailModal").style.display="none";
}

/* ==========================
   SEARCH ROUTE
========================== */
document.getElementById("routeSearch").addEventListener("keyup", function(){
    let search = this.value.toLowerCase();
    let routes = document.querySelectorAll(".route-item");

    routes.forEach(function(route){
        let text = route.innerText.toLowerCase();
        if(text.includes(search)){
            route.style.display="flex";
        }else{
            route.style.display="none";
        }
    });
});
</script>

<style>
/* ==========================================================================
   GLOBAL SYSTEM COMPACT RESET
   ========================================================================== */
* {
    box-sizing: border-box;
}

/* ==========================================================================
   SIDEBAR ASAL - MERENTASI FLASHBUS (TIDAK TERPOTONG)
   ========================================================================== */
.admin-sidebar {
    position: fixed;
    top: 56px; /* Diturunkan dari navigasi utama FlashBus */
    left: 0;
    width: 240px; /* Saiz standard lebar sidebar */
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

/* ==========================================================================
   ADMIN CONTENT (DISERAGAMKAN KEDUDUKAN)
   ========================================================================== */
.admin-content {
    margin-left: 240px; /* Ditolak ke kanan mengikut saiz lebar sidebar */
    width: calc(100% - 240px);
    min-height: calc(100vh - 56px);
    padding: 25px 30px;
    background-color: #f8fafc;
}

.section-title {
    font-size: 26px; /* Diselaraskan saiz tajuk utama */
    font-weight: 700;
    color: #0A4DA6;
    margin: 0 0 5px 0;
}

/* ==========================================================================
   OPERATOR FORM CARD
   ========================================================================== */
.operator-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.form-grid input {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
}

.form-grid input:focus, .route-search:focus {
    outline: none;
    border-color: #0A4DA6;
    box-shadow: 0 0 0 2px rgba(10, 77, 166, 0.1);
}

.route-search {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    margin-bottom: 15px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
}

/* ==========================================================================
   ROUTE SELECTION BOX
   ========================================================================== */
.route-box {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    max-height: 220px;
    overflow-y: auto;
    padding-right: 4px;
}

.route-box::-webkit-scrollbar { width: 6px; }
.route-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.route-box label {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    cursor: pointer;
    font-size: 13px;
}

.route-box label:hover {
    background: #fff7ed;
    border-color: #FF6B1A;
}

.route-box input[type="checkbox"] {
    margin: 0;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.route-text strong {
    color: #334155;
    font-weight: 600;
}

/* ==========================================================================
   BUTTON ACTIONS
   ========================================================================== */
.btn-orange {
    background: #FF6B1A;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-orange:hover { background: #e05600; }

.detail-btn {
    background: #0A4DA6;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    cursor: pointer;
}

.delete {
    background: #fee2e2;
    color: #dc2626 !important;
    padding: 6px 12px;
    border-radius: 6px;
    margin-left: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    display: inline-block;
}

.delete:hover {
    background: #dc2626;
    color: white !important;
}

/* ==========================================================================
   COMPACT DATA TABLE
   ========================================================================== */
.table-wrapper {
    background: white;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8fafc;
    color: #475569;
    padding: 14px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    border-bottom: 2px solid #e2e8f0;
}

.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #edf2f7;
    color: #334155;
    font-size: 14px;
    vertical-align: middle;
}

.data-table tr:hover { background: #f8fafc; }

/* ==========================================================================
   MODAL WINDOW STYLING
   ========================================================================== */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(2px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-box {
    width: 420px;
    background: white;
    border-radius: 12px;
    padding: 24px;
    position: relative;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.close {
    position: absolute;
    top: 14px;
    right: 18px;
    font-size: 22px;
    cursor: pointer;
    color: #94a3b8;
}
.close:hover { color: #334155; }

/* ==========================================================================
   MEDIA RESPONSIVE
   ========================================================================== */
@media(max-width: 1200px) {
    .route-box { grid-template-columns: repeat(3, 1fr); }
}
@media(max-width: 992px) {
    .admin-sidebar { position: static; width: 100%; height: auto; }
    .admin-sidebar a { display: inline-block; padding: 10px 15px; }
    .admin-content { margin-left: 0; width: 100%; padding: 15px; }
    .route-box { grid-template-columns: repeat(2, 1fr); }
    .form-grid { grid-template-columns: 1fr 1fr; }
}
@media(max-width: 768px) {
    .form-grid, .route-box { grid-template-columns: 1fr; }
}
</style>

<?php include 'footer.php'; ?>