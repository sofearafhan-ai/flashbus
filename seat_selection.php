<?php
require_once 'db.php';
require_once 'functions.php';

// Menghalang pengguna yang belum log masuk daripada mengakses halaman ini
requireLogin(); 

$schedules_id = (int)($_GET['schedules_id'] ?? 0);

/* =========================
GET TRIP INFO
========================= */
$stmt = $conn->prepare("
    SELECT
        s.schedules_id,
        s.travel_date,
        s.departure_time,
        s.arrival_time,
        s.price,
        r.origin,
        r.destination,
        b.buses_id,
        b.plate_number,
        b.total_seats
    FROM schedules s
    JOIN routes r ON s.routes_id = r.routes_id
    JOIN buses b ON s.buses_id = b.buses_id
    WHERE s.schedules_id = ?
");

$stmt->bind_param("i", $schedules_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("Trip not found");
}

/* =========================
GET SEATS STATUS
========================= */
$seatStmt = $conn->prepare("
    SELECT
        s.seats_id,
        s.seat_number,
        p.passenger_name,
        p.gender
    FROM seats s
    LEFT JOIN passengers p ON s.seats_id = p.seats_id
    LEFT JOIN bookings b ON p.bookings_id = b.bookings_id AND b.schedules_id = ?
    WHERE s.buses_id = ?
    ORDER BY s.seats_id ASC
");

$seatStmt->bind_param("ii", $schedules_id, $trip['buses_id']);
$seatStmt->execute();
$seats = $seatStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Select Seat";
include 'header.php';
?>

<div class="container">
    <div class="section-title">
        <?= htmlspecialchars($trip['origin']) ?> &rarr; <?= htmlspecialchars($trip['destination']) ?>
    </div>

    <p class="trip-info">
        <?= date('d M Y', strtotime($trip['travel_date'])) ?> |
        <?= date('H:i', strtotime($trip['departure_time'])) ?> |
        RM <?= number_format($trip['price'], 2) ?>
    </p>

    <div class="seat-box">
        <h3 class="bus-title">🚍 FRONT</h3>
        <div class="seat-grid">
            <?php for ($i = 0; $i < count($seats); $i += 3): ?>
                <div class="seat-row">
                    <?php
                    $list = [
                        $seats[$i] ?? null,
                        $seats[$i + 1] ?? null,
                        $seats[$i + 2] ?? null
                    ];

                    foreach ($list as $index => $seat):
                        if ($index == 1) {
                            echo '<div class="aisle"></div>';
                        }

                        if ($seat) {
                            $class = "available";
                            if ($seat['gender'] == "Male") {
                                $class = "male";
                            } elseif ($seat['gender'] == "Female") {
                                $class = "female";
                            }
                    ?>
                            <div class="seat <?= $class ?>" data-id="<?= $seat['seats_id'] ?>" data-number="<?= $seat['seat_number'] ?>" <?php if ($class == "available"): ?> onclick="toggleSeat(this)" <?php endif; ?>>
                                <?= $seat['seat_number'] ?>
                            </div>
                    <?php
                        }
                    endforeach;
                    ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="legend">
        <div><span class="color available-color"></span> Available</div>
        <div><span class="color selected-color"></span> Selected</div>
        <div><span class="color male-color"></span> Male</div>
        <div><span class="color female-color"></span> Female</div>
    </div>

    <!-- GABUNGAN FORM UTAMA -->
    <form id="seatForm">
        <input type="hidden" name="schedules_id" value="<?= $schedules_id ?>">
        
        <div id="passengerForms"></div>

        <div class="checkout">
            <button type="button" class="btn-orange" onclick="validateCheckout()">
                Continue Checkout
            </button>
        </div>

        <!-- PAYMENT MODAL -->
        <div id="paymentModal" class="modal">
            <div class="modal-content">
                <h2>Payment Information</h2>

                <div id="paymentArea">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment</option>
                        <option value="fpx">Online Banking (FPX)</option>
                    </select>

                    <div class="modal-btn">
                        <button type="button" class="btn-orange" onclick="hantarDataKeToyyibPay()">
                            Confirm & Pay
                        </button>
                        <button type="button" onclick="closeModal()" class="btn-cancel">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .container { padding: 30px; }
    .section-title { font-size: 24px; font-weight: bold; }
    .trip-info { color: #777; }
    .seat-box { background: white; padding: 25px; border-radius: 15px; margin-top: 20px; }
    .bus-title { text-align: center; }
    .seat-grid { display: flex; flex-direction: column; align-items: center; }
    .seat-row { display: flex; align-items: center; }
    .aisle { width: 40px; }
    .seat { width: 45px; height: 45px; margin: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid #ccc; }
    .available { background: #ccc; cursor: pointer; }
    .selected { background: green; color: white; }
    .male { background: #2196f3; color: white; cursor: not-allowed; }
    .female { background: #e91e63; color: white; cursor: not-allowed; }
    .legend { display: flex; gap: 20px; margin-top: 20px; }
    .color { width: 20px; height: 20px; display: inline-block; border-radius: 5px; }
    .available-color { background: #ccc; }
    .selected-color { background: green; }
    .male-color { background: #2196f3; }
    .female-color { background: #e91e63; }
    .passenger-box { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 10px; margin-top: 15px; }
    .passenger-box input, .passenger-box select { width: 100%; padding: 10px; margin-top: 8px; }
    .checkout { text-align: right; margin-top: 20px; }
    .btn-orange { background: #ff9800; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; }
    .btn-orange:disabled { background: #ffcc80; cursor: not-allowed; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, .5); z-index: 999; }
    .modal-content { background: white; width: 420px; padding: 25px; border-radius: 15px; margin: 7% auto; }
    .modal-content select { width: 100%; padding: 10px; margin-bottom: 12px; }
    .modal-btn { text-align: right; margin-top: 15px; }
    .btn-cancel { padding: 12px 20px; border: none; border-radius: 8px; margin-left: 10px; cursor: pointer; }
</style>

<script>
    let selectedSeats = {};

    /* =========================
       SELECT SEAT
    ========================= */
    function toggleSeat(el) {
        let id = el.dataset.id;
        let number = el.dataset.number;

        if (selectedSeats[id]) {
            delete selectedSeats[id];
            el.className = "seat available";
        } else {
            selectedSeats[id] = number;
            el.className = "seat selected";
        }
        renderPassengers();
    }

    /* =========================
       PASSENGER FORM
    ========================= */
    function renderPassengers() {
        let box = document.getElementById("passengerForms");
        box.innerHTML = "";

        Object.keys(selectedSeats).forEach(id => {
            box.innerHTML += `
            <div class="passenger-box">
                <h4>Seat ${selectedSeats[id]}</h4>
                <input type="hidden" name="seat_id[]" value="${id}">

                <label>Passenger Name</label>
                <input type="text" name="passenger_name[]" placeholder="Passenger Name" required>

                <label>Gender</label>
                <select name="passenger_gender[]" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>

                <label>Phone Number</label>
                <input type="text" name="phone[]" placeholder="Phone Number" required>
            </div>
            `;
        });
    }

    /* =========================
       VALIDATE CHECKOUT & OPEN MODAL
    ========================= */
    function validateCheckout() {
        let seats = Object.keys(selectedSeats);

        if (seats.length == 0) {
            alert("Please select seat first");
            return;
        }

        let names = document.querySelectorAll('input[name="passenger_name[]"]');
        let gender = document.querySelectorAll('select[name="passenger_gender[]"]');
        let phone = document.querySelectorAll('input[name="phone[]"]');

        for (let i = 0; i < names.length; i++) {
            if (names[i].value.trim() == "") {
                alert("Please enter passenger name");
                return;
            }
            if (gender[i].value == "") {
                alert("Please select gender");
                return;
            }
            if (phone[i].value.trim() == "") {
                alert("Please enter phone number");
                return;
            }
        }
        openModal();
    }

    function openModal() {
        document.getElementById("paymentModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("paymentModal").style.display = "none";
    }

    /* =========================
       SUBMIT DATA TO TOYYIBPAY
    ========================= */
    function hantarDataKeToyyibPay() {
        let paymentMethod = document.querySelector('select[name="payment_method"]').value;

        if (paymentMethod === "") {
            alert("Please select a payment method");
            return;
        }

        let data = new FormData(document.getElementById("seatForm"));

        let confirmBtn = document.querySelector("#paymentArea .btn-orange");
        confirmBtn.innerText = "Processing...";
        confirmBtn.disabled = true;

        fetch("process_payment.php", {
            method: "POST",
            body: data
        })
        .then(async response => {
            // Memastikan ralat status seperti 401 dikesan sebelum cuba membaca sebagai JSON
            if (!response.ok) {
                if (response.status === 401) {
                    let result = await response.json();
                    alert("Sila log masuk terlebih dahulu untuk meneruskan pembayaran.");
                    window.location.href = result.redirect || 'login.php';
                    throw new Error('User not logged in');
                }
                throw new Error('Server error');
            }
            
            // Baca teks mentah dahulu untuk langkah berjaga-jaga sekiranya HTML dihantar semula
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error("Respons bukan JSON:", text);
                throw new Error("Respons server bermasalah (bukan JSON sah).");
            }
        })
        .then(result => {
            if (result.success) {
                // Selesai - Hala pengguna ke pautan pembayaran ToyyibPay
                window.location.href = result.payment_url;
            } else {
                alert("Error: " + result.message);
                confirmBtn.innerText = "Confirm & Pay";
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            if (error.message !== 'User not logged in') {
                alert("Pembayaran gagal diproses. Sila cuba lagi atau hubungi sokongan.");
            }
            confirmBtn.innerText = "Confirm & Pay";
            confirmBtn.disabled = false;
        });
    }
</script>

<?php include 'footer.php'; ?>