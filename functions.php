<?php
// FlashBus - Common helper functions

// NOTA: Penggunaan PHPMailer telah digantikan dengan fungsi mail() bawaan PHP
// bagi mengelakkan ralat fail tidak ditemui, sambil mengekalkan fungsi hantar emel notifikasi.

function isLoggedIn() {
    return isset($_SESSION['users_id']);
}

function isAdmin() {
    return isLoggedIn() &&
           isset($_SESSION['role']) &&
           $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard_fb.php');
        exit();
    }
}

function clean($conn, $str) {
    return mysqli_real_escape_string($conn, trim($str));
}

function generateBookingCode() {
    return 'FB' . strtoupper(substr(uniqid(), -8));
}

function statusBadgeClass($status) {
    switch ($status) {
        case 'Delayed':
            return 'badge-delayed';
        case 'Cancelled':
            return 'badge-cancelled';
        case 'Arrived':
            return 'badge-arrived';
        case 'Departed':
            return 'badge-departed';
        default:
            return 'badge-scheduled';
    }
}

/* ========================================================================
   TAMBAH NOTIFIKASI KE DALAM DATABASE
======================================================================== */
function addNotification($conn, $users_id, $bookings_id, $type, $message) {
    $stmt = $conn->prepare("
        INSERT INTO notifications
        (users_id, bookings_id, type, message, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");

    $stmt->bind_param('iiss', $users_id, $bookings_id, $type, $message);
    $stmt->execute();
    $stmt->close();
}

function unreadNotificationCount($conn, $users_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE users_id = ? AND is_read = 0
    ");

    $stmt->bind_param('i', $users_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['cnt'] ?? 0;
}

/* ========================================================================
   FUNGSI NOTIFIKASI EMEL (Menggunakan mail() Bawaan PHP)
======================================================================== */
function sendNotificationEmail($to_email, $subject, $message_body) {
    // Header wajib untuk memformat emel dalam bentuk HTML (UTF-8)
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    
    // Alamat penghantar (Gantikan domain mengikut domain sebenar sistem anda nanti)
    $headers .= 'From: FlashBus <noreply@yourdomain.com>' . "\r\n";

    // Struktur reka bentuk emel HTML yang ringkas & kemas
    $html_message = "
    <html>
    <head>
        <title>" . htmlspecialchars($subject) . "</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8fafc; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;'>
            <h2 style='color: #0A4DA6; margin-top: 0;'>FlashBus Travel Update</h2>
            <p style='font-size: 15px; color: #475569;'>" . nl2br(htmlspecialchars($message_body)) . "</p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;' />
            <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Emel ini dijana secara automatik oleh sistem FlashBus. Sila jangan balas emel ini.</p>
        </div>
    </body>
    </html>
    ";

    // Hantar emel terus menggunakan servis mail tempatan (local server/hosting)
    // Simbol @ menghalang paparan ralat PHP jika fungsi mail belum diaktifkan di localhost/Laragon
    return @mail($to_email, $subject, $html_message, $headers);
}
?>