<?php
require_once 'db.php';
require_once 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($conn, $_POST['fullname']);
    $email = clean($conn, $_POST['email']);
    $phone = clean($conn, $_POST['phone']);
    $gender = clean($conn, $_POST['gender']);
    $password = $_POST['password'];

    if ($name && $email && $phone && $gender && $password) {
        // CHECK EMAIL EXIST
        $check = $conn->prepare("
            SELECT users_id
            FROM users
            WHERE email = ?
        ");
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            // HASH PASSWORD
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // INSERT USER
            $stmt = $conn->prepare("
                INSERT INTO users (fullname, email, phone, gender, password, role)
                VALUES (?, ?, ?, ?, ?, 'customer')
            ");
            $stmt->bind_param('sssss', $name, $email, $phone, $gender, $hash);

            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

$pageTitle = 'Sign Up';
include 'header.php';
?>

<style>
/* ==========================
   REGISTER PAGE & CARD
   ========================== */
body {
    background: #FFF7DD;
}

.form-card {
    width: 480px;
    max-width: 90%;
    margin: 60px auto;
    background: #fff;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    box-sizing: border-box;
}

.form-card h2 {
    margin: 0;
    text-align: center;
    color: var(--fb-blue, #0A4DA6);
    font-size: 30px;
    font-weight: 700;
}

.sub {
    text-align: center;
    color: #777;
    margin: 10px 0 28px;
    font-size: 14px;
}

/* ==========================
   ALERT
   ========================== */
.alert {
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: #FFEAEA;
    border: 1px solid #F5B7B1;
    color: #C0392B;
}

/* ==========================
   FORM ELEMENTS
   ========================== */
.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    height: 48px;
    padding: 0 15px;
    border: 1px solid #D9D9D9;
    border-radius: 10px;
    font-size: 14px;
    background: #fff;
    transition: .3s;
    box-sizing: border-box;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--fb-blue, #0A4DA6);
    box-shadow: 0 0 0 3px rgba(10, 77, 166, 0.15);
}

/* ==========================
   BUTTON
   ========================== */
.btn {
    border: none;
    cursor: pointer;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    transition: .3s;
}

.btn-block {
    width: 100%;
    height: 48px;
}

.btn-orange {
    background: var(--fb-orange, #FF6B1A);
    color: #fff;
}

.btn-orange:hover {
    background: var(--fb-orange-dark, #E0550A);
    transform: translateY(-2px);
}

/* ==========================
   LINK & RESPONSIVE
   ========================== */
.form-card p {
    color: #555;
}

.form-card a {
    color: var(--fb-blue, #0A4DA6);
    text-decoration: none;
    font-weight: 600;
}

.form-card a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .form-card {
        width: 95%;
        margin: 35px auto;
        padding: 25px;
    }
    .form-card h2 {
        font-size: 25px;
    }
}
</style>

<div class="form-card">
    <h2>Create your account</h2>
    <p class="sub">Join FlashBus and book your trips in seconds.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="Enter your phone number" required>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="">-- Select Gender --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-orange btn-block">Sign Up</button>
    </form>

    <p style="text-align: center; margin-top: 20px; font-size: 14px;">
        Already have an account? 
        <a href="login.php">Login</a>
    </p>
</div>

<?php include 'footer.php'; ?>