<?php
// SidebarWarehouse.php
include __DIR__ . '/../include/connect-db.php'; // Database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = '';
$role = ''; // role: admin, agent, shop-owner, user
$image_url = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare('SELECT full_name, role, image_url FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $role, $image_url);
    $stmt->fetch();
    $stmt->close();

    if (!empty($name)) {
        $name = explode(' ', trim($name))[0];
    }
}

// Get current filename
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function to add classes
function isActive($page, $current_page)
{
    return $current_page == $page ? 'bg-white text-black' : 'text-gray-700 hover:bg-gray-100';
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/Logo/LogoBG.png">
    <link rel="icon" type="image/png" href="..\assets\Logo\LogoBG.png">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-custom">
    <div class="sidebar open m-0">
        <ul class="nav-list p-0">
            <li>
                <a href="/../warehouse-app/admin-dashboard/admin-dashboard.php" class="<?php echo isActive('admin-dashboard.php', $current_page); ?>">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="/../warehouse-app/warehouse information/product-list.php" class="<?php echo isActive('product-list.php', $current_page); ?>">
                    <i class='bx bx-box'></i>
                    <span class="links_name">Products</span>
                </a>
                <span class="tooltip">Products</span>
            </li>
            <li>
                <a href="/../warehouse-app/Orders/orders.php" class="<?php echo isActive('orders.php', $current_page); ?>">
                    <i class='bx bx-cart'></i>
                    <span class="links_name">Orders</span>
                </a>
                <span class="tooltip">Orders</span>
            </li>
            <li>
                <a href="/../warehouse-app/agent/all-agents.php" class="<?php echo isActive('all-agents.php', $current_page); ?>">
                    <i class='bx bx-user'></i>
                    <span class="links_name">Agents</span>
                </a>
                <span class="tooltip">Agents</span>
            </li>
            <li>
                <a href="/../warehouse-app/admin-dashboard/admin-agent-management.php" class="<?php echo isActive('admin-agent-management.php', $current_page); ?>">
                    <i class='bx bx-user-check'></i>
                    <span class="links_name">Agent Management</span>
                </a>
                <span class="tooltip">Agent Management </span>
            </li>
            <li>
                <a href="/../warehouse-app/All-Inventory-Requests/all-inventory-requests.php" class="<?php echo isActive('all-inventory-requests.php', $current_page); ?>">
                    <i class='bx bx-package'></i>
                    <span class="links_name">Inventory Request</span>
                </a>
                <span class="tooltip">Inventory Request</span>
            </li>
            <li>
                <a href="/../warehouse-app/stock-request/stock-request.php" class="<?php echo isActive('stock-request.php', $current_page); ?>">
                    <i class='bx bx-cube'></i>
                    <span class="links_name">Stock Request</span>
                </a>
                <span class="tooltip">Stock Request</span>
            </li>
            <li>
                <a href="/../warehouse-app/Manage-Warehouse/warehouse-status.php" class="<?php echo isActive('manage_warehouse.php', $current_page); ?>">
                    <i class='bx bx-buildings'></i>
                    <span class="links_name">Manage Warehouse</span>
                </a>
                <span class="tooltip">Manage Warehouse</span>
            </li>
            <li>
                <a href="/../warehouse-app/add-warehouse.php" class="<?php echo isActive('add-warehouse.php', $current_page); ?>">
                    <i class='bx bx-building-house'></i>
                    <span class="links_name">Add New Warehouse</span>
                </a>
                <span class="tooltip">Add New Warehouse</span>
            </li>
            <li>
                <a href="/../warehouse-app/warehouse information/warehouse-info.php" class="<?php echo isActive('warehouse-info.php', $current_page); ?>">
                    <i class='bx bxs-box'></i>
                    <span class="links_name">Warehouse Products</span>
                </a>
                <span class="tooltip">Warehouse Products</span>
            </li>

            <!-- Profile Section -->
            <li class="profile">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/../warehouse-app/admin-profile.php">
                        <img src="<?php echo !empty($image_url) ? htmlspecialchars("../" . $image_url) : 'https://www.svgrepo.com/show/23012/profile-user.svg'; ?>" alt="Profile Image">
                        <div class="name_job">
                            <div class="name"><?php echo htmlspecialchars($name ?: 'Guest'); ?></div>
                            <div class="job"><?php echo ($role === 'Agent') ? 'Agent' : ''; ?></div>
                        </div>
                    </a>
                    <a href="/../Login-Signup/logout.php">
                        <i class='bx bx-log-out' id="log_out"></i>
                    </a>
                <?php else: ?>
                    <a href="/../Login-Signup/login.php">
                        <i class='bx bx-log-in' id="log_in"></i>
                        <span class="links_name">Login</span>
                    </a>
                <?php endif; ?>
            </li>
        </ul>
    </div>

    <script>
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");

        closeBtn?.addEventListener("click", () => {
            sidebar.classList.toggle("open");
        });
    </script>
</body>

</html>