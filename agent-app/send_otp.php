<?php
// send_otp.php
session_start();
include '../include/connect-db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer_id = intval($_POST['farmer_id']);

    // Fetch Farmer Phone
    $stmt = $conn->prepare("SELECT contact_number FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $phone = $row['contact_number'];
        
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Save in Session for verification
        $_SESSION['current_otp'] = $otp;
        $_SESSION['otp_farmer_id'] = $farmer_id;
        $_SESSION['otp_verified'] = false; // Reset verification status

        // TODO: Integrate SMS Gateway here
        // Example: send_sms($phone, "Your DirectEdge Agreement OTP is: $otp");
        
        // For Testing: We are sending OTP in response (Remove this in production!)
        echo json_encode([
            'success' => true, 
            'message' => "OTP sent to " . substr($phone, 0, 3) . "****" . substr($phone, -2) . ". (Test OTP: $otp)"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Farmer not found']);
    }
}
?>