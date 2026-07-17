<?php
if (!isset($conn)) { 
    require_once __DIR__ . '/db.php'; 
}

require_once __DIR__ . '/functions.php';

/* =================================
   BASE PATH
   =================================
   Semua file berada dalam folder sama (user + admin)
*/
$base = '';
$unread = 0;

if (isLoggedIn()) {
    $unread = unreadNotificationCount($conn, $_SESSION['users_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - FlashBus' : 'FlashBus'; ?></title>
    <link rel="stylesheet" href="<?php echo $base; ?>style.css?v=123">
</head>
<body>

<nav class="navbar">
    <!-- LOGO -->
    <a href="<?php echo $base; ?>index_fb.php" class="logo">
        🚌 Flash<span>Bus</span>
    </a>

    <div class="nav-links">
        <!-- SEARCH BUS (SEMUA BOLEH LIHAT) -->
        <a href="<?php echo $base; ?>index_fb.php">Search Bus</a>

        <?php if (isLoggedIn() && !isAdmin()): ?>
            <!-- CUSTOMER MENU -->
            <a href="<?php echo $base; ?>my_bookings.php">My Bookings</a>
            <a href="<?php echo $base; ?>notifications.php" class="notif-bell">
                🔔
                <?php if ($unread > 0): ?>
                    <span class="notif-count"><?php echo $unread; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $base; ?>logout.php" class="btn-cta">Logout</a>

        <?php elseif (isAdmin()): ?>
            <!-- ADMIN MENU -->
            <a href="<?php echo $base; ?>dashboard_fb.php">Admin Panel</a>
            <a href="<?php echo $base; ?>logout.php" class="btn-cta">Logout</a>

        <?php else: ?>
            <!-- GUEST MENU -->
            <a href="<?php echo $base; ?>login.php">Login</a>
            <a href="<?php echo $base; ?>register.php" class="btn-cta">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>