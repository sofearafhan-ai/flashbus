<?php
require_once 'db.php';
require_once 'functions.php';

requireLogin();

/* =========================
   TOYYIBPAY RETURN PARAMS
========================= */
$billCode = $_GET['billcode'] ?? '';
$statusId = $_GET['status_id'] ?? '';
$orderId  = $_GET['order_id'] ?? ''; // booking_code

$booking = null;

if(!empty($billCode)){

    // 1. UPDATE DATABASE IKUT STATUS DARI TOYYIBPAY
    if($statusId == 1) {
        // Jika success, update status jadi Confirmed & success
        $updateStmt = $conn->prepare("
            UPDATE bookings b 
            JOIN payment p ON b.bookings_id = p.bookings_id
            SET b.booking_status = 'Confirmed', p.payment_status = 'success'
            WHERE p.toyyibpay_billcode = ?
        ");
        $updateStmt->bind_param("s", $billCode);
        $updateStmt->execute();
    } 
    elseif($statusId == 3) {
        // Jika failed, update status jadi Cancelled & failed
        $updateStmt = $conn->prepare("
            UPDATE bookings b 
            JOIN payment p ON b.bookings_id = p.bookings_id
            SET b.booking_status = 'Cancelled', p.payment_status = 'failed'
            WHERE p.toyyibpay_billcode = ?
        ");
        $updateStmt->bind_param("s", $billCode);
        $updateStmt->execute();
    }

    // 2. AMBIL DATA TERBARU UNTUK DIPAPARKAN
    $stmt = $conn->prepare("
        SELECT b.bookings_id, b.booking_code, b.total_amount, b.booking_status,
               p.payment_status, p.payment_method
        FROM payment p
        JOIN bookings b ON b.bookings_id = p.bookings_id
        WHERE p.toyyibpay_billcode = ?
    ");
    $stmt->bind_param("s", $billCode);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
}

$pageTitle = 'Payment Status';
include 'header.php';
?>

<!-- Gaya CSS Moden & Responsif -->
<style>
    .status-container {
        max-width: 550px;
        margin: 50px auto;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .status-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 40px 30px;
        text-align: center;
        border: 1px solid #f1f1f1;
    }

    /* Animasi Bulatan Ikon */
    .icon-wrapper {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px auto;
        font-size: 40px;
    }

    .success-icon {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .failed-icon {
        background-color: #ffebee;
        color: #c62828;
    }

    .pending-icon {
        background-color: #fff8e1;
        color: #f57f17;
    }

    .status-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .status-message {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    /* Bahagian Butiran Resit */
    .receipt-box {
        background: #f8f9fa;
        border: 1px solid #eef2f5;
        border-radius: 14px;
        padding: 20px;
        text-align: left;
        margin-bottom: 30px;
    }

    .receipt-title {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #888;
        margin-bottom: 15px;
        border-bottom: 1px dashed #ddd;
        padding-bottom: 10px;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
    }

    .receipt-row:last-child {
        margin-bottom: 0;
    }

    .receipt-label {
        color: #6c757d;
    }

    .receipt-value {
        font-weight: 600;
        color: #2b3a4a;
    }

    /* Badges Status */
    .badge {
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-success { background: #e8f5e9; color: #2e7d32; }
    .badge-failed { background: #ffebee; color: #c62828; }
    .badge-pending { background: #fff8e1; color: #f57f17; }

    /* Butang / Navigation */
    .button-group {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
        flex: 1;
    }

    .btn-success {
        background: #2e7d32;
        color: #ffffff;
    }

    .btn-success:hover {
        background: #1b5e20;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #ff9800;
        color: #ffffff;
    }

    .btn-secondary:hover {
        background: #e68a00;
        transform: translateY(-2px);
    }
</style>

<div class="status-container">
    <div class="status-card">

        <!-- KEPALA STATUS DINAMIK -->
        <?php if($statusId == 1): ?>
            <div class="icon-wrapper success-icon">✓</div>
            <h2 class="status-title" style="color: #2e7d32;">Payment Successful!</h2>
            <p class="status-message">
                Your booking has been confirmed successfully. Thank you for choosing FlashBus for your travel!
            </p>

        <?php elseif($statusId == 3): ?>
            <div class="icon-wrapper failed-icon">✗</div>
            <h2 class="status-title" style="color: #c62828;">Payment Failed</h2>
            <p class="status-message">
                Something went wrong during the checkout process. Please check your banking details and try again.
            </p>

        <?php else: ?>
            <div class="icon-wrapper pending-icon">⌛</div>
            <h2 class="status-title" style="color: #f57f17;">Payment Pending</h2>
            <p class="status-message">
                We are currently processing or waiting for confirmation from the payment gateway. Please refresh or check back in a moment.
            </p>
        <?php endif; ?>

        <!-- RESIT DETAIL (JIKA ADA DATA BOOKING) -->
        <?php if($booking): ?>
            <div class="receipt-box">
                <div class="receipt-title">Transaction Details</div>
                
                <div class="receipt-row">
                    <span class="receipt-label">Booking Code</span>
                    <span class="receipt-value"><?= htmlspecialchars($booking['booking_code']); ?></span>
                </div>

                <div class="receipt-row">
                    <span class="receipt-label">Amount Paid</span>
                    <span class="receipt-value" style="color: #2e7d32; font-size: 16px;">
                        RM <?= number_format($booking['total_amount'], 2); ?>
                    </span>
                </div>

                <div class="receipt-row">
                    <span class="receipt-label">Payment Method</span>
                    <span class="receipt-value"><?= htmlspecialchars($booking['payment_method']); ?></span>
                </div>

                <div class="receipt-row">
                    <span class="receipt-label">Payment Status</span>
                    <span>
                        <?php 
                        $payStatus = strtolower($booking['payment_status']);
                        if($payStatus == 'success') {
                            echo '<span class="badge badge-success">Success</span>';
                        } elseif($payStatus == 'failed') {
                            echo '<span class="badge badge-failed">Failed</span>';
                        } else {
                            echo '<span class="badge badge-pending">Pending</span>';
                        }
                        ?>
                    </span>
                </div>

                <div class="receipt-row">
                    <span class="receipt-label">Booking Status</span>
                    <span>
                        <?php 
                        $bookStatus = strtolower($booking['booking_status']);
                        if($bookStatus == 'confirmed') {
                            echo '<span class="badge badge-success">Confirmed</span>';
                        } elseif($bookStatus == 'cancelled') {
                            echo '<span class="badge badge-failed">Cancelled</span>';
                        } else {
                            echo '<span class="badge badge-pending">Pending</span>';
                        }
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- PILIHAN BUTANG ACTION -->
        <div class="button-group">
            <?php if($statusId == 1 && isset($booking['bookings_id'])): ?>
                <a href="ticket.php?booking_id=<?= $booking['bookings_id']; ?>" class="btn-action btn-success">
                    View Ticket
                </a>
            <?php endif; ?>

            <a href="index_fb.php" class="btn-action btn-secondary">
                Back to Home
            </a>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>