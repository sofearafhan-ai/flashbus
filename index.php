<?php
require_once 'db.php';
require_once 'functions.php';

$pageTitle = 'Search Buses';
include 'header.php';

$states = [
    "Johor","Kedah","Kelantan","Melaka","Negeri Sembilan","Pahang",
    "Perak","Perlis","Pulau Pinang","Sabah","Sarawak","Selangor",
    "Terengganu","Kuala Lumpur","Labuan","Putrajaya"
];
?>

<div class="hero">
    <h1>Travel Fast with <span>FlashBus</span></h1>
    <p>Search, compare and book bus tickets across multiple operators and routes.</p>
</div>

<form action="search_results.php" method="GET">
    <div class="search-card">

        <!-- SEARCH GRID (ALL IN ONE ROW) -->
        <div class="search-grid">

            <div class="field">
                <label>From</label>
                <select name="origin" required>
                    <option value="">Select State</option>
                    <?php foreach($states as $state): ?>
                        <option value="<?= $state ?>"><?= $state ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>To</label>
                <select name="destination" required>
                    <option value="">Select State</option>
                    <?php foreach($states as $state): ?>
                        <option value="<?= $state ?>"><?= $state ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Travel Date</label>
                <input type="date" name="travel_date" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="field">
                <label>Return Date</label>
                <input type="date" name="return_date" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="field">
                <label>Pax</label>
                <input type="number" name="pax" min="1" value="1" required>
            </div>

        </div>

        <!-- BUTTON -->
        <div class="search-btn-row">
            <button type="submit" class="btn-search">
                Search Bus
            </button>
        </div>

    </div>
</form>

<div class="container">
    <div class="section-title">Why Choose FlashBus?</div>

    <div class="features-grid">
        <div class="stat-card">
            <div class="num">🎟️</div>
            <div class="label">Instant QR Ticket</div>
        </div>

        <div class="stat-card">
            <div class="num">🚻</div>
            <div class="label">See Seatmate Gender</div>
        </div>

        <div class="stat-card">
            <div class="num">🔔</div>
            <div class="label">Live Trip Notifications</div>
        </div>

        <div class="stat-card">
            <div class="num">🛬</div>
            <div class="label">Platform & Bus Plate Info</div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>