<?php
session_start();

require_once 'db.php';
require_once 'functions.php';
requireLogin();

/* =========================
   CHECK USER SESSION
========================= */
$user_id = $_SESSION['users_id'] ?? null;

if (!$user_id) {
    die("User not logged in.");
}

/* =========================
   POST CHECK
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index_fb.php");
    exit;
}

/* =========================
   INPUT
========================= */
$schedules_id = (int)$_POST['schedules_id'];
$seat_ids = $_POST['seat_id'] ?? [];
$names = $_POST['passenger_name'] ?? [];
$genders = $_POST['passenger_gender'] ?? [];

if (empty($seat_ids)) {
    die("No seats selected.");
}

/* =========================
   GET TRIP
========================= */
$stmt = $conn->prepare("
    SELECT s.*, r.origin, r.destination, b.plate_number
    FROM schedules s
    JOIN routes r ON s.routes_id = r.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    WHERE s.schedules_id = ?
");

$stmt->bind_param("i", $schedules_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("Trip not found.");
}

/* =========================
   CALCULATION
========================= */
$total = $trip['price'] * count($seat_ids);
$booking_code = generateBookingCode();

/* =========================
   TRANSACTION
========================= */
$conn->begin_transaction();

try {

    /* INSERT BOOKING */
    $bk = $conn->prepare("
        INSERT INTO bookings (booking_code, users_id, schedules_id, total_amount)
        VALUES (?, ?, ?, ?)
    ");

    $bk->bind_param("siid", $booking_code, $user_id, $schedules_id, $total);
    $bk->execute();

    $booking_id = $conn->insert_id;

    if (!$booking_id) {
        throw new Exception("Booking insert failed.");
    }

    /* INSERT SEATS */
    $bs = $conn->prepare("
        INSERT INTO booking_seats 
        (bookings_id, schedules_id, seats_id, passenger_name, passenger_gender)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($seat_ids as $i => $seat_id) {

        $seat_id = (int)$seat_id;
        $name = clean($conn, $names[$i] ?? '');
        $gender = clean($conn, $genders[$i] ?? '');

        $bs->bind_param(
            "iiiss",
            $booking_id,
            $schedules_id,
            $seat_id,
            $name,
            $gender
        );

        $bs->execute();
    }

    $conn->commit();

    header("Location: ticket.php?bookings_id=" . $booking_id);
    exit;

} catch (Exception $e) {

    $conn->rollback();
    die("Booking failed: " . $e->getMessage());
}
?>