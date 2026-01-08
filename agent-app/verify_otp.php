<?php
// verify_otp.php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_otp = $_POST['otp'];
    
    if (isset($_SESSION['current_otp']) && $user_otp == $_SESSION['current_otp']) {
        // Mark as verified
        $_SESSION['otp_verified'] = true;
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect OTP']);
    }
}
?>