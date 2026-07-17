<?php
session_start();

require_once 'db.php';
require_once 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($conn, $_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT users_id, fullname, email, password, role
        FROM users
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['users_id'] = $user['users_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = strtolower(trim($user['role']));

            if ($_SESSION['role'] === 'admin') {
                header("Location: dashboard_fb.php");
                exit();
            } else {
                header("Location: index_fb.php");
                exit();
            }
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}

$pageTitle = 'Login';
include 'header.php';
?>

<style>
/* ==========================
   LOGIN PAGE & CARD
   ========================== */
body {
    background: #FFF7DD;
}

.form-card {
    width: 420px;
    max-width: 90%;
    margin: 60px auto;
    background: white;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    box-sizing: border-box;
}

.form-card h2 {
    text-align: center;
    margin-bottom: 8px;
    font-size: 28px;
    color: var(--fb-blue, #0A4DA6);
    font-weight: 700;
}

.sub {
    text-align: center;
    color: #777;
    font-size: 14px;
    margin-bottom: 25px;
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

.alert-success {
    background: #E8F8EE;
    color: #198754;
    border: 1px solid #B7E4C7;
}

.alert-error {
    background: #FFE8E8;
    color: #C0392B;
    border: 1px solid #F5B7B1;
}

/* ==========================
   FORM ELEMENTS
   ========================== */
.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.form-group input {
    width: 100%;
    height: 45px;
    padding: 0 15px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
    transition: 0.3s;
}

.form-group input:focus {
    border-color: var(--fb-blue, #0A4DA6);
    box-shadow: 0 0 0 3px rgba(10, 77, 166, 0.12);
}

/* ==========================
   BUTTON
   ========================== */
.btn {
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    border-radius: 10px;
    transition: 0.3s;
}

.btn-block {
    width: 100%;
    height: 45px;
}

.btn-orange {
    background: var(--fb-orange, #FF6B1A);
    color: white;
}

.btn-orange:hover {
    background: var(--fb-orange-dark, #E0550A);
    transform: translateY(-1px);
}

/* ==========================
   LINK & RESPONSIVE
   ========================== */
.form-card a {
    color: var(--fb-blue, #0A4DA6);
    text-decoration: none;
    font-weight: 600;
}

.form-card a:hover {
    text-decoration: underline;
}

@media(max-width: 600px) {
    .form-card {
        margin: 30px auto;
        padding: 25px;
    }
    .form-card h2 {
        font-size: 24px;
    }
}
</style>

<div class="form-card">
    <h2>Welcome back</h2>
    <p class="sub">Login to manage your bus bookings.</p>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            Account created! Please login.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-orange btn-block">Login</button>
    </form>

    <p style="text-align: center; margin-top: 18px; font-size: 14px;">
        No account yet? 
        <a href="register.php">Sign Up</a>
    </p>
</div>

<?php include 'footer.php'; ?>