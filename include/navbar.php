<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection

session_start();

$name = '';
$role = ''; // role: admin, agent, shop-owner, user

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare('SELECT full_name, role FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $role);
    $stmt->fetch();
    $stmt->close();

    // Take only first name
    if (!empty($name)) {
        $name = explode(' ', trim($name))[0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&display=swap" rel="stylesheet">

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
                        <?php if ($role === 'Admin'): ?>
                            <!-- ADMIN NAV -->
                            <li><a class="text-green-700 font-semibold hover:text-green-600" href="../Admin/dashboard.php">Dashboard</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Admin/products.php">Products</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Admin/orders.php">Orders</a></li>

                            <!-- Agents Dropdown -->
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#" class="text-gray-700 hover:text-green-700 font-semibold flex items-center">
                                    Agents
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition
                                    class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border"
                                    style="display:none;">
                                    <a href="../Admin/agents.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Agents</a>
                                    <a href="../Admin/agents-requests.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Agents Requests</a>
                                </div>
                            </li>

                            <!-- Stock Dropdown -->
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#" class="text-gray-700 hover:text-green-700 font-semibold flex items-center">
                                    Stock
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition
                                    class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border"
                                    style="display:none;">
                                    <a href="../Admin/inventory-request.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Inventory Request</a>
                                    <a href="../Admin/stock-request.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Stock Request</a>
                                </div>
                            </li>

                            <!-- Warehouse Dropdown -->
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#" class="text-gray-700 hover:text-green-700 font-semibold flex items-center">
                                    Warehouse Management
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition
                                    class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border"
                                    style="display:none;">
                                    <a href="../Admin/manage-warehouse.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Manage Warehouse</a>
                                    <a href="../Admin/add-warehouse.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Add New Warehouse</a>
                                    <a href="../Admin/warehouse-products.php" class="block px-6 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700">Warehouse Products</a>
                                </div>
                            </li>

                        <?php elseif ($role === 'Agent'): ?>
                            <!-- AGENT NAV -->
                            <li><a class="text-green-700 font-semibold hover:text-green-600" href="../Agent/profile.php">Agent Profile</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/farmers.php">Farmer under Agent</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/payment.php">Payment Info</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Agent/add-farmer.php">Add Farmer</a></li>

                        <?php elseif ($role === 'Shop-Owner'): ?>
                            <!-- SHOP OWNER (BUYER) NAV -->
                            <li><a class="text-green-700 font-semibold hover:text-green-600" href="../Buyer/buy-products.php">Buy Products</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Buyer/self-service-orders.php">Self-Service Orders</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Buyer/dashboard.php">Dashboard</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Buyer/demand-forecasting.php">Demand Forecasting</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Buyer/home.php">Home (Available Products)</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../Buyer/request-products.php">Request Products</a></li>

                        <?php else: ?>
                            <!-- REGULAR USER NAV -->
                            <li><a class="text-green-700 font-semibold hover:text-green-600" href="../User/update-profile.php">Update Profile</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../User/dashboard.php">Dashboard</a></li>
                            <li><a class="text-gray-700 hover:text-green-700 font-semibold" href="../User/about-us.php">About Us</a></li>
                        <?php endif; ?>
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