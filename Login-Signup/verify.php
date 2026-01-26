<?php
session_start();

// Check if user has pending verification
if (!isset($_SESSION['pending_verification_user_id'])) {
    header("Location: signup.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user info from session
$user_id = $_SESSION['pending_verification_user_id'];
$email = isset($_SESSION['pending_verification_email']) ? $_SESSION['pending_verification_email'] : '';
$phone = isset($_SESSION['pending_verification_phone']) ? $_SESSION['pending_verification_phone'] : '';
$verify = isset($_SESSION['verification_type']) ? $_SESSION['verification_type'] : 'email';

$verification_success = false;
$verification_error = false;


if (isset($_POST['verify_account'])) {

    $code_input = $_POST['code'];
    $user_id = $_SESSION['pending_verification_user_id'];
    $verify = $_SESSION['verification_type'];

    // Email verification Process..................
    if ($verify === 'email') {

        $email = $_SESSION['pending_verification_email'];
        $stored_code = isset($_SESSION['email_verification_code']) ? $_SESSION['email_verification_code'] : '';

        // Verify code from session
        if ($stored_code && $stored_code == $code_input) {

            // Update users table to mark email as verified
            $update = $conn->prepare("UPDATE users SET is_valid_email = 1 WHERE user_id = ?");
            $update->bind_param("i", $user_id);
            $update->execute();
            $update->close();

            // Clear verification session data
            unset($_SESSION['pending_verification_user_id']);
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['pending_verification_phone']);
            unset($_SESSION['verification_type']);
            unset($_SESSION['email_verification_code']);
            unset($_SESSION['phone_verification_code']);

            $verification_success = true;
        } else {
            $verification_error = true;
        }
    }
    // Phone verification Process..................
    elseif ($verify === 'phone') {

        $phone = $_SESSION['pending_verification_phone'];
        $stored_code = isset($_SESSION['phone_verification_code']) ? $_SESSION['phone_verification_code'] : '';

        // Verify code from session
        if ($stored_code && $stored_code == $code_input) {

            // Update users table to mark phone as verified
            $update = $conn->prepare("UPDATE users SET is_valid_phone = 1 WHERE user_id = ?");
            $update->bind_param("i", $user_id);
            $update->execute();
            $update->close();

            // Set user session for logged in user
            $_SESSION['user_id'] = $user_id;

            // Clear verification session data
            unset($_SESSION['pending_verification_user_id']);
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['pending_verification_phone']);
            unset($_SESSION['verification_type']);
            unset($_SESSION['email_verification_code']);
            unset($_SESSION['phone_verification_code']);

            $verification_success = true;
        } else {
            $verification_error = true;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo ($verify === 'phone') ? 'Phone' : 'Email'; ?> Verification - DirectEdge</title>
    <link rel="icon" type="image/x-icon" href="../assets/Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-success {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: rgb(22, 163, 74);
            color: white;
            text-align: center;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-success:hover {
            background-color: rgb(21, 128, 61);
        }
    </style>
</head>

<body class="bg-[#eff2f9] flex items-center justify-center min-h-screen">

    <?php
    if ($verify === 'email' && $email) {
    ?>

        <div class="bg-white shadow rounded-lg p-6 md:p-10 w-full max-w-md">
            <h2 class="text-2xl font-bold text-center mb-2">Verify Your Email</h2>
            <p class="text-center text-gray-600 text-sm mb-6">Enter the verification code sent to your email</p>

            <?php if ($verification_success): ?>
                <div class="alert alert-success">
                    ✓ Email verified successfully!
                </div>
                <a href="login.php" class="btn-success">Go to Login Page</a>

            <?php elseif ($verification_error): ?>
                <div class="alert alert-error">
                    ✗ Invalid verification code. Please try again.
                </div>
            <?php endif; ?>

            <?php if (!$verification_success): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                    <p class="text-blue-900 text-sm">📧 Check your email inbox for the 6-digit verification code sent to
                        <?php
                        function maskEmail($email)
                        {
                            // Split email into name and domain
                            list($name, $domain) = explode('@', $email);

                            // First 3 characters of the name
                            $start = substr($name, 0, 3);

                            // Last 5 characters of the domain
                            $end = substr($domain, -5);

                            // Mask everything in-between
                            return $start . str_repeat('*', 8) . $end;
                        }

                        echo htmlspecialchars(' ' . maskEmail($email));
                        ?>
                    </p>
                </div>

                <form method="POST" action="verify.php" class="space-y-4">

                    <div>
                        <label for="code" class="block text-sm font-medium mb-1">Verification Code</label>
                        <input type="text" id="code" name="code" placeholder="Enter 6-digit code" required maxlength="6"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none" />
                    </div>

                    <button type="submit" name="verify_account"
                        class="w-full bg-green-600 text-white font-medium py-2 rounded-md hover:bg-green-700 transition">
                        Verify Account
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-4">
                    Already verified?
                    <a href="login.php" class="text-green-600 hover:underline">Log in</a>
                </p>
            <?php endif; ?>
        </div>

    <?php
    } elseif ($verify === 'phone' && $phone) {
    ?>

        <div class="bg-white shadow rounded-lg p-6 md:p-10 w-full max-w-md">
            <h2 class="text-2xl font-bold text-center mb-2">Verify Your Phone Number</h2>
            <p class="text-center text-gray-600 text-sm mb-6">Enter the verification code sent to your phone</p>

            <?php if ($verification_success): ?>
                <div class="alert alert-success">
                    ✓ Phone number verified successfully!
                </div>
                <a href="profile.php" class="btn-success">Go to Profile Page</a>
                <script>
                    // Auto-redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'profile.php';
                    }, 2000);
                </script>

            <?php elseif ($verification_error): ?>
                <div class="alert alert-error">
                    ✗ Invalid verification code. Please try again.
                </div>
            <?php endif; ?>

            <?php if (!$verification_success): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                    <p class="text-blue-900 text-sm">📱 Check your phone for the 6-digit verification code sent to
                        <?php
                        function maskPhone($phone)
                        {
                            // Mask middle digits of phone number
                            $length = strlen($phone);
                            if ($length > 6) {
                                $start = substr($phone, 0, 3);
                                $end = substr($phone, -3);
                                return $start . str_repeat('*', $length - 6) . $end;
                            }
                            return $phone;
                        }

                        echo htmlspecialchars(' ' . maskPhone($phone));
                        ?>
                    </p>
                </div>

                <form method="POST" action="verify.php" class="space-y-4">

                    <div>
                        <label for="code" class="block text-sm font-medium mb-1">Verification Code</label>
                        <input type="text" id="code" name="code" placeholder="Enter 6-digit code" required maxlength="6"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none" />
                    </div>

                    <button type="submit" name="verify_account"
                        class="w-full bg-green-600 text-white font-medium py-2 rounded-md hover:bg-green-700 transition">
                        Verify Phone Number
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-4">
                    Already verified?
                    <a href="profile.php" class="text-green-600 hover:underline">Go to Profile</a>
                </p>
            <?php endif; ?>
        </div>

    <?php
    } else {
        echo '<div class="bg-white shadow rounded-lg p-6 md:p-10 w-full max-w-md text-center">';
        echo '<p class="text-red-600 font-medium">Invalid verification link. Please try again.</p>';
        echo '<a href="signup.php" class="text-green-600 hover:underline mt-4 inline-block">Go to Sign Up</a>';
        echo '</div>';
    }
    ?>

</body>

</html>