<?php

require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

requireLogin();

// =========================
// TOYYIBPAY CONFIG
// =========================
$toyyibSecretKey    = '79p2wpgf-1cun-zukl-vidj-o41m9fcq661r';   // <-- letak secret key sandbox korang
$toyyibCategoryCode = 'orgk749z';        // <-- letak category code sandbox korang
$toyyibBaseUrl      = 'https://dev.toyyibpay.com';

// Tukar domain ni ikut setup korang (ngrok URL kalau nak test callback)
$appBaseUrl = 'http://localhost/flashbus_db';


try{

    /* =========================
       GET FORM DATA
    ========================= */

    $payment_method = $_POST['payment_method'] ?? '';
    $bank_name      = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_name   = $_POST['account_name'] ?? '';

    $schedules_id = (int)($_POST['schedules_id'] ?? 0);

    $seat_ids = $_POST['seat_id'] ?? [];
    $names    = $_POST['passenger_name'] ?? [];
    $genders  = $_POST['passenger_gender'] ?? [];
    $phones   = $_POST['phone'] ?? [];


    if(
        empty($payment_method) ||
        empty($seat_ids)
    ){
        echo json_encode([
            "success"=>false,
            "message"=>"Payment information incomplete"
        ]);
        exit;
    }


    /* =========================
       GET USER SESSION
    ========================= */

    $user_id = $_SESSION['users_id'] ?? 0;

    if($user_id == 0){
        echo json_encode([
            "success"=>false,
            "message"=>"User session not found. Please login again."
        ]);
        exit;
    }


    /* =========================
       GET USER INFO (for ToyyibPay bill)
    ========================= */

    $stmtUser = $conn->prepare("
        SELECT fullname, email, phone
        FROM users
        WHERE users_id = ?
    ");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $userInfo = $stmtUser->get_result()->fetch_assoc();


    /* =========================
       GET SCHEDULE PRICE
    ========================= */

    $stmt=$conn->prepare("
        SELECT price
        FROM schedules
        WHERE schedules_id=?
    ");

    $stmt->bind_param("i", $schedules_id);
    $stmt->execute();

    $schedule=$stmt->get_result()->fetch_assoc();

    if(!$schedule){
        throw new Exception("Schedule not found");
    }

    $total_amount = $schedule['price'] * count($seat_ids);


    /* =========================
       START TRANSACTION
    ========================= */

    $conn->begin_transaction();


    /* =========================
       INSERT BOOKING (PENDING)
    ========================= */

    $booking_code   = "BK".date("YmdHis");
    $booking_status = "Pending"; // matches ENUM('Pending','Confirmed','Cancelled')

    $stmt=$conn->prepare("
        INSERT INTO bookings
        (booking_code, users_id, schedules_id, total_amount, booking_status)
        VALUES (?,?,?,?,?)
    ");

    $stmt->bind_param(
        "siids",
        $booking_code,
        $user_id,
        $schedules_id,
        $total_amount,
        $booking_status
    );

    if(!$stmt->execute()){
        throw new Exception("Booking Error: ".$stmt->error);
    }

    $booking_id=$conn->insert_id;


    /* =========================
       INSERT PASSENGERS
       + BOOKING SEATS
    ========================= */

    for($i=0;$i<count($seat_ids);$i++){

        $seat_id = (int)$seat_ids[$i];
        $name    = $names[$i] ?? '';
        $gender  = $genders[$i] ?? '';
        $phone   = $phones[$i] ?? '';


        /* ---- INSERT PASSENGERS ---- */
        $stmt2=$conn->prepare("
            INSERT INTO passengers
            (bookings_id, seats_id, passenger_name, gender, phone)
            VALUES (?,?,?,?,?)
        ");

        $stmt2->bind_param(
            "iisss",
            $booking_id,
            $seat_id,
            $name,
            $gender,
            $phone
        );

        if(!$stmt2->execute()){
            throw new Exception("Passenger Error: ".$stmt2->error);
        }


        /* ---- INSERT BOOKING SEATS ---- */
        $stmtSeat=$conn->prepare("
            INSERT INTO booking_seats
            (bookings_id, schedules_id, seats_id, passenger_name, passenger_gender)
            VALUES (?,?,?,?,?)
        ");

        $stmtSeat->bind_param(
            "iiiss",
            $booking_id,
            $schedules_id,
            $seat_id,
            $name,
            $gender
        );

        if(!$stmtSeat->execute()){
            throw new Exception("Booking Seat Error: ".$stmtSeat->error);
        }
    }


    /* =========================
       INSERT PAYMENT (PENDING)
    ========================= */

    $payment_status = "pending"; // matches ENUM('pending','success','failed')

    $stmt3=$conn->prepare("
        INSERT INTO payment
        (bookings_id, payment_method, bank_name, account_number, account_name, payment_status)
        VALUES (?,?,?,?,?,?)
    ");

    $stmt3->bind_param(
        "isssss",
        $booking_id,
        $payment_method,
        $bank_name,
        $account_number,
        $account_name,
        $payment_status
    );

    if(!$stmt3->execute()){
        throw new Exception("Payment Error: ".$stmt3->error);
    }


    /* =========================
       CREATE TOYYIBPAY BILL
    ========================= */

    $billData = [
        'userSecretKey'           => $toyyibSecretKey,
        'categoryCode'            => $toyyibCategoryCode,
         'billName'        => 'FlashBus '.$booking_code, // Cth: "FlashBus BK20260717012235" (25 aksara)
         'billDescription' => 'Bus ticket payment for booking '.$booking_code, // Yang ini biarkan, sebab description boleh panjang
        'billPriceSetting'        => 1,
        'billPayorInfo'           => 1,
        'billAmount'              => (int)round($total_amount * 100), // dalam sen
        'billReturnUrl'           => $appBaseUrl.'/payment_return.php',
        'billCallbackUrl'         => $appBaseUrl.'/payment_callback.php',
        'billExternalReferenceNo' => $booking_code,
        'billTo'                  => $userInfo['fullname'] ?? 'Guest',
        'billEmail'               => $userInfo['email'] ?? 'guest@example.com',
        'billPhone'               => $userInfo['phone'] ?? '0100000000',
        'billSplitPayment'        => 0,
        'billPaymentChannel'      => 0, // 0 = FPX + Card
    ];

    $ch = curl_init($toyyibBaseUrl.'/index.php/api/createBill');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($billData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // sandbox/localhost testing only

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if($curlError){
        throw new Exception("ToyyibPay connection error: ".$curlError);
    }

    $result = json_decode($response, true);

    if(!isset($result[0]['BillCode'])){
        throw new Exception("Failed to create ToyyibPay bill. Response: ".$response);
    }

    $billCode = $result[0]['BillCode'];


    /* Simpan billcode dalam table payment untuk trace masa callback */
    $stmt4 = $conn->prepare("
        UPDATE payment
        SET toyyibpay_billcode = ?
        WHERE bookings_id = ?
    ");
    $stmt4->bind_param("si", $billCode, $booking_id);
    $stmt4->execute();


    /* =========================
       COMPLETE
    ========================= */

    $conn->commit();

    echo json_encode([
        "success"    => true,
        "message"    => "Redirecting to payment gateway",
        "booking_id" => $booking_id,
        "payment_url"=> $toyyibBaseUrl.'/'.$billCode
    ]);

}

catch(Exception $e){

    $conn->rollback();

    echo json_encode([
        "success"=>false,
        "message"=>$e->getMessage()
    ]);

}

?>