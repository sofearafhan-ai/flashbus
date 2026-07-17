<?php
// Run this ONCE in your browser (http://localhost/flashbus/create_admin.php)
// to create the admin account, then DELETE this file for security.
require_once 'db.php';

$email = 'admin@flashbus.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->prepare("SELECT users_id FROM users WHERE email = ?");
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
    $stmt->bind_param('ss', $hash, $email);
    $stmt->execute();
    echo "Admin password reset. Login with $email / $password";
} else {
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, gender, password, role) VALUES ('System Admin', ?, '0000000000', 'Male', ?, 'admin')");
    $stmt->bind_param('ss', $email, $hash);
    $stmt->execute();
    echo "Admin account created! Login with $email / $password";
}
echo "<br><strong>Please delete create_admin.php now for security.</strong>";