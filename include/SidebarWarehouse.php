<!-- SidebarAdmin.php -->
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
        <!-- <div class="logo-details">
            <i class='bx bx-menu-alt-right' id="btn"></i>
        </div> -->
        <ul class="nav-list p-0">
            <li>
                <a href="../warehouse-app/admin-dashboard/admin-dashboard.php"
                    class="<?php echo ($current_page == 'admin-dashboard.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="../warehouse-app\warehouse information\product-list.php"
                    class="<?php echo ($current_page == 'products.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-box'></i>
                    <span class="links_name">Products</span>
                </a>
                <span class="tooltip">Products</span>
            </li>
            <li>
                <a href="../warehouse-app\Orders\orders.php"
                    class="<?php echo ($current_page == 'orders.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-cart'></i>
                    <span class="links_name">Orders</span>
                </a>
                <span class="tooltip">Orders</span>
            </li>
            <!-- Agents Section with Dropdown -->
            <li x-data="{ open: <?php echo in_array($current_page, ['agents.php', 'agents-requests.php']) ? 'true' : 'false'; ?> }"
                x-cloak>
                <div class="flex items-center justify-between cursor-pointer">
                    <a href="../warehouse-app\agent\all-agents.php"
                        class="<?php echo in_array($current_page, ['agents.php', 'agents-requests.php']) ? 'text-white bg-white' : ''; ?> flex items-center w-full text-left">
                        <i class='bx bx-user'></i>
                        <span class="links_name">Agents</span>
                    </a>
                    <button type="button" @click.prevent="open = !open" class="btn-toggle"
                        aria-label="Toggle Agents submenu">
                        <i class='bx bx-chevron-down' :class="{ 'bx-rotate-180': open }"></i>
                    </button>
                </div>
                <ul x-show="open" x-transition class="pl-6">
                    <li>
                        <a href="../Admin/agents-requests.php"
                            class="<?php echo ($current_page == 'agents-requests.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Agents Requests</span>
                        </a>
                    </li>
                </ul>
                <span class="tooltip">Agents</span>
            </li>
            <!-- Stock Section with Dropdown -->
            <li x-data="{ open: <?php echo in_array($current_page, ['inventory-request.php', 'stock-request.php']) ? 'true' : 'false'; ?> }"
                x-cloak>
                <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                    <button type="button"
                        class="<?php echo in_array($current_page, ['inventory-request.php', 'stock-request.php']) ? 'text-white bg-white' : ''; ?> flex items-center w-full text-left">
                        <i class='bx bx-package'></i>
                        <span class="links_name">Stock</span>
                    </button>
                    <i class='bx bx-chevron-down' :class="{ 'bx-rotate-180': open }"></i>
                </div>
                <ul x-show="open" x-transition class="pl-6">
                    <li>
                        <a href="../warehouse-app\All-Inventory-Requests\all-inventory-requests.php"
                            class="<?php echo ($current_page == 'inventory-request.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Inventory Request</span>
                        </a>
                    </li>
                    <li>
                        <a href="../Admin/stock-request.php"
                            class="<?php echo ($current_page == 'stock-request.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Stock Request</span>
                        </a>
                    </li>
                </ul>
                <span class="tooltip">Stock</span>
            </li>
            <!-- Warehouse Management with Dropdown -->
            <li x-data="{ open: <?php echo in_array($current_page, ['manage-warehouse.php', 'add-warehouse.php', 'warehouse-products.php']) ? 'true' : 'false'; ?> }"
                x-cloak>
                <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                    <button type="button"
                        class="<?php echo in_array($current_page, ['manage-warehouse.php', 'add-warehouse.php', 'warehouse-products.php']) ? 'text-white bg-white' : ''; ?> flex items-center w-full text-left">
                        <i class='bx bx-buildings'></i>
                        <span class="links_name">Warehouse Management</span>
                    </button>
                    <i class='bx bx-chevron-down' :class="{ 'bx-rotate-180': open }"></i>
                </div>
                <ul x-show="open" x-transition class="pl-6">
                    <li>
                        <a href="../Admin/manage-warehouse.php"
                            class="<?php echo ($current_page == 'manage-warehouse.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Manage Warehouse</span>
                        </a>
                    </li>
                    <li>
                        <a href="../warehouse-app\add-warehouse.php"
                            class="<?php echo ($current_page == 'add-warehouse.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Add New Warehouse</span>
                        </a>
                    </li>
                    <li>
                        <a href="../warehouse-app\warehouse information\warehouse-info.php"
                            class="<?php echo ($current_page == 'warehouse-products.php') ? 'text-white bg-white' : ''; ?>">
                            <span class="links_name">Warehouse Products</span>
                        </a>
                    </li>
                </ul>
                <span class="tooltip">Warehouse Management</span>
            </li>
            <!-- Profile Info -->
            <li class="profile">
                <a href="../Login-Signup/profile.php" class="profile-details">
                    <img src="https://www.svgrepo.com/show/23012/profile-user.svg" alt="profileImg">
                    <div class="name_job">
                        <div class="name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="job">Admin</div>
                    </div>
                </a>
                <a href="../Login-Signup/logout.php">
                    <i class='bx bx-log-out' id="log_out"></i>
                </a>
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