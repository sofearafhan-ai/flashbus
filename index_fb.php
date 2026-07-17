<?php
require_once 'db.php';
require_once 'functions.php';

$searchError = "";

if($_SERVER['REQUEST_METHOD']=="GET" 
&& isset($_GET['origin'])
&& isset($_GET['destination'])
&& isset($_GET['travel_date'])){

    $origin = clean($conn,$_GET['origin']);
    $destination = clean($conn,$_GET['destination']);
    $travel_date = clean($conn,$_GET['travel_date']);

    $check = $conn->prepare("
        SELECT s.schedules_id
        FROM schedules s
        JOIN routes r ON s.routes_id = r.routes_id
        WHERE r.origin = ? AND r.destination = ? AND s.travel_date = ?
    ");

    $check->bind_param("sss", $origin, $destination, $travel_date);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        header("Location: search_results.php?".http_build_query($_GET));
        exit;
    } else {
        $searchError = "Sorry, no bus trip available for this route and date.";
    }
}

$origins = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");

$pageTitle = 'Search Buses';
include 'header.php';
?>

<style>
/* ==============================================
   GLOBAL REFACTOR & MODERN BASE
=============================================== */
body {
    background: linear-gradient(180deg, #fffcf3 0%, #f8fafc 500px, #f8fafc 100%);
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: #1e293b;
    margin: 0;
}

.container {
    width: 92%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 0;
}

.section-title {
    text-align: center;
    font-size: 32px;
    color: #1F3C88;
    font-weight: 800;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}

.section-subtitle {
    text-align: center;
    color: #64748b;
    font-size: 16px;
    margin-bottom: 45px;
}

/* ==============================================
   HERO SECTION
=============================================== */
.hero {
    text-align: center;
    padding: 90px 20px 60px;
    position: relative;
    overflow: hidden;
}

.hero h1 {
    font-size: 46px;
    margin-bottom: 18px;
    color: #1F3C88;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.hero h1 span {
    color: #ff8c00;
}

.hero p {
    font-size: 19px;
    color: #475569;
    max-width: 650px;
    margin: auto;
    line-height: 1.6;
}

/* Decorative Background Elements */
.hero:before {
    content: "";
    position: absolute;
    width: 350px;
    height: 350px;
    background: #ffb347;
    opacity: .12;
    border-radius: 50%;
    top: -120px;
    left: -100px;
    z-index: -1;
}

.hero:after {
    content: "";
    position: absolute;
    width: 300px;
    height: 300px;
    background: #1F3C88;
    opacity: .06;
    border-radius: 50%;
    right: -80px;
    bottom: 0;
    z-index: -1;
}

/* ==============================================
   PREMIUM SEARCH CARD
=============================================== */
.search-card {
    width: 92%;
    max-width: 1200px;
    margin: 0 auto 80px;
    background: #ffffff;
    padding: 35px;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(31, 60, 136, 0.08);
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    align-items: end;
    border: 1px solid rgba(255, 140, 0, 0.15);
}

.field {
    display: flex;
    flex-direction: column;
}

.field label {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.field input, .field select {
    width: 100%;
    padding: 14px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    border-radius: 12px;
    font-size: 14px;
    color: #1e293b;
    outline: none;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.field input:hover, .field select:hover {
    border-color: #ff8c00;
    background: #ffffff;
}

.field input:focus, .field select:focus {
    border-color: #ff8c00;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.15);
}

/* ==============================================
   SEARCH BUTTON
=============================================== */
.search-btn {
    display: flex;
}

.btn-orange {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #ff9d18, #ff6b00);
    color: white;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(255, 107, 0, 0.3);
    transition: all 0.3s ease;
}

.btn-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(255, 107, 0, 0.45);
}

/* ==============================================
   LIVE DASHBOARD SECTION
=============================================== */
.dashboard-section {
    background: #0f172a;
    color: #f8fafc;
    padding: 50px 0;
}

.dash-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
}

.dash-counter-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 24px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.dash-counter-item {
    margin-bottom: 20px;
}
.dash-counter-item:last-child { margin-bottom: 0; }

.dash-counter-item .val {
    font-size: 32px;
    font-weight: 800;
    color: #ff8c00;
}
.dash-counter-item .lbl {
    font-size: 13px;
    color: #94a3b8;
}

.live-table {
    width: 100%;
    border-collapse: collapse;
}

.live-table th {
    text-align: left;
    padding: 12px;
    font-size: 12px;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 2px solid #334155;
}

.live-table td {
    padding: 14px 12px;
    font-size: 13px;
    border-bottom: 1px solid #1e293b;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
}

.status-indicator.otw {
    background: rgba(234, 179, 8, 0.15);
    color: #fde047;
}

.live-pulse {
    width: 8px;
    height: 8px;
    background: #4ade80;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(74, 222, 128, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
}

/* ==============================================
   FEATURES SECTIONS
=============================================== */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.stat-card {
    background: white;
    padding: 35px 20px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:before {
    content: "";
    position: absolute;
    height: 4px;
    width: 100%;
    background: linear-gradient(90deg, #ff9d18, #ff6b00);
    top: 0;
    left: 0;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 35px rgba(31, 60, 136, 0.12);
}

.stat-card .num {
    font-size: 42px;
    margin-bottom: 15px;
}

.stat-card .label {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.stat-card .desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

/* ==============================================
   MODAL DENGAN CAROUSEL SYSTEM (IKLAN SLIDER < & >)
=============================================== */
.adv-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s ease;
}

.adv-overlay.show {
    opacity: 1;
    pointer-events: auto;
}

.adv-modal {
    background: #ffffff;
    width: 92%;
    max-width: 400px; /* Dikecilkan lebar kotak supaya ngam dengan orientasi menegak gambar */
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
    position: relative;
    transform: scale(0.85);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.adv-overlay.show .adv-modal {
    transform: scale(1);
}

/* Butang Tutup X */
.adv-close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: #1e293b;
    font-size: 20px;
    font-weight: bold;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 110;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.2s;
}

.adv-close-btn:hover {
    background: #ffffff;
    color: #ef4444;
}

/* Kontainer Utama Slider */
/* Kontainer Utama Slider */
.carousel-container {
    position: relative;
    width: 100%;
    overflow: hidden; /* Memotong iklan lain yang berada di luar kotak modal */
}

/* Trek yang memegang semua slaid iklan */
.carousel-track {
    display: flex !important;
    flex-direction: row !important; /* Susun iklan 1, 2, 3 secara baris melintang ke kanan */
    transition: transform 0.4s ease-in-out;
    width: 100% !important; /* Tukar ke 100% supaya ia mengikut kontainer utama */
}

/* Rupa kad iklan tunggal */
.carousel-slide {
    width: 100% !important; /* Setiap slaid WAJIB ambil 100% lebar modal */
    flex-shrink: 0 !important; /* MENYEKAT iklan 2 dan 3 daripada mengecil atau mengikut iklan 1 */
    box-sizing: border-box;
    display: flex;
    flex-direction: column; 
}

.adv-img-holder {
    width: 100%;
    height: 450px; 
    overflow: hidden;
    background: #f8fafc;
    position: relative;
}

.adv-img-holder img {
    width: 100%;
    height: 100%;
    object-fit: contain; 
}

.adv-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #ff8c00;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 10;
}

.adv-content {
    padding: 20px 25px;
    text-align: center;
    background: #ffffff;
}

.adv-content h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    color: #1f3c88;
    font-weight: 800;
}

.adv-content p {
    margin: 0 0 15px 0;
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

.adv-action-btn {
    display: inline-block;
    padding: 10px 30px;
    background: linear-gradient(135deg, #1f3c88, #11224d);
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(31, 60, 136, 0.2);
    transition: all 0.2s;
}

.adv-action-btn:hover {
    background: #ff8c00;
    box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3);
}

/* ==============================================
   NAVIGASI BUTTON INTERFACE (< & >)
=============================================== */
.nav-btn {
    position: absolute;
    top: 225px; /* Diletakkan tepat di tengah paksi tinggi gambar baru (450px / 2) */
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e2e8f0;
    color: #1f3c88;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 105;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}

.nav-btn:hover {
    background: #ff8c00;
    color: white;
    border-color: #ff8c00;
}

.nav-btn.prev-btn { left: 10px; }
.nav-btn.next-btn { right: 10px; }

/* Indikator Titik (Dots) di Bahagian Bawah */
.carousel-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding-bottom: 20px;
    background: #ffffff;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    cursor: pointer;
    transition: all 0.3s;
}

.dot.active {
    background: #ff8c00;
    width: 20px;
    border-radius: 4px;
}

/* ==============================================
   RESPONSIVE LAYOUTS
=============================================== */
@media(max-width: 1200px) {
    .search-card { grid-template-columns: repeat(3, 1fr); }
    .dash-grid { grid-template-columns: 1fr; }
    .feature-grid { grid-template-columns: repeat(2, 1fr); }
}

@media(max-width: 768px) {
    .hero { padding: 60px 20px 40px; }
    .hero h1 { font-size: 34px; }
    .search-card { grid-template-columns: 1fr; padding: 25px; margin-bottom: 50px; }
    .feature-grid { grid-template-columns: 1fr; }
}
</style>

<!-- POPUP MODAL DENGAN NAVIGASI BUTTON CAROUSEL INTERFACE -->
<div class="adv-overlay" id="advOverlay">
    <div class="adv-modal">
        <button class="adv-close-btn" id="closeAdvBtn">&times;</button>
        
        <!-- Butang Navigasi Kiri & Kanan -->
        <button class="nav-btn prev-btn" id="prevBtn">&#10094;</button>
        <button class="nav-btn next-btn" id="nextBtn">&#10095;</button>

        <div class="carousel-container">
            <div class="carousel-track" id="carouselTrack">
                
<!-- IKLAN SLIDE 1 -->
<div class="carousel-slide">
    <div class="adv-img-holder">
        <!-- Tukar kepada relative path seperti ini -->
        <img src="img/iklan1.jpeg">
    </div>
</div>

<!-- IKLAN SLIDE 2 -->
<div class="carousel-slide">
    <div class="adv-img-holder">
        <img src="img/iklan2.jpeg">
    </div>
</div>

<!-- IKLAN SLIDE 3 -->
<div class="carousel-slide">
    <div class="adv-img-holder">
        <img src="img/iklan3.jpeg" >
    </div>
</div>

            </div>
        </div>

        <!-- Indikator Dots -->
        <div class="carousel-dots">
            <span class="dot active" data-index="0"></span>
            <span class="dot" data-index="1"></span>
            <span class="dot" data-index="2"></span>
        </div>
    </div>
</div>

<!-- HERO SECTION -->
<div class="hero">
    <h1>Travel Fast with <span>FlashBus</span></h1>
    <p>Search, compare and book bus tickets instantly across multiple premium operators and routes.</p>
</div>

<!-- ERROR DISPLAY -->
<?php if($searchError!=""): ?>
<div style="width:92%; max-width:1200px; margin:-20px auto 30px; padding:18px; background:#fef2f2; border-left:6px solid #ef4444; border-radius:12px; color:#991b1b; font-weight:700; box-sizing:border-box;">
    🚌 <?php echo $searchError; ?>
</div>
<?php endif; ?>

<!-- SEARCH INTERFACE -->
<form method="GET">
    <div class="search-card">
        <div class="field">
            <label>📍 From</label>
            <input list="originList" name="origin" placeholder="Select origin" required>
            <datalist id="originList">
                <?php while($row=$origins->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['origin']); ?>">
                <?php endwhile; ?>
            </datalist>
        </div>

        <div class="field">
            <label>📍 To</label>
            <input list="destinationList" name="destination" placeholder="Select destination" required>
            <datalist id="destinationList">
                <?php while($row=$destinations->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['destination']); ?>">
                <?php endwhile; ?>
            </datalist>
        </div>

        <div class="field">
            <label>📅 Travel Date</label>
            <input type="date" name="travel_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="field">
            <label>📅 Return Date</label>
            <input type="date" name="return_date">
        </div>

        <div class="field">
            <label>👥 Pax</label>
            <select name="pax" required>
                <?php for($i=1;$i<=10;$i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Passenger<?php echo ($i > 1) ? 's' : ''; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="search-btn">
            <button type="submit" class="btn-orange">Search Bus</button>
        </div>
    </div>
</form>


<!-- VALUE PROPOSITION / FEATURES SECTION -->
<div class="container">
    <div class="section-title">Why Choose FlashBus?</div>
    <div class="section-subtitle">We provide top-tier terminal management integration for seamless travels.</div>

    <div class="feature-grid">
        <div class="stat-card">
            <div class="num">🎟️</div>
            <div class="label">Instant QR Ticket</div>
            <div class="desc">Bypass ticketing physical counters. Scan your mobile generated QR directly at the boarding gates.</div>
        </div>

        <div class="stat-card">
            <div class="num">🚻</div>
            <div class="label">See Seatmate Gender</div>
            <div class="desc">Smart booking logic lets you view existing seat assignments' gender for optimized comfort.</div>
        </div>

        <div class="stat-card">
            <div class="num">🔔</div>
            <div class="label">Live Notifications</div>
            <div class="desc">Receive immediate automated system dynamic pings for scheduling re-routing or delays.</div>
        </div>

        <div class="stat-card">
            <div class="num">🚌</div>
            <div class="label">Platform & Plate Info</div>
            <div class="desc">Acquire accurate live vehicle assignment dispatch plate numbers and exact bay locations.</div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
// 1. Kawalan Input Had Tarikh Pulang
const travel = document.querySelector('input[name="travel_date"]');
const returnDate = document.querySelector('input[name="return_date"]');
travel.addEventListener('change', function(){
    returnDate.min = this.value;
});

// 2. Logik Carousel Slider Iklan beserta Butang '<' & '>'
const overlay = document.getElementById('advOverlay');
const closeBtn = document.getElementById('closeAdvBtn');
const actionButtons = document.querySelectorAll('.close-trigger');

const track = document.getElementById('carouselTrack');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const dots = document.querySelectorAll('.dot');

let currentIndex = 0;
const totalSlides = 3;

// Fungsi untuk gerakkan track/slide iklan
function updateSlider(index) {
    currentIndex = index;
    // Mengalihkan peratusan transformasi x paksi mengikut index aktif
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    // Kemas kini status reka bentuk titik petunjuk (dots)
    dots.forEach((dot, idx) => {
        if(idx === currentIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// Event klik butang kanan (>)
nextBtn.addEventListener('click', () => {
    let nextIndex = currentIndex + 1;
    if(nextIndex >= totalSlides) nextIndex = 0; // Pusing balik ke iklan pertama jika dah hujung
    updateSlider(nextIndex);
});

// Event klik butang kiri (<)
prevBtn.addEventListener('click', () => {
    let prevIndex = currentIndex - 1;
    if(prevIndex < 0) prevIndex = totalSlides - 1; // Pusing balik ke iklan terakhir jika undur dari pertama
    updateSlider(prevIndex);
});

// Event klik pada titik petunjuk bawah secara manual
dots.forEach(dot => {
    dot.addEventListener('click', (e) => {
        const targetIndex = parseInt(e.target.getAttribute('data-index'));
        updateSlider(targetIndex);
    });
});

window.addEventListener('DOMContentLoaded', () => {
    if (!sessionStorage.getItem('hasSeenPopupAd')) {
        setTimeout(() => {
            overlay.classList.add('show');
            sessionStorage.setItem('hasSeenPopupAd', 'true');
        }, 1500); 
    }
});

const closePopup = () => {
    overlay.classList.remove('show');
};

// Pasang fungsi tutup
closeBtn.addEventListener('click', closePopup);
actionButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault(); 
        closePopup();
    });
});
overlay.addEventListener('click', (e) => {
    if(e.target === overlay) closePopup();
});
</script>

<?php include 'footer.php'; ?>