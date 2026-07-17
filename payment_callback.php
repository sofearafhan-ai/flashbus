<?php

require_once 'db.php';

/* =========================
   TOYYIBPAY CALLBACK
   ToyyibPay akan POST data ni ke sini secara
   server-to-server bila status bill berubah.
========================= */

$billCode = $_POST['billcode'] ?? '';
$statusId = $_POST['status_id'] ?? ''; // 1 = success, 2 = pending, 3 = fail
$refNo    = $_POST['order_id'] ?? '';  // ni booking_code kita (billExternalReferenceNo)

if(empty($billCode)){
    http_response_code(400);
    exit('Missing billcode');
}


/* =========================
   MAP STATUS
========================= */

// payment_status ENUM('pending','success','failed')
// booking_status ENUM('Pending','Confirmed','Cancelled')

if($statusId == 1){
    $paymentStatus = 'success';
    $bookingStatus = 'Confirmed';
} elseif($statusId == 3){
    $paymentStatus = 'failed';
    $bookingStatus = 'Cancelled';
} else {
    $paymentStatus = 'pending';
    $bookingStatus = 'Pending';
}


/* =========================
   UPDATE PAYMENT
========================= */

$stmt = $conn->prepare("
    UPDATE payment
    SET payment_status = ?, payment_date = NOW()
    WHERE toyyibpay_billcode = ?
");
$stmt->bind_param("ss", $paymentStatus, $billCode);
$stmt->execute();


/* =========================
   UPDATE BOOKING
   (guna bookings_id dari payment record yg match billcode)
========================= */

$stmt2 = $conn->prepare("
    UPDATE bookings b
    JOIN payment p ON p.bookings_id = b.bookings_id
    SET b.booking_status = ?
    WHERE p.toyyibpay_billcode = ?
");
$stmt2->bind_param("ss", $bookingStatus, $billCode);
$stmt2->execute();


/* =========================
   RESPONSE
   ToyyibPay tak expect apa-apa specific,
   just pastikan return 200 OK
========================= */

http_response_code(200);
echo "OK";

?>