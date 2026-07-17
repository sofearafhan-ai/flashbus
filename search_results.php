<?php
require_once 'db.php';
require_once 'functions.php';

$origin = clean($conn, $_GET['origin'] ?? '');
$destination = clean($conn, $_GET['destination'] ?? '');
$travel_date = clean($conn, $_GET['travel_date'] ?? date('Y-m-d'));

$stmt = $conn->prepare("
    SELECT
        s.schedules_id,
        s.travel_date,
        s.departure_time,
        s.arrival_time,
        s.platform_number,
        s.status,
        s.price,
        r.origin,
        r.destination,
        b.plate_number,
        b.total_seats,
        b.buses_id,
        o.operators_name
    FROM schedules s
    JOIN routes r ON s.routes_id = r.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    JOIN operators o ON b.operators_id = o.operators_id
    WHERE r.origin = ?
      AND r.destination = ?
      AND s.travel_date = ?
      AND s.status <> 'Cancelled'
    ORDER BY s.departure_time ASC
");

$stmt->bind_param('sss', $origin, $destination, $travel_date);
$stmt->execute();
$results = $stmt->get_result();

$pageTitle = 'Search Results';
include 'header.php';
?>

<style>
/* =========================
   SEARCH RESULT CONTAINER
   ========================= */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
}

/* =========================
   TITLE
   ========================= */
.section-title {
    text-align: center;
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 30px;
    color: var(--fb-blue, #0A4DA6);
}

/* =========================
   TRIP CARD
   ========================= */
.trip-card {
    background: white;
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1.2fr 2fr 0.8fr auto;
    gap: 25px;
    align-items: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: 0.3s;
}

.trip-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

/* =========================
   OPERATOR & META INFO
   ========================= */
.operator {
    font-size: 18px;
    font-weight: bold;
    color: #222;
    margin-bottom: 5px;
}

.meta {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
}

/* =========================
   TIME SECTION
   ========================= */
.time-block {
    text-align: center;
}

.time {
    font-size: 24px;
    font-weight: bold;
    color: var(--fb-orange, #FF6B1A);
}

.place {
    margin-top: 8px;
    font-size: 14px;
    color: #555;
    max-width: 220px;
}

.arrow {
    font-size: 28px;
    color: var(--fb-orange, #FF6B1A);
}

/* =========================
   STATUS BADGE
   ========================= */
.badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.badge.available {
    background: #d4edda;
    color: #155724;
}

.badge.delayed {
    background: #fff3cd;
    color: #856404;
}

.badge.cancelled {
    background: #f8d7da;
    color: #721c24;
}

/* =========================
   PRICE
   ========================= */
.price {
    font-size: 22px;
    font-weight: bold;
    color: var(--fb-orange, #FF6B1A);
    margin-top: 8px;
}

/* =========================
   BUTTON
   ========================= */
.btn {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.btn-orange {
    background: var(--fb-orange, #FF6B1A);
    color: white;
    border: none;
}

.btn-orange:hover {
    background: var(--fb-orange-dark, #E0550A);
}

.btn-outline {
    border: 1px solid #ccc;
    background: #eee;
    color: #777;
}

/* =========================
   ALERT
   ========================= */
.alert {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}

.alert-error {
    background: #FFE8E8;
    color: #C0392B;
    border: 1px solid #F5B7B1;
}

/* =========================
   RESPONSIVE
   ========================= */
@media(max-width: 1000px) {
    .trip-card {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
    }
    .time-block {
        margin: auto;
    }
    .place {
        max-width: none;
    }
}
</style>

<div class="container">
    <div class="section-title">
        <?= htmlspecialchars($origin) ?> &rarr; <?= htmlspecialchars($destination) ?> on <?= htmlspecialchars($travel_date) ?>
    </div>

    <?php if ($results->num_rows === 0): ?>
        <div class="alert alert-error">
            No buses found for this route and date. Try a different search.
        </div>
    <?php endif; ?>

    <?php while ($trip = $results->fetch_assoc()): ?>
        <?php
        // Jumlah seat bas
        $seatStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM seats
            WHERE buses_id = ?
        ");
        $seatStmt->bind_param('i', $trip['buses_id']);
        $seatStmt->execute();
        $totalSeats = $seatStmt->get_result()->fetch_assoc()['total'];

        // Kira seat yang sudah dibooking
        $bookedStmt = $conn->prepare("
            SELECT COUNT(*) AS booked
            FROM booking_seats
            WHERE schedules_id = ?
        ");
        $bookedStmt->bind_param('i', $trip['schedules_id']);
        $bookedStmt->execute();
        $booked = $bookedStmt->get_result()->fetch_assoc()['booked'];

        // Seat yang masih kosong
        $available = $totalSeats - $booked;
        ?>

        <div class="trip-card">
            <div>
                <div class="operator">
                    <?= htmlspecialchars($trip['operators_name']) ?>
                </div>
                <div class="meta">
                    Plate: <?= htmlspecialchars($trip['plate_number']) ?> &middot; 
                    <span class="badge <?= statusBadgeClass($trip['status']) ?>">
                        <?= htmlspecialchars($trip['status']) ?>
                    </span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 14px; justify-content: center;">
                <div class="time-block">
                    <div class="time">
                        <?= date('H:i', strtotime($trip['departure_time'])) ?>
                    </div>
                    <div class="place">
                        <?= htmlspecialchars($trip['origin']) ?>
                    </div>
                </div>

                <div class="arrow">&#10142;</div>

                <div class="time-block">
                    <div class="time">
                        <?= date('H:i', strtotime($trip['arrival_time'])) ?>
                    </div>
                    <div class="place">
                        <?= htmlspecialchars($trip['destination']) ?>
                    </div>
                </div>
            </div>

            <div>
                <div class="meta">
                    <?= $available ?> seats left
                </div>
                <div class="price">
                    RM <?= number_format($trip['price'], 2) ?>
                </div>
            </div>

            <div>
                <?php if ($available > 0 && $trip['status'] !== 'Cancelled'): ?>
                    <a href="seat_selection.php?schedules_id=<?= $trip['schedules_id'] ?>" class="btn btn-orange">
                        Select Seat
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline" disabled>Unavailable</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include 'footer.php'; ?>