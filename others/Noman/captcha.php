<?php
// Your secret key from Google
$secretKey = "6LdNTSEsAAAAAPNc_OatPnrX3J12qWvkthB99Uc3";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];
    $captcha = $_POST["g-recaptcha-response"];

    if (!$captcha) {
        echo "<h3>Please complete the CAPTCHA.</h3>";
        exit;
    }

    // Verify CAPTCHA with Google
    $verifyResponse = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha"
    );
    $responseData = json_decode($verifyResponse);

    if ($responseData->success) {

        // CAPTCHA successful → save user (demo only)

        // In real project → insert into DB here
        // For demo → redirect to success page
        header("Location: success.php?email=" . urlencode($email));
        exit;
    } else {
        echo "<h3>CAPTCHA verification failed. Try again.</h3>";
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>Signup with Google reCAPTCHA</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        body {
            font-family: Arial;
            padding: 30px;
        }

        .container {
            width: 350px;
            margin: auto;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Create Account</h2>

        <form action="signup.php" method="POST">

            <input type="email" name="email" placeholder="Email" required>

            <input type="password" name="password" placeholder="Password" required>

            <!-- Google reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="6LdNTSEsAAAAAO7ovUcBYc5ZyWNTbyKrAMX5YtVn"></div>

            <button type="submit">Sign Up</button>
        </form>
    </div>
</body>

</html>