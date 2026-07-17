<?php
// FlashBus - Database connection (Laragon default MySQL settings)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'flashbus_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Start session globally for the whole app
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}