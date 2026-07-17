<?php
require_once 'db.php';
require_once 'functions.php';
requireLogin();

// Tanda semua sebagai telah dibaca apabila pengguna melawat halaman ini
$conn->query("UPDATE notifications SET is_read = 1 WHERE users_id = " . (int)$_SESSION['users_id']);

// Dapatkan notifikasi dengan susunan yang terbaru di atas (DESC)
$stmt = $conn->prepare("SELECT * FROM notifications WHERE users_id = ? ORDER BY notifications_id DESC");
$stmt->bind_param('i', $_SESSION['users_id']);
$stmt->execute();
$notifs = $stmt->get_result();

$icons = [
    'Reminder' => '⏰',
    'Delay' => '⚠️',
    'Arrived' => '🚌',
    'Cancelled' => '❌',
    'Platform Info' => '📍'
];

$pageTitle = 'Notifications';
include 'header.php';
?>

<div class="notif-wrapper">
    <div class="notif-header">
        <h2 class="notif-title">Notifications</h2>
        <span class="notif-count"><?= $notifs->num_rows; ?> total</span>
    </div>

    <?php if ($notifs->num_rows === 0): ?>
        <div class="notif-empty card-shadow">
            <div class="empty-icon">🔔</div>
            <h3>All caught up!</h3>
            <p>You don't have any notifications at the moment.</p>
        </div>
    <?php endif; ?>

    <div class="notif-list">
        <?php while ($n = $notifs->fetch_assoc()): 
            // Tukar warna border-left mengikut jenis impak notifikasi
            $borderClass = 'border-info';
            $typeLower = strtolower($n['type']);
            if ($typeLower === 'delay') $borderClass = 'border-warning';
            if ($typeLower === 'cancelled') $borderClass = 'border-danger';
            if ($typeLower === 'arrived') $borderClass = 'border-success';
            if ($typeLower === 'platform info' || $typeLower === 'platform') $borderClass = 'border-primary';
        ?>
            <div class="notif-card card-shadow <?= $borderClass; ?>">
                <div class="notif-icon-box">
                    <?php echo $icons[$n['type']] ?? '🔔'; ?>
                </div>
                
                <div class="notif-body">
                    <div class="notif-type"><?php echo htmlspecialchars($n['type']); ?></div>
                    <div class="notif-message"><?php echo htmlspecialchars($n['message']); ?></div>
                </div>
                
                <div class="notif-time">
                    <?php 
                    if (isset($n['created_at']) && !empty($n['created_at'])) {
                        echo date('d M Y, H:i', strtotime($n['created_at']));
                    } else {
                        echo date('d M Y, H:i'); 
                    }
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
/* ========================================================================
    MODERN NOTIFICATION STYLES
======================================================================== */
.notif-wrapper {
    max-width: 750px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 2px solid #eef2f5;
    padding-bottom: 15px;
}

.notif-title {
    font-size: 26px;
    font-weight: 700;
    color: #0A4DA6;
    margin: 0;
}

.notif-count {
    background: #eef2f5;
    color: #6c757d;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

/* Base Card Styling */
.notif-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    border-left: 5px solid #0A4DA6; /* Default Border */
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.notif-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.08) !important;
}

.card-shadow {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
}

/* Dynamic Border Colors based on Types */
.border-info { border-left-color: #0A4DA6; }
.border-primary { border-left-color: #3b82f6; }
.border-warning { border-left-color: #ffc107; }
.border-danger { border-left-color: #dc3545; }
.border-success { border-left-color: #28a745; }

/* Icon Wrapper */
.notif-icon-box {
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Card Content Body */
.notif-body {
    flex: 1;
    min-width: 0; /* Mengelakkan masalah text-overflow */
}

.notif-type {
    font-weight: 700;
    font-size: 13px;
    color: #64748b;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.notif-message {
    color: #1e293b;
    font-size: 14.5px;
    line-height: 1.5;
    margin: 0;
    word-wrap: break-word;
}

/* Timestamp Styling */
.notif-time {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
    align-self: flex-start;
    margin-top: 3px;
}

/* Empty State View */
.notif-empty {
    background: #ffffff;
    border-radius: 12px;
    padding: 50px 30px;
    text-align: center;
    color: #64748b;
    border: 1px dashed #cbd5e1;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.notif-empty h3 {
    color: #1e293b;
    margin: 0 0 8px 0;
    font-size: 18px;
}

.notif-empty p {
    margin: 0;
    font-size: 14px;
}

/* ========================================================================
    RESPONSIVE DESIGN (MOBILE FOCUS)
======================================================================== */
@media (max-width: 600px) {
    .notif-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
    }
    
    .notif-icon-box {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .notif-time {
        align-self: flex-end;
        margin-top: 5px;
    }
    
    .notif-header {
        margin-bottom: 15px;
    }
}
</style>

<?php include 'footer.php'; ?>