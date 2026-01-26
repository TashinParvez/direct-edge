<?php
session_start();

// Check if user has valid two-factor session
if (!isset($_SESSION['two_factor_user_id']) || !isset($_SESSION['two_factor_code'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/../include/connect-db.php';

$error = "";
$success = "";

// Handle verification code submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
    $entered_code = $_POST['verification_code'];
    $session_code = $_SESSION['two_factor_code'];

    if ($entered_code == $session_code) {
        // Verification successful
        $user_id = $_SESSION['two_factor_user_id'];

        // Fetch user details for redirect
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Clear two-factor session variables
        unset($_SESSION['two_factor_code']);
        unset($_SESSION['two_factor_user_id']);
        unset($_SESSION['two_factor_type']);

        // Set user session
        $_SESSION['user_id'] = $user_id;

        // Redirect based on role
        $redirect_page = '';
        if ($user['role'] == 'Agent') {
            $redirect_page = "../agent-app/agent-farmer-dashboard.php";
        } elseif ($user['role'] == 'Admin') {
            $redirect_page = "../warehouse-app/admin-dashboard/admin-dashboard.php";
        } elseif ($user['role'] == 'Shop-Owner') {
            header("Location: ../shop-owner-app/Profuct-for-buyers-from-shop/Available-Products-List.php?shop_id=" . $user_id);
            exit();
        } else {
            $redirect_page = "../Home/landing.php";
        }

        header("Location: " . $redirect_page);
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}

// Resend verification code
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_code'])) {
    $verification_code = rand(100000, 999999);
    $_SESSION['two_factor_code'] = $verification_code;
    $user_id = $_SESSION['two_factor_user_id'];
    $two_factor_type = $_SESSION['two_factor_type'];

    // Fetch user email/phone
    $stmt = $conn->prepare("SELECT email, phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    if ($two_factor_type === 'email') {
        // Resend email
        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';
        require 'PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mnoman221338@bscse.uiu.ac.bd';
            $mail->Password = 'rzjj ljkz lqwf qhzb';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('mnoman221338@bscse.uiu.ac.bd', 'DirectEdge');
            $mail->addAddress($user_data['email']);

            $mail->isHTML(true);
            $mail->Subject = 'Two-Factor Authentication Code';
            $mail->Body = "
                <h2>Two-Factor Authentication</h2>
                <p>Your verification code is: <strong style='font-size: 24px;'>{$verification_code}</strong></p>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't attempt to log in, please ignore this email.</p>
            ";

            $mail->send();
            $success = "Verification code sent to your email!";
        } catch (Exception $e) {
            $error = "Failed to send verification code. Please try again.";
        }
    } elseif ($two_factor_type === 'phone') {
        // Resend WhatsApp
        $idInstance = "7105402430";
        $apiTokenInstance = "2d8e63493c2b41b990db9e521c5f62fdb2f20c7be5e14cbd8d";
        $url = "https://api.greenapi.com/waInstance{$idInstance}/sendMessage/{$apiTokenInstance}";

        $formattedPhone = preg_replace('/^\+?88/', '', $user_data['phone']);
        $formattedPhone = '88' . $formattedPhone;

        $data = [
            "chatId" => $formattedPhone . "@c.us",
            "message" => "Your DirectEdge login verification code is: $verification_code\n\nThis code will expire in 10 minutes."
        ];

        $options = [
            "http" => [
                "header"  => "Content-type: application/json\r\n",
                "method"  => "POST",
                "content" => json_encode($data),
                "ignore_errors" => true
            ]
        ];

        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
        $success = "Verification code sent to your phone via WhatsApp!";
    }
}

$conn->close();

$two_factor_type = $_SESSION['two_factor_type'] ?? 'email';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - DirectEdge</title>
    <link rel="icon" type="image/x-icon" href="../assets/Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex justify-center items-center min-h-screen bg-[#eff2f9]">

    <div class="bg-white shadow-lg rounded-2xl p-8 w-[90%] max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Two-Factor Authentication</h2>
            <p class="text-sm text-gray-600 mt-2">
                We've sent a verification code to your
                <strong><?php echo $two_factor_type === 'email' ? 'email' : 'phone (WhatsApp)'; ?></strong>
            </p>
        </div>

        <!-- Display messages -->
        <?php if (!empty($error)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Verification Form -->
        <form action="" method="POST" class="space-y-6">
            <div>
                <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Enter Verification Code
                </label>
                <input type="text" id="verification_code" name="verification_code"
                    placeholder="000000" required maxlength="6" pattern="[0-9]{6}"
                    class="mt-1 block w-full px-4 py-3 text-center text-2xl tracking-widest border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    autocomplete="off">
                <p class="mt-2 text-xs text-gray-500">Enter the 6-digit code sent to your <?php echo $two_factor_type === 'email' ? 'email' : 'phone'; ?></p>
            </div>

            <button type="submit" name="verify_code"
                class="w-full bg-green-600 text-white font-medium py-3 rounded-md hover:bg-green-700 transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Verify and Log In
            </button>
        </form>

        <!-- Resend Code -->
        <div class="mt-6 text-center border-t border-gray-200 pt-6">
            <p class="text-sm text-gray-600 mb-3">Didn't receive the code?</p>
            <form method="POST" action="">
                <button type="submit" name="resend_code"
                    class="text-green-600 hover:text-green-700 font-medium text-sm hover:underline focus:outline-none">
                    Resend Verification Code
                </button>
            </form>
        </div>

        <!-- Cancel -->
        <div class="mt-4 text-center">
            <a href="login.php" class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Login
            </a>
        </div>
    </div>

    <script>
        // Auto-focus on verification code input
        document.getElementById('verification_code').focus();

        // Auto-submit when 6 digits are entered
        document.getElementById('verification_code').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                // Optional: auto-submit after 6 digits
                // document.querySelector('form').submit();
            }
        });

        // Only allow numbers
        document.getElementById('verification_code').addEventListener('keypress', function(e) {
            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
            }
        });
    </script>

</body>

</html>