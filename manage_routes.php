<?php

$inAdmin = true;

require_once 'db.php';
require_once 'functions.php';

requireAdmin();

/* =========================
   ADD ROUTE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $origin = clean($conn, $_POST['origin']);
    $destination = clean($conn, $_POST['destination']);
    $distance = (int)$_POST['distance_km'];

    /* CHECK DUPLICATE */
    $check = $conn->prepare("
        SELECT routes_id
        FROM routes
        WHERE origin = ?
        AND destination = ?
    ");
    $check->bind_param("ss", $origin, $destination);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        echo "<script>
        alert('Route already exists!');
        window.location='manage_routes.php';
        </script>";
        exit;
    }

    /* INSERT ROUTE */
    $stmt = $conn->prepare("
        INSERT INTO routes (origin, destination, distance_km)
        VALUES(?,?,?)
    ");
    $stmt->bind_param("ssi", $origin, $destination, $distance);
    $stmt->execute();

    header("Location: manage_routes.php");
    exit;
}

/* =========================
   DELETE ROUTE
========================= */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM routes
        WHERE routes_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manage_routes.php");
    exit;
}

/* =========================
   GET ROUTES
========================= */
$routes = $conn->query("
    SELECT *
    FROM routes
    ORDER BY routes_id DESC
");

$pageTitle = 'Manage Routes';
include 'header.php';
?>

<style>
/* ==========================================================================
   LAYOUT STRUCTURING
   ========================================================================== */
* {
    box-sizing: border-box;
}

/* Sidebar Asal FlashBus */
.admin-sidebar {
    position: fixed;
    top: 56px;
    left: 0;
    width: 240px;
    height: calc(100vh - 56px);
    background: #0A4DA6;
    color: white;
    z-index: 99;
}
.admin-sidebar div {
    padding: 20px 24px;
    font-size: 18px;
    font-weight: 700;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.admin-sidebar a {
    display: block;
    padding: 16px 24px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
}
.admin-sidebar a:hover,
.admin-sidebar a.active {
    background: #FF6B1A;
    color: white;
}

/* Content Area */
.admin-content {
    margin-left: 240px; 
    padding: 25px 30px;
    background-color: #f8fafc;
    min-height: calc(100vh - 56px);
}
.route-container {
    max-width: 1100px;
    margin: 0 auto;
}
.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #0A4DA6;
    margin-bottom: 20px;
}

/* ==========================================================================
   ROUTE FORM (CLEAN & COMPACT)
   ========================================================================== */
.route-form {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
}
.route-form-grid {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr auto;
    gap: 15px;
    align-items: center;
}
.route-form input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.route-form input:focus {
    border-color: #0A4DA6;
}
.btn-add {
    background: #0A4DA6;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.btn-add:hover {
    background: #083d85;
}

/* ==========================================================================
   DATA TABLE DESIGN
   ========================================================================== */
.route-table-wrap {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 14px;
}
.data-table th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
}
.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}
.data-table tr:last-child td {
    border-bottom: none;
}
.data-table tr:hover td {
    background: #f8fafc;
}
.route-id {
    font-weight: 600;
    color: #64748b;
}
.route-distance {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}
.btn-delete {
    background: #ef4444;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    transition: background 0.2s;
    display: inline-block;
}
.btn-delete:hover {
    background: #dc2626;
}

/* RESPONSIVE DESIGN */
@media(max-width: 992px){
    .admin-sidebar { position: static; width: 100%; height: auto; }
    .admin-content { margin-left: 0; padding: 15px; }
    .route-form-grid { grid-template-columns: 1fr 1fr; }
    .btn-add { grid-column: span 2; }
}
@media(max-width: 600px) {
    .route-form-grid { grid-template-columns: 1fr; }
    .btn-add { grid-column: span 1; }
    .data-table th:nth-child(1), .data-table td:nth-child(1) { display: none; } /* Sorok ID pada mobile */
}
</style>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div>FlashBus Admin</div>
    <a href="dashboard_fb.php">Dashboard</a>
    <a href="manage_operators.php">Operators</a>
    <a href="manage_buses.php">Buses</a>
    <a href="manage_routes.php" class="active">Routes</a>
    <a href="manage_schedules.php">Schedules</a>
    <a href="manage_bookings.php">Bookings</a>
</div>

<!-- CONTENT MAIN -->
<div class="admin-content">
    <div class="route-container">
        
        <div class="section-title">Manage Routes</div>

        <!-- INPUT FORM LALUAN -->
        <form method="POST" class="route-form">
            <div class="route-form-grid">
                <input 
                    list="originList"
                    name="origin"
                    placeholder="Select or type origin"
                    required>

                <input 
                    list="destinationList"
                    name="destination"
                    placeholder="Select or type destination"
                    required>

                <input 
                    type="number"
                    name="distance_km"
                    placeholder="Distance (km)"
                    required>

                <button type="submit" name="add" class="btn-add">
                    + Add Route
                </button>
            </div>
        </form>

        <!-- JADUAL DATA LALUAN -->
        <div class="route-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th width="120">Distance</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $routes->fetch_assoc()): ?>
                    <tr>
                        <td><span class="route-id">#<?= $r['routes_id']; ?></span></td>
                        <td><?= htmlspecialchars($r['origin']); ?></td>
                        <td><?= htmlspecialchars($r['destination']); ?></td>
                        <td><span class="route-distance"><?= $r['distance_km']; ?> km</span></td>
                        <td>
                            <a href="?delete=<?= $r['routes_id']; ?>" 
                               onclick="return confirm('Delete this route?')" 
                               class="btn-delete">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- DATALIST DI POSISIKAN DENGAN BETUL DI BAWAH HTML -->
<datalist id="originList">
    <optgroup label="Johor, Malaysia">
        <option value="Johor Bahru, Johor, Malaysia">Johor Bahru</option>
        <option value="JB Sentral, Johor, Malaysia">JB Sentral</option>
        <option value="Johor Bahru (JB Larkin Terminal), Johor, Malaysia">JB Larkin Terminal</option>
        <option value="KFC Bus Terminal Larkin, Johor, Malaysia">KFC Larkin Terminal</option>
        <option value="Terminal Bas Johor Jaya, Johor, Malaysia">Johor Jaya Bus Terminal</option>
        <option value="Taman Ungku Tun Aminah (TUTA), Johor, Malaysia">TUTA Terminal</option>
        <option value="Taman Universiti, Johor, Malaysia">Taman Universiti</option>
        <option value="Ulu Tiram, Johor, Malaysia">Ulu Tiram</option>
        <option value="Masai, Johor, Malaysia">Masai</option>
        <option value="Pasir Gudang, Johor, Malaysia">Pasir Gudang</option>
        <option value="Plentong, Johor, Malaysia">Plentong</option>
        <option value="Skudai, Johor, Malaysia">Skudai</option>
        <option value="Senai, Johor, Malaysia">Senai</option>
        <option value="Senai Airport, Johor, Malaysia">Senai Airport</option>
        <option value="Gelang Patah Sentral Bus Terminal, Johor, Malaysia">Gelang Patah Sentral</option>
        <option value="Kota Iskandar, Johor, Malaysia">Kota Iskandar</option>
        <option value="Medini, Johor, Malaysia">Medini</option>
        <option value="Nusajaya, Johor, Malaysia">Nusajaya</option>
        <option value="Forest City, Johor, Malaysia">Forest City</option>
        <option value="Kulai, Johor, Malaysia">Kulai</option>
        <option value="Bandar Putra Kulai, Johor, Malaysia">Bandar Putra Kulai</option>
        <option value="Kluang Bus Terminal, Johor, Malaysia">Kluang Bus Terminal</option>
        <option value="Batu Pahat Bus Terminal, Johor, Malaysia">Batu Pahat Bus Terminal</option>
        <option value="Muar Bus Terminal, Johor, Malaysia">Muar Bus Terminal</option>
        <option value="Segamat Bus Terminal, Johor, Malaysia">Segamat Bus Terminal</option>
        <option value="Tangkak Bus Terminal, Johor, Malaysia">Tangkak Bus Terminal</option>
        <option value="Pontian Bus Terminal, Johor, Malaysia">Pontian Bus Terminal</option>
        <option value="Kukup, Johor, Malaysia">Kukup</option>
        <option value="Sungai Rengit, Johor, Malaysia">Sungai Rengit</option>
        <option value="Kota Tinggi Bus Terminal, Johor, Malaysia">Kota Tinggi Bus Terminal</option>
        <option value="Mersing Bus Terminal, Johor, Malaysia">Mersing Bus Terminal</option>
        <option value="Desaru Coast, Johor, Malaysia">Desaru Coast</option>
        <option value="Desaru Coast Ferry Terminal, Johor, Malaysia">Desaru Ferry Terminal</option>
        <option value="Desaru Fruit Farm, Johor, Malaysia">Desaru Fruit Farm</option>
        <option value="Desaru Water Park, Johor, Malaysia">Desaru Waterpark</option>
        <option value="Hard Rock Hotel Desaru, Johor, Malaysia">Hard Rock Hotel Desaru</option>
        <option value="Four Points by Sheraton Desaru, Johor, Malaysia">Four Points Desaru</option>
        <option value="Legoland Malaysia, Johor, Malaysia">Legoland</option>
        <option value="Puteri Harbour, Johor, Malaysia">Puteri Harbour</option>
        <option value="Sanrio Hello Kitty Town, Johor, Malaysia">Hello Kitty Town</option>
        <option value="KSL City Mall, Johor, Malaysia">KSL City Mall</option>
        <option value="KSL Hotel, Johor, Malaysia">KSL Hotel</option>
        <option value="City Square Mall, Johor, Malaysia">City Square Mall</option>
        <option value="Mid Valley Southkey, Johor, Malaysia">Mid Valley Southkey</option>
        <option value="Ayer Hitam, Johor, Malaysia">Ayer Hitam</option>
        <option value="Ayer Hitam (Shell), Johor, Malaysia">Ayer Hitam Shell</option>
        <option value="Dataran Segamat, Johor, Malaysia">Dataran Segamat</option>
    </optgroup>
    <optgroup label="Kedah, Malaysia">
        <option value="Alor Setar, Kedah, Malaysia">Alor Setar</option>
        <option value="Alor Setar Bus Terminal, Kedah, Malaysia">Alor Setar Bus Terminal</option>
        <option value="Aman Central, Alor Setar, Kedah, Malaysia">Aman Central</option>
        <option value="Sungai Petani, Kedah, Malaysia">Sungai Petani</option>
        <option value="Sungai Petani Bus Terminal, Kedah, Malaysia">Sungai Petani Bus Terminal</option>
        <option value="Sungai Petani Amanjaya Mall, Kedah, Malaysia">Amanjaya Mall</option>
        <option value="Kulim, Kedah, Malaysia">Kulim</option>
        <option value="Kulim Bus Terminal, Kedah, Malaysia">Kulim Bus Terminal</option>
        <option value="Bandar Kulim Hi-Tech, Kedah, Malaysia">Kulim Hi-Tech Park</option>
        <option value="Langkawi, Kedah, Malaysia">Langkawi</option>
        <option value="Kuah Town, Langkawi, Kedah, Malaysia">Kuah Town</option>
        <option value="Langkawi Ferry Terminal, Kedah, Malaysia">Langkawi Ferry Terminal</option>
        <option value="Pantai Cenang, Langkawi, Kedah, Malaysia">Pantai Cenang</option>
        <option value="Underwater World Langkawi, Kedah, Malaysia">Underwater World Langkawi</option>
        <option value="Jitra, Kedah, Malaysia">Jitra</option>
        <option value="Jitra Bus Terminal, Kedah, Malaysia">Jitra Bus Terminal</option>
        <option value="Changlun, Kedah, Malaysia">Changlun</option>
        <option value="Bukit Kayu Hitam, Kedah, Malaysia">Bukit Kayu Hitam</option>
        <option value="Bukit Kayu Hitam Immigration Complex, Kedah, Malaysia">Bukit Kayu Hitam ICQS</option>
        <option value="Pendang, Kedah, Malaysia">Pendang</option>
        <option value="Kuala Kedah, Kedah, Malaysia">Kuala Kedah</option>
        <option value="Kuala Kedah Ferry Terminal, Kedah, Malaysia">Kuala Kedah Ferry Terminal</option>
        <option value="Yan, Kedah, Malaysia">Yan</option>
        <option value="Merbok, Kedah, Malaysia">Merbok</option>
        <option value="Kota Kuala Muda, Kedah, Malaysia">Kota Kuala Muda</option>
        <option value="Baling, Kedah, Malaysia">Baling</option>
        <option value="Kuala Nerang, Kedah, Malaysia">Kuala Nerang</option>
        <option value="Padang Terap, Kedah, Malaysia">Padang Terap</option>
        <option value="Gurun, Kedah, Malaysia">Gurun</option>
        <option value="Bedong, Kedah, Malaysia">Bedong</option>
        <option value="Kota Setar, Kedah, Malaysia">Kota Setar</option>
        <option value="Pokok Sena, Kedah, Malaysia">Pokok Sena</option>
    </optgroup>
    <optgroup label="Kelantan, Malaysia">
        <option value="Kota Bharu, Kelantan, Malaysia">Kota Bharu</option>
        <option value="Kota Bharu Bus Terminal, Kelantan, Malaysia">Kota Bharu Bus Terminal</option>
        <option value="Wakaf Che Yeh, Kelantan, Malaysia">Wakaf Che Yeh</option>
        <option value="Siti Khadijah Market, Kota Bharu, Kelantan, Malaysia">Pasar Siti Khadijah</option>
        <option value="Wakaf Bharu, Kelantan, Malaysia">Wakaf Bharu</option>
        <option value="Wakaf Bharu Bus Terminal, Kelantan, Malaysia">Wakaf Bharu Bus Terminal</option>
        <option value="Tumpat, Kelantan, Malaysia">Tumpat</option>
        <option value="Pengkalan Kubor, Kelantan, Malaysia">Pengkalan Kubor</option>
        <option value="Pengkalan Kubor Ferry Terminal, Kelantan, Malaysia">Pengkalan Kubor Ferry Terminal</option>
        <option value="Pasir Mas, Kelantan, Malaysia">Pasir Mas</option>
        <option value="Pasir Mas Bus Terminal, Kelantan, Malaysia">Pasir Mas Bus Terminal</option>
        <option value="Rantau Panjang, Kelantan, Malaysia">Rantau Panjang</option>
        <option value="Tanah Merah, Kelantan, Malaysia">Tanah Merah</option>
        <option value="Tanah Merah Bus Terminal, Kelantan, Malaysia">Tanah Merah Bus Terminal</option>
        <option value="Machang, Kelantan, Malaysia">Machang</option>
        <option value="Machang Bus Terminal, Kelantan, Malaysia">Machang Bus Terminal</option>
        <option value="Kuala Krai, Kelantan, Malaysia">Kuala Krai</option>
        <option value="Kuala Krai Bus Terminal, Kelantan, Malaysia">Kuala Krai Bus Terminal</option>
        <option value="Gua Musang, Kelantan, Malaysia">Gua Musang</option>
        <option value="Gua Musang Bus Terminal, Kelantan, Malaysia">Gua Musang Bus Terminal</option>
        <option value="Jeli, Kelantan, Malaysia">Jeli</option>
        <option value="Ketereh, Kelantan, Malaysia">Ketereh</option>
        <option value="Kok Lanas, Kelantan, Malaysia">Kok Lanas</option>
        <option value="Pasir Hor, Kelantan, Malaysia">Pasir Hor</option>
        <option value="Kubang Kerian, Kelantan, Malaysia">Kubang Kerian</option>
        <option value="Lundang, Kota Bharu, Kelantan, Malaysia">Lundang</option>
        <option value="Panji, Kota Bharu, Kelantan, Malaysia">Panji</option>
        <option value="Kampung Chekok, Kelantan, Malaysia">Kampung Chekok</option>
    </optgroup>
    <optgroup label="Melaka, Malaysia">
        <option value="Melaka, Malaysia">Melaka</option>
        <option value="Melaka Sentral Bus Terminal, Melaka, Malaysia">Melaka Sentral Bus Terminal</option>
        <option value="Bandar Hilir, Melaka, Malaysia">Bandar Hilir</option>
        <option value="A Famosa Fort, Melaka, Malaysia">A Famosa Fort</option>
        <option value="Stadthuys, Melaka, Malaysia">Stadthuys</option>
        <option value="Jonker Street, Melaka, Malaysia">Jonker Street</option>
        <option value="Mahkota Parade, Melaka, Malaysia">Mahkota Parade</option>
        <option value="Dataran Pahlawan Melaka Megamall, Melaka, Malaysia">Dataran Pahlawan</option>
        <option value="Ayer Keroh, Melaka, Malaysia">Ayer Keroh</option>
        <option value="Ayer Keroh Toll Plaza, Melaka, Malaysia">Ayer Keroh Toll</option>
        <option value="Melaka Zoo, Melaka, Malaysia">Melaka Zoo</option>
        <option value="Melaka Botanical Garden, Melaka, Malaysia">Melaka Botanical Garden</option>
        <option value="Melaka Wonderland Theme Park, Melaka, Malaysia">Melaka Wonderland</option>
        <option value="Alor Gajah, Melaka, Malaysia">Alor Gajah</option>
        <option value="Masjid Tanah, Melaka, Malaysia">Masjid Tanah</option>
        <option value="Jasin, Melaka, Malaysia">Jasin</option>
        <option value="Pulau Sebang, Melaka, Malaysia">Pulau Sebang</option>
        <option value="Pulau Sebang Bus Terminal, Melaka, Malaysia">Pulau Sebang Bus Terminal</option>
        <option value="Batu Berendam, Melaka, Malaysia">Batu Berendam</option>
        <option value="Melaka International Airport, Melaka, Malaysia">Melaka Airport (Batu Berendam)</option>
        <option value="Tanjung Kling, Melaka, Malaysia">Tanjung Kling</option>
        <option value="Klebang, Melaka, Malaysia">Klebang</option>
        <option value="Pantai Klebang, Melaka, Malaysia">Pantai Klebang</option>
        <option value="Bukit Baru, Melaka, Malaysia">Bukit Baru</option>
        <option value="Bukit Katil, Melaka, Malaysia">Bukit Katil</option>
        <option value="Durian Tunggal, Melaka, Malaysia">Durian Tunggal</option>
        <option value="Sungai Udang, Melaka, Malaysia">Sungai Udang</option>
        <option value="Lendu, Melaka, Malaysia">Lendu</option>
        <option value="Serkam, Melaka, Malaysia">Serkam</option>
    </optgroup>
    <optgroup label="Negeri Sembilan, Malaysia">
        <option value="Seremban, Negeri Sembilan, Malaysia">Seremban</option>
        <option value="Seremban Bus Terminal, Negeri Sembilan, Malaysia">Seremban Bus Terminal</option>
        <option value="Terminal 1 Seremban, Negeri Sembilan, Malaysia">Terminal 1 Seremban</option>
        <option value="Terminal 2 Seremban, Negeri Sembilan, Malaysia">Terminal 2 Seremban</option>
        <option value="Nilai, Negeri Sembilan, Malaysia">Nilai</option>
        <option value="Nilai Bus Terminal, Negeri Sembilan, Malaysia">Nilai Bus Terminal</option>
        <option value="Nilai 3 Wholesale Centre, Negeri Sembilan, Malaysia">Nilai 3</option>
        <option value="INTI International University, Nilai, Negeri Sembilan, Malaysia">INTI Nilai</option>
        <option value="Port Dickson, Negeri Sembilan, Malaysia">Port Dickson</option>
        <option value="Teluk Kemang, Port Dickson, Negeri Sembilan, Malaysia">Teluk Kemang</option>
        <option value="PD Waterfront, Port Dickson, Negeri Sembilan, Malaysia">PD Waterfront</option>
        <option value="Lexis Hibiscus Port Dickson, Negeri Sembilan, Malaysia">Lexis Hibiscus</option>
        <option value="Rasah, Seremban, Negeri Sembilan, Malaysia">Rasah</option>
        <option value="Ampangan, Seremban, Negeri Sembilan, Malaysia">Ampangan</option>
        <option value="Senawang, Negeri Sembilan, Malaysia">Senawang</option>
        <option value="Paroi, Seremban, Negeri Sembilan, Malaysia">Paroi</option>
        <option value="Bahau, Negeri Sembilan, Malaysia">Bahau</option>
        <option value="Bahau Bus Terminal, Negeri Sembilan, Malaysia">Bahau Bus Terminal</option>
        <option value="Kuala Pilah, Negeri Sembilan, Malaysia">Kuala Pilah</option>
        <option value="Tampin, Negeri Sembilan, Malaysia">Tampin</option>
        <option value="Gemas, Negeri Sembilan, Malaysia">Gemas</option>
        <option value="Rembau, Negeri Sembilan, Malaysia">Rembau</option>
        <option value="Pedas, Negeri Sembilan, Malaysia">Pedas</option>
        <option value="Pedas Linggi, Negeri Sembilan, Malaysia">Pedas Linggi</option>
        <option value="Jempol, Negeri Sembilan, Malaysia">Jempol</option>
        <option value="Bandar Seri Jempol, Negeri Sembilan, Malaysia">Bandar Seri Jempol</option>
        <option value="Mantin, Negeri Sembilan, Malaysia">Mantin</option>
        <option value="Sendayan, Negeri Sembilan, Malaysia">Sendayan</option>
        <option value="Bandar Sri Sendayan, Negeri Sembilan, Malaysia">Bandar Sri Sendayan</option>
    </optgroup>
    <optgroup label="Pahang, Malaysia">
        <option value="Kuantan, Pahang, Malaysia">Kuantan</option>
        <option value="Kuantan Sentral Bus Terminal, Pahang, Malaysia">Kuantan Sentral Bus Terminal</option>
        <option value="Terminal Makmur Kuantan, Pahang, Malaysia">Terminal Makmur Kuantan</option>
        <option value="Gambang, Pahang, Malaysia">Gambang</option>
        <option value="Gambang Water Park, Pahang, Malaysia">Gambang Water Park</option>
        <option value="Universiti Malaysia Pahang (UMP), Gambang, Pahang, Malaysia">UMP Gambang</option>
        <option value="Genting Highlands, Pahang, Malaysia">Genting Highlands</option>
        <option value="Awana Bus Terminal, Genting Highlands, Pahang, Malaysia">Awana Bus Terminal</option>
        <option value="SkyAvenue Genting, Pahang, Malaysia">SkyAvenue Genting</option>
        <option value="Bentong, Pahang, Malaysia">Bentong</option>
        <option value="Bukit Tinggi, Bentong, Pahang, Malaysia">Bukit Tinggi</option>
        <option value="Colmar Tropicale, Bukit Tinggi, Pahang, Malaysia">Colmar Tropicale</option>
        <option value="Raub, Pahang, Malaysia">Raub</option>
        <option value="Benta, Pahang, Malaysia">Benta</option>
        <option value="Jerantut, Pahang, Malaysia">Jerantut</option>
        <option value="Jerantut Bus Terminal, Pahang, Malaysia">Jerantut Bus Terminal</option>
        <option value="Taman Negara, Pahang, Malaysia">Taman Negara</option>
        <option value="Temerloh, Pahang, Malaysia">Temerloh</option>
        <option value="Temerloh Bus Terminal, Pahang, Malaysia">Temerloh Bus Terminal</option>
        <option value="Mentakab, Pahang, Malaysia">Mentakab</option>
        <option value="Maran, Pahang, Malaysia">Maran</option>
        <option value="Chenor, Pahang, Malaysia">Chenor</option>
        <option value="Bera, Pahang, Malaysia">Bera</option>
        <option value="Kuala Lipis, Pahang, Malaysia">Kuala Lipis</option>
        <option value="Pekan, Pahang, Malaysia">Pekan</option>
        <option value="Universiti Malaysia Pahang (Pekan), Pahang, Malaysia">UMP Pekan</option>
        <option value="Rompin, Pahang, Malaysia">Rompin</option>
        <option value="Kuala Rompin, Pahang, Malaysia">Kuala Rompin</option>
        <option value="Cameron Highlands, Pahang, Malaysia">Cameron Highlands</option>
        <option value="Tanah Rata, Cameron Highlands, Pahang, Malaysia">Tanah Rata</option>
        <option value="Brinchang, Cameron Highlands, Pahang, Malaysia">Brinchang</option>
        <option value="Ringlet, Cameron Highlands, Pahang, Malaysia">Ringlet</option>
        <option value="Lanchang, Pahang, Malaysia">Lanchang</option>
        <option value="Karak, Pahang, Malaysia">Karak</option>
        <option value="Sungai Lembing, Pahang, Malaysia">Sungai Lembing</option>
    </optgroup>
    <optgroup label="Perak, Malaysia">
        <option value="Ipoh, Perak, Malaysia">Ipoh</option>
        <option value="Ipoh Amanjaya Bus Terminal, Perak, Malaysia">Amanjaya Bus Terminal</option>
        <option value="Medan Kidd Bus Terminal, Ipoh, Perak, Malaysia">Medan Kidd Bus Terminal</option>
        <option value="Batu Gajah, Perak, Malaysia">Batu Gajah</option>
        <option value="Pusing, Perak, Malaysia">Pusing</option>
        <option value="Taiping, Perak, Malaysia">Taiping</option>
        <option value="Taiping Bus Terminal, Perak, Malaysia">Taiping Bus Terminal</option>
        <option value="Zoo Taiping, Perak, Malaysia">Zoo Taiping</option>
        <option value="Kuala Sepetang, Perak, Malaysia">Kuala Sepetang</option>
        <option value="Teluk Intan, Perak, Malaysia">Teluk Intan</option>
        <option value="Teluk Intan Bus Terminal, Perak, Malaysia">Teluk Intan Bus Terminal</option>
        <option value="Menara Condong Teluk Intan, Perak, Malaysia">Menara Condong Teluk Intan</option>
        <option value="Lumut, Perak, Malaysia">Lumut</option>
        <option value="Lumut Bus Terminal, Perak, Malaysia">Lumut Bus Terminal</option>
        <option value="Pangkor Island Jetty, Lumut, Perak, Malaysia">Pangkor Jetty</option>
        <option value="Pulau Pangkor, Perak, Malaysia">Pulau Pangkor</option>
        <option value="Manjung, Perak, Malaysia">Manjung</option>
        <option value="Seri Manjung, Perak, Malaysia">Seri Manjung</option>
        <option value="Sitiawan, Perak, Malaysia">Sitiawan</option>
        <option value="Parit Buntar, Perak, Malaysia">Parit Buntar</option>
        <option value="Bagan Serai, Perak, Malaysia">Bagan Serai</option>
        <option value="Kampar, Perak, Malaysia">Kampar</option>
        <option value="Universiti Tunku Abdul Rahman (UTAR), Kampar, Perak, Malaysia">UTAR Kampar</option>
        <option value="Gopeng, Perak, Malaysia">Gopeng</option>
        <option value="Kampung Gajah, Perak, Malaysia">Kampung Gajah</option>
        <option value="Tapah, Perak, Malaysia">Tapah</option>
        <option value="Tapah Road, Perak, Malaysia">Tapah Road</option>
        <option value="Slim River, Perak, Malaysia">Slim River</option>
        <option value="Gerik, Perak, Malaysia">Gerik</option>
        <option value="Lenggong, Perak, Malaysia">Lenggong</option>
        <option value="Pengkalan Hulu, Perak, Malaysia">Pengkalan Hulu</option>
        <option value="Kuala Kangsar, Perak, Malaysia">Kuala Kangsar</option>
        <option value="Sayong, Perak, Malaysia">Sayong</option>
        <option value="Bagan Datuk, Perak, Malaysia">Bagan Datuk</option>
        <option value="Hutan Melintang, Perak, Malaysia">Hutan Melintang</option>
    </optgroup>
    <optgroup label="Perlis, Malaysia">
        <option value="Kangar, Perlis, Malaysia">Kangar</option>
        <option value="Kangar Bus Terminal, Perlis, Malaysia">Kangar Bus Terminal</option>
        <option value="Arau, Perlis, Malaysia">Arau</option>
        <option value="Arau Railway Station, Perlis, Malaysia">Arau Railway Station</option>
        <option value="Padang Besar, Perlis, Malaysia">Padang Besar</option>
        <option value="Padang Besar Bus Terminal, Perlis, Malaysia">Padang Besar Bus Terminal</option>
        <option value="Padang Besar Railway Station, Perlis, Malaysia">Padang Besar Railway Station</option>
        <option value="Chuping, Perlis, Malaysia">Chuping</option>
        <option value="Sungai Batu Pahat, Perlis, Malaysia">Sungai Batu Pahat</option>
        <option value="Kuala Perlis, Perlis, Malaysia">Kuala Perlis</option>
        <option value="Kuala Perlis Ferry Terminal, Perlis, Malaysia">Kuala Perlis Ferry Terminal</option>
        <option value="Beseri, Perlis, Malaysia">Beseri</option>
        <option value="Padang Siding, Perlis, Malaysia">Padang Siding</option>
        <option value="Simpang Empat, Perlis, Malaysia">Simpang Empat</option>
        <option value="Kaki Bukit, Perlis, Malaysia">Kaki Bukit</option>
        <option value="Wang Kelian, Perlis, Malaysia">Wang Kelian</option>
        <option value="Wang Kelian Viewpoint, Perlis, Malaysia">Wang Kelian Viewpoint</option>
        <option value="Bukit Keteri, Perlis, Malaysia">Bukit Keteri</option>
        <option value="Guar Sanji, Perlis, Malaysia">Guar Sanji</option>
        <option value="Titi Tinggi, Perlis, Malaysia">Titi Tinggi</option>
        <option value="Seriab, Perlis, Malaysia">Seriab</option>
        <option value="Jalan Kaki Bukit, Perlis, Malaysia">Jalan Kaki Bukit</option>
        <option value="Universiti Malaysia Perlis (UniMAP), Perlis, Malaysia">UniMAP</option>
    </optgroup>
    <optgroup label="Pulau Pinang, Malaysia">
        <option value="George Town, Pulau Pinang, Malaysia">George Town</option>
        <option value="Komtar Bus Terminal, Pulau Pinang, Malaysia">Komtar Bus Terminal</option>
        <option value="Penang Sentral, Butterworth, Pulau Pinang, Malaysia">Penang Sentral</option>
        <option value="Butterworth Bus Terminal, Pulau Pinang, Malaysia">Butterworth Bus Terminal</option>
        <option value="Sungai Nibong Bus Terminal, Pulau Pinang, Malaysia">Sungai Nibong Bus Terminal</option>
        <option value="Bayan Lepas, Pulau Pinang, Malaysia">Bayan Lepas</option>
        <option value="Penang International Airport, Pulau Pinang, Malaysia">Penang Airport</option>
        <option value="Queensbay Mall, Pulau Pinang, Malaysia">Queensbay Mall</option>
        <option value="Gurney Plaza, Pulau Pinang, Malaysia">Gurney Plaza</option>
        <option value="Gurney Drive, Pulau Pinang, Malaysia">Gurney Drive</option>
        <option value="Tanjung Bungah, Pulau Pinang, Malaysia">Tanjung Bungah</option>
        <option value="Batu Ferringhi, Pulau Pinang, Malaysia">Batu Ferringhi</option>
        <option value="Teluk Bahang, Pulau Pinang, Malaysia">Teluk Bahang</option>
        <option value="Penang National Park, Pulau Pinang, Malaysia">Penang National Park</option>
        <option value="Balik Pulau, Pulau Pinang, Malaysia">Balik Pulau</option>
        <option value="Air Itam, Pulau Pinang, Malaysia">Air Itam</option>
        <option value="Kek Lok Si Temple, Pulau Pinang, Malaysia">Kek Lok Si Temple</option>
        <option value="Bukit Mertajam, Pulau Pinang, Malaysia">Bukit Mertajam</option>
        <option value="Seberang Jaya, Pulau Pinang, Malaysia">Seberang Jaya</option>
        <option value="Perai, Pulau Pinang, Malaysia">Perai</option>
        <option value="Nibong Tebal, Pulau Pinang, Malaysia">Nibong Tebal</option>
        <option value="Jawi, Pulau Pinang, Malaysia">Jawi</option>
        <option value="Simpang Ampat, Pulau Pinang, Malaysia">Simpang Ampat</option>
        <option value="Kepala Batas, Pulau Pinang, Malaysia">Kepala Batas</option>
        <option value="Kuala Muda, Pulau Pinang, Malaysia">Kuala Muda</option>
        <option value="Pulau Jerejak, Pulau Pinang, Malaysia">Pulau Jerejak</option>
    </optgroup>
    <optgroup label="Selangor, Malaysia">
        <option value="Shah Alam, Selangor, Malaysia">Shah Alam</option>
        <option value="Terminal Seksyen 17 Shah Alam, Selangor, Malaysia">Terminal Seksyen 17 Shah Alam</option>
        <option value="Klang, Selangor, Malaysia">Klang</option>
        <option value="Klang Sentral Bus Terminal, Selangor, Malaysia">Klang Sentral</option>
        <option value="Port Klang, Selangor, Malaysia">Port Klang</option>
        <option value="Petaling Jaya, Selangor, Malaysia">Petaling Jaya</option>
        <option value="Sunway Pyramid, Selangor, Malaysia">Sunway Pyramid</option>
        <option value="Sunway Lagoon, Selangor, Malaysia">Sunway Lagoon</option>
        <option value="Subang Jaya, Selangor, Malaysia">Subang Jaya</option>
        <option value="USJ Taipan, Selangor, Malaysia">USJ Taipan</option>
        <option value="Puchong, Selangor, Malaysia">Puchong</option>
        <option value="IOI Mall Puchong, Selangor, Malaysia">IOI Mall Puchong</option>
        <option value="Cheras, Selangor, Malaysia">Cheras</option>
        <option value="Batu 11 Cheras, Selangor, Malaysia">Batu 11 Cheras</option>
        <option value="Kajang, Selangor, Malaysia">Kajang</option>
        <option value="Kajang Bus Terminal, Selangor, Malaysia">Kajang Bus Terminal</option>
        <option value="Semenyih, Selangor, Malaysia">Semenyih</option>
        <option value="Bangi, Selangor, Malaysia">Bangi</option>
        <option value="UKM Bangi, Selangor, Malaysia">UKM Bangi</option>
        <option value="Sepang, Selangor, Malaysia">Sepang</option>
        <option value="KLIA, Selangor, Malaysia">KLIA</option>
        <option value="KLIA2, Selangor, Malaysia">KLIA2</option>
        <option value="Cyberjaya, Selangor, Malaysia">Cyberjaya</option>
        <option value="Putrajaya, Selangor, Malaysia">Putrajaya</option>
        <option value="Ampang, Selangor, Malaysia">Ampang</option>
        <option value="Hulu Langat, Selangor, Malaysia">Hulu Langat</option>
        <option value="Rawang, Selangor, Malaysia">Rawang</option>
        <option value="Sungai Buloh, Selangor, Malaysia">Sungai Buloh</option>
        <option value="Kuala Selangor, Selangor, Malaysia">Kuala Selangor</option>
        <option value="Sekinchan, Selangor, Malaysia">Sekinchan</option>
        <option value="Tanjong Karang, Selangor, Malaysia">Tanjong Karang</option>
        <option value="Sabak Bernam, Selangor, Malaysia">Sabak Bernam</option>
        <option value="Gombak, Selangor, Malaysia">Gombak</option>
        <option value="Batu Caves, Selangor, Malaysia">Batu Caves</option>
        <option value="Hulu Selangor, Selangor, Malaysia">Hulu Selangor</option>
        <option value="Kuala Kubu Bharu, Selangor, Malaysia">Kuala Kubu Bharu</option>
    </optgroup>
    <optgroup label="Terengganu, Malaysia">
        <option value="Kuala Terengganu, Terengganu, Malaysia">Kuala Terengganu</option>
        <option value="Hentian Bas MPKT Kuala Terengganu, Terengganu, Malaysia">Hentian Bas MPKT Kuala Terengganu</option>
        <option value="Kuala Terengganu Bus Terminal, Terengganu, Malaysia">Kuala Terengganu Bus Terminal</option>
        <option value="Gong Badak, Terengganu, Malaysia">Gong Badak</option>
        <option value="Universiti Sultan Zainal Abidin (UniSZA), Terengganu, Malaysia">UniSZA</option>
        <option value="Universiti Malaysia Terengganu (UMT), Terengganu, Malaysia">UMT</option>
        <option value="Kuala Nerus, Terengganu, Malaysia">Kuala Nerus</option>
        <option value="Marang, Terengganu, Malaysia">Marang</option>
        <option value="Marang Bus Terminal, Terengganu, Malaysia">Marang Bus Terminal</option>
        <option value="Dungun, Terengganu, Malaysia">Dungun</option>
        <option value="Dungun Bus Terminal, Terengganu, Malaysia">Dungun Bus Terminal</option>
        <option value="UiTM Dungun, Terengganu, Malaysia">UiTM Dungun</option>
        <option value="Kemaman, Terengganu, Malaysia">Kemaman</option>
        <option value="Chukai, Kemaman, Terengganu, Malaysia">Chukai</option>
        <option value="Kijal, Terengganu, Malaysia">Kijal</option>
        <option value="Besut, Terengganu, Malaysia">Besut</option>
        <option value="Jerteh, Terengganu, Malaysia">Jerteh</option>
        <option value="Kuala Besut Jetty, Terengganu, Malaysia">Kuala Besut Jetty</option>
        <option value="Pulau Perhentian Jetty, Terengganu, Malaysia">Perhentian Jetty</option>
        <option value="Setiu, Terengganu, Malaysia">Setiu</option>
        <option value="Permaisuri, Terengganu, Malaysia">Permaisuri</option>
        <option value="Hulu Terengganu, Terengganu, Malaysia">Hulu Terengganu</option>
        <option value="Kuala Berang, Terengganu, Malaysia">Kuala Berang</option>
        <option value="Rantau Abang, Terengganu, Malaysia">Rantau Abang</option>
        <option value="Paka, Terengganu, Malaysia">Paka</option>
        <option value="Kerteh, Terengganu, Malaysia">Kerteh</option>
        <option value="Kerteh Airport, Terengganu, Malaysia">Kerteh Airport</option>
        <option value="Pulau Redang Jetty, Terengganu, Malaysia">Redang Jetty</option>
        <option value="Pulau Kapas Jetty, Terengganu, Malaysia">Kapas Jetty</option>
    </optgroup>
    <optgroup label="Kuala Lumpur, Malaysia">
        <option value="Terminal Bersepadu Selatan (TBS), Kuala Lumpur, Malaysia">TBS (Bandar Tasik Selatan)</option>
        <option value="KL Sentral, Kuala Lumpur, Malaysia">KL Sentral</option>
        <option value="Kuala Lumpur Sentral Bus Terminal, Malaysia">KL Sentral Bus Terminal</option>
        <option value="Pudu Sentral, Kuala Lumpur, Malaysia">Pudu Sentral</option>
        <option value="Puduraya, Kuala Lumpur, Malaysia">Puduraya</option>
        <option value="Hentian Duta, Kuala Lumpur, Malaysia">Hentian Duta</option>
        <option value="Hentian Pekeliling, Kuala Lumpur, Malaysia">Hentian Pekeliling</option>
        <option value="Bukit Bintang, Kuala Lumpur, Malaysia">Bukit Bintang</option>
        <option value="Pavilion Kuala Lumpur, Malaysia">Pavilion KL</option>
        <option value="Berjaya Times Square, Kuala Lumpur, Malaysia">Berjaya Times Square</option>
        <option value="KLCC, Kuala Lumpur, Malaysia">KLCC</option>
        <option value="Suria KLCC, Kuala Lumpur, Malaysia">Suria KLCC</option>
        <option value="Avenue K, Kuala Lumpur, Malaysia">Avenue K</option>
        <option value="Chow Kit, Kuala Lumpur, Malaysia">Chow Kit</option>
        <option value="Masjid Jamek, Kuala Lumpur, Malaysia">Masjid Jamek</option>
        <option value="Brickfields, Kuala Lumpur, Malaysia">Brickfields</option>
        <option value="Mid Valley Megamall, Kuala Lumpur, Malaysia">Mid Valley Megamall</option>
        <option value="The Gardens Mall, Kuala Lumpur, Malaysia">The Gardens Mall</option>
        <option value="Cheras, Kuala Lumpur, Malaysia">Cheras</option>
        <option value="Bandar Tun Razak, Kuala Lumpur, Malaysia">Bandar Tun Razak</option>
        <option value="Setapak, Kuala Lumpur, Malaysia">Setapak</option>
        <option value="Wangsa Maju, Kuala Lumpur, Malaysia">Wangsa Maju</option>
        <option value="Segambut, Kuala Lumpur, Malaysia">Segambut</option>
        <option value="Sentul, Kuala Lumpur, Malaysia">Sentul</option>
    </optgroup>
</datalist>

<!-- GUNA DESTINATION DENGAN DATALIST YANG SAMA -->
<datalist id="destinationList">
    <!-- Menggunakan semula datalist yang sama melalui javascript/HTML browser -->
</datalist>

<script>
    // Copy options dari originList ke destinationList automatik supaya kod tidak terlalu panjang berulang
    document.getElementById('destinationList').innerHTML = document.getElementById('originList').innerHTML;
</script>

<?php include 'footer.php'; ?>