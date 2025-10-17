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
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="..\assets\Logo\LogoBG.png">
    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-custom">
    <div class="sidebar open m-0">
        <ul class="nav-list p-0">
            <li>
                <a href="/../warehouse-app/admin-dashboard/admin-dashboard.php"
                    class="<?php echo ($current_page == 'admin-dashboard.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="/../warehouse-app/warehouse information/product-list.php"
                    class="<?php echo ($current_page == 'products.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-box'></i>
                    <span class="links_name">Products</span>
                </a>
                <span class="tooltip">Products</span>
            </li>
            <li>
                <a href="/../warehouse-app/Orders/orders.php"
                    class="<?php echo ($current_page == 'orders.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-cart'></i>
                    <span class="links_name">Orders</span>
                </a>
                <span class="tooltip">Orders</span>
            </li>
            <li>
                <a href="/../warehouse-app/agent/all-agents.php"
                    class="<?php echo ($current_page == 'agents.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-user'></i>
                    <span class="links_name">Agents</span>
                </a>
                <span class="tooltip">Agents</span>
            </li>
            <li>
                <a href="/../warehouse-app/admin-dashboard/admin-agent-management.php"
                    class="<?php echo ($current_page == 'agents-requests.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-user-check'></i>
                    <span class="links_name">Agents Requests</span>
                </a>
                <span class="tooltip">Agents Requests</span>
            </li>
            <li>
                <a href="/../warehouse-app/All-Inventory-Requests/all-inventory-requests.php"
                    class="<?php echo ($current_page == 'inventory-request.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-package'></i>
                    <span class="links_name">Inventory Request</span>
                </a>
                <span class="tooltip">Inventory Request</span>
            </li>
            <li>
                <a href="/../warehouse-app/stock-request/stock-request.php"
                    class="<?php echo ($current_page == 'stock-request.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-cube'></i>
                    <span class="links_name">Stock Request</span>
                </a>
                <span class="tooltip">Stock Request</span>
            </li>
            <li>
                <a href="/../warehouse-app/Manage-Warehouse/manage_warehouse.php"
                    class="<?php echo ($current_page == 'manage-warehouse.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-buildings'></i>
                    <span class="links_name">Manage Warehouse</span>
                </a>
                <span class="tooltip">Manage Warehouse</span>
            </li>
            <li>
                <a href="/../warehouse-app/add-warehouse.php"
                    class="<?php echo ($current_page == 'add-warehouse.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-building-house'></i>
                    <span class="links_name">Add New Warehouse</span>
                </a>
                <span class="tooltip">Add New Warehouse</span>
            </li>
            <li>
                <a href="/../warehouse-app/warehouse information/warehouse-info.php"
                    class="<?php echo ($current_page == 'warehouse-info.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bxs-box'></i>
                    <span class="links_name">Warehouse Products</span>
                </a>
                <span class="tooltip">Warehouse Products</span>
            </li>
            <li class="profile">
                <?php $profileHref = '/../Login-Signup/login.php'; ?>
                <a href="/../warehouse-app/admin-profile.php">
                    <img src="<?php echo !empty($image_url) ? htmlspecialchars("../" . $image_url) : 'https://www.svgrepo.com/show/23012/profile-user.svg'; ?>" alt="Profile Image">
                    <div class="name_job">
                        <div class="name"><?php echo htmlspecialchars($name ?: 'Guest'); ?></div>
                        <div class="job"><?php echo ($role === 'Agent') ? 'Agent' : ''; ?></div>
                    </div>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/../Login-Signup/logout.php">
                        <i class='bx bx-log-out' id="log_out"></i>
                    </a>
                <?php else: ?>
                    <a href="../Login-Signup/login.php">
                        <i class='bx bx-log-in' id="log_in"></i>
                    </a>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    <script>
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");

        closeBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
            menuBtnChange();
        });

        function menuBtnChange() {
            if (sidebar.classList.contains("open")) {
                closeBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            } else {
                closeBtn.classList.replace("bx-menu-alt-right", "bx-menu");
            }
        }
    </script>
</body>

</html>