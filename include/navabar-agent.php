<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection

session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

$name = '';

$stmt = $conn->prepare('SELECT full_name FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

// Take only first name from full_name
if (!empty($name)) {
    $name = explode(' ', trim($name))[0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body,
        * {
            font-family: "Lora", serif;
        }
    </style>
</head>

<body class="bg-white">
    <header class="sticky top-0 z-50 bg-white border-b shadow-sm">
        <nav class="w-full">
            <div class="flex items-center justify-between px-6 py-2">
                <!-- Logo -->
                <a class="flex items-center flex-shrink-0" href="../Home/landing.php">
                    <img src="../assets/Logo/logo.png" alt="Logo" class="h-14 w-14 object-contain">
                </a>

                <!-- Navigation Links -->
                <div class="flex-1 flex justify-center">
                    <ul class="flex space-x-6 items-center">
                        <li>
                            <a class="text-green-700 font-semibold hover:text-green-600" href="../Agent/profile.php">Agent Profile</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/farmers.php">Farmer under Agent</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/payment.php">Payment Info</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/add-farmer.php">Add Farmer</a>
                        </li>
                    </ul>
                </div>

                <!-- Login / Logout Section -->
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <a href="../Login-Signup/profile.php" class="text-black hover:text-green-600 text-sm font-semibold">
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <span>|</span>
                        <a href="../Login-Signup/logout.php" class="text-black hover:text-green-600 text-sm font-semibold">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <a href="../Login-Signup/login.php" class="text-black hover:text-green-600 text-sm font-semibold">Login</a>
                        <span>|</span>
                        <a href="../Login-Signup/signup.php" class="text-black hover:text-green-600 text-sm font-semibold">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>
</body>

</html>