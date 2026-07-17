<?php 
require_once 'db.php';
require_once 'functions.php';

requireLogin();

$stmt = $conn->prepare("
    SELECT 
        bk.bookings_id,
        bk.booking_code,
        bk.total_amount,
        s.travel_date,
        s.departure_time,
        s.platform_number,
        s.status,
        r.origin,
        r.destination,
        b.plate_number,
        o.operators_name
    FROM bookings bk
    JOIN schedules s ON bk.schedules_id = s.schedules_id
    JOIN routes r ON s.routes_id = r.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    JOIN operators o ON b.operators_id = o.operators_id
    WHERE bk.users_id = ?
");

$stmt->bind_param('i', $_SESSION['users_id']);
$stmt->execute();
$bookings = $stmt->get_result();

$pageTitle = 'My Bookings';
include 'header.php';
?>

<style>
/* =========================
   CONTAINER & LAYOUT
   ========================= */
.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 25px;
    color: var(--fb-blue, #0A4DA6);
}

/* =========================
   ALERT & NO BOOKINGS
   ========================= */
.alert {
    padding: 18px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.alert-error {
    background: #FFE8E8;
    border: 1px solid #F5B7B1;
    color: #C0392B;
}

.alert a {
    color: var(--fb-orange, #FF6B1A);
    font-weight: bold;
    text-decoration: none;
}

.alert a:hover {
    text-decoration: underline;
}

/* =========================
   BOOKING CARD
   ========================= */
.trip-card {
    background: white;
    border-radius: 18px;
    padding: 22px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 0.8fr auto;
    align-items: center;
    gap: 20px;
    border: 1px solid #eee;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: 0.3s;
}

.trip-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* =========================
   CARD DETAILS
   ========================= */
.operator {
    font-size: 18px;
    font-weight: 700;
    color: #222;
    margin-bottom: 8px;
}

.meta {
    font-size: 13px;
    color: #777;
    line-height: 1.6;
}

/* =========================
   TIME SECTION
   ========================= */
.time-block {
    text-align: center;
}

.time {
    font-size: 26px;
    font-weight: 700;
    color: var(--fb-orange, #FF6B1A);
}

.place {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

/* =========================
   STATUS BADGE
   ========================= */
.badge {
    display: inline-block;
    padding: 7px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
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
    font-size: 20px;
    font-weight: 700;
    color: #222;
    white-space: nowrap;
}

/* =========================
   BUTTON
   ========================= */
.btn {
    display: inline-block;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-orange {
    background: var(--fb-orange, #FF6B1A);
    color: white;
}

.btn-orange:hover {
    background: var(--fb-orange-dark, #E0550A);
}

/* =========================
   RESPONSIVE
   ========================= */
@media(max-width: 900px) {
    .trip-card {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
    }
    .time-block {
        text-align: center;
    }
}
</style>

<div class="container">
    <div class="section-title">
        My Bookings
    </div>

    <?php if ($bookings->num_rows === 0): ?>
        <div class="alert alert-error">
            You have no bookings yet. 
            <a href="index_fb.php">Search a bus now</a>
        </div>
    <?php endif; ?>

    <?php while ($b = $bookings->fetch_assoc()): ?>
        <div class="trip-card">
            <div>
                <div class="operator">
                    <?= htmlspecialchars($b['origin']) ?> &rarr; <?= htmlspecialchars($b['destination']) ?>
                </div>
                <div class="meta">
                    <?= htmlspecialchars($b['operators_name']) ?> &middot; 
                    Plate: <?= htmlspecialchars($b['plate_number']) ?> &middot; 
                    Code: <strong><?= htmlspecialchars($b['booking_code']) ?></strong>
                </div>
            </div>

            <div class="time-block">
                <div class="time">
                    <?= date('H:i', strtotime($b['departure_time'])) ?>
                </div>
                <div class="place">
                    <?= date('d M Y', strtotime($b['travel_date'])) ?>
                </div>
            </div>

            <div>
                <span class="badge <?= statusBadgeClass($b['status']) ?>">
                    <?= htmlspecialchars($b['status']) ?>
                </span>
                <br>
                <span style="font-size: 13px; color: #777;">
                    Platform: <?= htmlspecialchars($b['platform_number'] ?? 'TBA') ?>
                </span>
            </div>

            <div class="price">
                RM <?= number_format($b['total_amount'], 2) ?>
            </div>

            <div>
                <?php if (!empty($b['bookings_id'])): ?>
                    <a href="ticket.php?booking_id=<?= $b['bookings_id'] ?>" class="btn btn-orange">
                        View Ticket
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include 'footer.php'; ?>