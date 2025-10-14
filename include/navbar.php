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

    if (!empty($name)) {
        $name = explode(' ', trim($name))[0];
    }

    // If role is 'Agent', fetch status
    if ($role === 'Agent') {
        $status = '';
        $agentStmt = $conn->prepare('SELECT status FROM agent_info WHERE agent_id = ? LIMIT 1');
        $agentStmt->bind_param('i', $user_id);
        $agentStmt->execute();
        $agentStmt->bind_result($status);
        $agentStmt->fetch();
        $agentStmt->close();
    }
}

// Get current filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>User Dashboard</title> -->

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
                    <img src="../assets/Logo/Favicon.png" alt="Logo" class="h-14 w-14 object-contain">
                </a>

                <!-- Navigation Links -->
                <div class="flex-1 flex justify-center">
                    <ul class="flex space-x-6 items-center">
                        <?php if ($role === 'Admin'): ?>
                            <!-- ADMIN NAV -->
                            <li>
                                <a href="../Admin/dashboard.php"
                                    class="font-semibold <?php echo ($current_page == 'dashboard.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="../Admin/products.php"
                                    class="font-semibold <?php echo ($current_page == 'products.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Products
                                </a>
                            </li>
                            <li>
                                <a href="../Admin/orders.php"
                                    class="font-semibold <?php echo ($current_page == 'orders.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Orders
                                </a>
                            </li>

                            <!-- Agents Dropdown -->
                            <?php $is_agents_section = in_array($current_page, ['agents.php', 'agents-requests.php']); ?>
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#"
                                    class="font-semibold flex items-center <?php echo $is_agents_section ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Agents
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border min-w-[260px]" style="display:none;">
                                    <a href="../Admin/agents.php"
                                        class="block px-8 py-3 font-semibold <?php echo ($current_page == 'agents.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Agents
                                    </a>
                                    <a href="../Admin/agents-requests.php"
                                        class="block px-8 py-3 font-semibold <?php echo ($current_page == 'agents-requests.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Agents Requests
                                    </a>
                                </div>
                            </li>

                            <!-- Stock Dropdown -->
                            <?php $is_stock_section = in_array($current_page, ['inventory-request.php', 'stock-request.php']); ?>
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#"
                                    class="font-semibold flex items-center <?php echo $is_stock_section ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Stock
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border min-w-[220px]" style="display:none;">
                                    <a href="../Admin/inventory-request.php"
                                        class="block px-8 py-3 font-semibold <?php echo ($current_page == 'inventory-request.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Inventory Request
                                    </a>
                                    <a href="../Admin/stock-request.php"
                                        class="block px-8 py-3 font-semibold <?php echo ($current_page == 'stock-request.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Stock Request
                                    </a>
                                </div>
                            </li>

                            <!-- Warehouse Dropdown -->
                            <?php $is_warehouse_section = in_array($current_page, ['manage-warehouse.php', 'add-warehouse.php', 'warehouse-products.php']); ?>
                            <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="#"
                                    class="font-semibold flex items-center <?php echo $is_warehouse_section ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">
                                    Warehouse Management
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </a>
                                <div x-show="open" x-transition class="absolute top-full left-0 bg-white shadow-md rounded mt-2 py-2 border" style="display:none;">
                                    <a href="../Admin/manage-warehouse.php"
                                        class="block px-6 py-2 font-semibold <?php echo ($current_page == 'manage-warehouse.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Manage Warehouse
                                    </a>
                                    <a href="../Admin/add-warehouse.php"
                                        class="block px-6 py-2 font-semibold <?php echo ($current_page == 'add-warehouse.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Add New Warehouse
                                    </a>
                                    <a href="../Admin/warehouse-products.php"
                                        class="block px-6 py-2 font-semibold <?php echo ($current_page == 'warehouse-products.php') ? 'text-green-700 bg-green-50' : 'text-gray-700 hover:bg-green-50 hover:text-green-700'; ?>">
                                        Warehouse Products
                                    </a>
                                </div>
                            </li>

                        <?php elseif ($role === 'Agent'):
                            $isPending = ($role === 'Agent' && $status === 'Pending');
                            $linkClass = $isPending ? 'text-gray-500 cursor-not-allowed pointer-events-none' : 'text-gray-700 hover:text-green-700';
                            $activeClass = $isPending ? 'text-gray-500' : 'text-green-700';
                            $hrefAttr = $isPending ? '' : 'href="';
                        ?>
                            <!-- AGENT NAV -->
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/agent-farmer-dashboard.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'agent-farmer-dashboard.php') ? 'text-green-700' : $linkClass; ?>">
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/farmers-list.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'farmers-list.php') ? 'text-green-700' : $linkClass; ?>">
                                    Farmer under Agent
                                </a>
                            </li>
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/payment-details.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'payment-details.php') ? 'text-green-700' : $linkClass; ?>">
                                    Payment Info
                                </a>
                            </li>
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/add-farmers-info.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'add-farmers-info.php') ? 'text-green-700' : $linkClass; ?>">
                                    Add Farmer
                                </a>
                            </li>
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/inventory-request.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'inventory-request.php') ? 'text-green-700' : $linkClass; ?>">
                                    Inventory Request
                                </a>
                            </li>
                            <li>
                                <a <?php echo $hrefAttr . '../agent-app/agent-profile.php"'; ?>
                                    class="font-semibold <?php echo ($current_page == 'agent-profile.php') ? 'text-green-700' : $linkClass; ?>">
                                    Profile
                                </a>
                            </li>

                        <?php elseif ($role === 'Shop-Owner'): ?>
                            <!-- SHOP OWNER NAV -->
                            <li><a href="../Buyer/buy-products.php"
                                    class="font-semibold <?php echo ($current_page == 'buy-products.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Buy Products</a></li>
                            <li><a href="../Buyer/self-service-orders.php"
                                    class="font-semibold <?php echo ($current_page == 'self-service-orders.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Self-Service Orders</a></li>
                            <li><a href="../Buyer/dashboard.php"
                                    class="font-semibold <?php echo ($current_page == 'dashboard.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Dashboard</a></li>
                            <li><a href="../Buyer/demand-forecasting.php"
                                    class="font-semibold <?php echo ($current_page == 'demand-forecasting.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Demand Forecasting</a></li>
                            <li><a href="../Buyer/home.php"
                                    class="font-semibold <?php echo ($current_page == 'home.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Home (Available Products)</a></li>
                            <li><a href="../Buyer/request-products.php"
                                    class="font-semibold <?php echo ($current_page == 'request-products.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Request Products</a></li>

                        <?php else: ?>
                            <!-- REGULAR USER NAV -->
                            <li><a href="../User/update-profile.php"
                                    class="font-semibold <?php echo ($current_page == 'update-profile.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Update Profile</a></li>
                            <li><a href="../User/dashboard.php"
                                    class="font-semibold <?php echo ($current_page == 'dashboard.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">Dashboard</a></li>
                            <li><a href="../User/about-us.php"
                                    class="font-semibold <?php echo ($current_page == 'about-us.php') ? 'text-green-700' : 'text-gray-700 hover:text-green-700'; ?>">About Us</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Login / Logout -->
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <a href="<?php echo ($role == 'Agent') ? '../agent-app/agent-profile.php' : '../Login-Signup/profile.php'; ?>"
                            class="text-black hover:text-green-600 text-sm font-semibold">
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