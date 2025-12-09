<?php

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = '';
$phone = '';
$user_id = '';
$verify = '';
$verification_success = false;
$verification_error = false;

if (isset($_GET['user_id']) && isset($_GET['verify'])) {
    $user_id = urldecode($_GET['user_id']);
    $verify = $_GET['verify'];

    if ($verify === 'email') {

        // Fetch email using user_id
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $stmt->bind_result($email);
        $stmt->fetch();

        $stmt->close();
    } elseif ($verify === 'phone') {

        // Fetch phone using user_id
        $stmt = $conn->prepare("SELECT phone FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $stmt->bind_result($phone);
        $stmt->fetch();

        $stmt->close();
    }
}


if (isset($_POST['verify_account'])) {

    $code_input = $_POST['code'];
    $user_id = $_POST['user_id'];
    $verify = $_POST['verify'];

    // Email verification Process..................
    if ($verify === 'email') {

        // Fetch email using user_id for POST request
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();

        // Get verification code from DB
        $stmt = $conn->prepare("SELECT email_verification_code FROM email_phone_verification WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && $result['email_verification_code'] == $code_input) {

            $update = $conn->prepare("UPDATE email_phone_verification SET email_is_verified = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            $update = $conn->prepare("UPDATE users SET is_valid_email = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            $verification_success = true;
        } else {
            $verification_error = true;
        }
    }
    // Phone verification Process..................
    elseif ($verify === 'phone') {

        // Fetch phone using user_id for POST request
        $stmt = $conn->prepare("SELECT phone FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($phone);
        $stmt->fetch();
        $stmt->close();

        // Get verification code from DB
        $stmt = $conn->prepare("SELECT phone_verification_code FROM email_phone_verification WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && $result['phone_verification_code'] == $code_input) {

            $update = $conn->prepare("UPDATE email_phone_verification SET phone_is_verified = 1 WHERE phone = ?");
            $update->bind_param("s", $phone);
            $update->execute();
            $update->close();

            $update = $conn->prepare("UPDATE users SET is_valid_phone = 1 WHERE phone = ?");
            $update->bind_param("s", $phone);
            $update->execute();
            $update->close();

            // Start session and redirect to profile
            session_start();
            $_SESSION['user_id'] = $user_id;

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
    <title>Email Verification - DirectEdge</title>
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

                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>" />
                    <input type="hidden" name="verify" value="<?php echo htmlspecialchars($verify); ?>" />

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

                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>" />
                    <input type="hidden" name="verify" value="<?php echo htmlspecialchars($verify); ?>" />

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