<?php
$inAdmin = true;
require_once 'db.php';
require_once 'functions.php';
requireAdmin();

$totalBookings = $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$totalSchedules = $conn->query("SELECT COUNT(*) c FROM schedules WHERE travel_date >= CURDATE()")->fetch_assoc()['c'];
$revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) r FROM bookings WHERE booking_status='Confirmed'")->fetch_assoc()['r'];

$pageTitle = 'Admin Dashboard';
include 'header.php';
?>
<div class="admin-sidebar">
  <div style="padding:0 24px 16px;font-weight:800;font-size:18px;">🚌 FlashBus Admin</div>
  <a href="dashboard.php" class="active">Dashboard</a>
  <a href="manage_operators.php">Operators</a>
  <a href="manage_buses.php">Buses</a>
  <a href="manage_routes.php">Routes</a>
  <a href="manage_schedules.php">Schedules &amp; Status</a>
  <a href="manage_bookings.php">Bookings</a>
</div>
<div class="admin-content">
  <div class="section-title">Dashboard Overview</div>
  <div class="stat-cards">
    <div class="stat-card"><div class="num"><?php echo $totalBookings; ?></div><div class="label">Total Bookings</div></div>
    <div class="stat-card"><div class="num"><?php echo $totalUsers; ?></div><div class="label">Registered Customers</div></div>
    <div class="stat-card"><div class="num"><?php echo $totalSchedules; ?></div><div class="label">Upcoming Trips</div></div>
    <div class="stat-card"><div class="num">RM <?php echo number_format($revenue, 2); ?></div><div class="label">Total Revenue</div></div>
  </div>

  <div class="section-title">Quick Actions</div>
  <a href="manage_schedules.php" class="btn btn-orange">Manage Schedules &amp; Trip Status</a>
  <a href="manage_routes.php" class="btn btn-blue">Add New Route</a>
</div>
<?php include 'footer.php'; ?>