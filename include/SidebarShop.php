<!-- SidebarShop.php -->
<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


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


// Get unread notification count
$unread_count = 0;
if (isset($user_id)) {
    $nbPath = __DIR__ . '/../notification/notification-backend.php';
    if (file_exists($nbPath)) {
        include_once $nbPath;
        if (function_exists('getUnreadCount')) {
            $unread_count = (int) getUnreadCount($conn, $user_id);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="..\assets\Logo\LogoBG.png">
</head>

<body class="bg-custom">
    <div class="sidebar open m-0">
        <!-- <div class="logo-details">
            <i class='bx bx-menu-alt-right' id="btn"></i>
        </div> -->
        <ul class="nav-list p-0">
            <li>
                <a href="/../shop-owner-app\buy-products-from-warehouse.php"
                    class="<?php echo ($current_page == 'buy-products.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-shopping-bag'></i>
                    <span class="links_name">Buy Products</span>
                </a>
                <span class="tooltip">Buy Products</span>
            </li>
            <li>
                <a href="/../shop-owner-app\Self-Service-Orders\Self-Service-Orders.php"
                    class="<?php echo ($current_page == 'self-service-orders.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-store'></i>
                    <span class="links_name">Self-Service Orders</span>
                </a>
                <span class="tooltip">Self-Service Orders</span>
            </li>
            <!-- <li>
                <a href="/../shop-owner-app/demand_forecast_dashboard_updated.php/"
                    class="<?php
                            // echo ($current_page == 'dashboard.php') ? 'text-white bg-white' : ''; 
                            ?>">
                    <i class='bx bx-bar-chart'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li> -->
            <li>
                <a href="/../shop-owner-app/demand_forecast_dashboard_updated.php/"
                    class="<?php echo ($current_page == 'demand-forecasting.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-line-chart'></i>
                    <span class="links_name">Demand Forecasting</span>
                </a>
                <span class="tooltip">Demand Forecasting</span>
            </li>
            <li>
                <a href="/../shop-owner-app\Profuct-for-buyers-from-shop\Available-Products-List.php"
                    class="<?php echo ($current_page == 'home.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">Available Products</span>
                </a>
                <span class="tooltip">Available Products</span>
            </li>

            <li>
                <a href="/../shop-owner-app/cart.php"
                    class="<?php echo ($current_page == 'cart.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-store'></i>
                    <span class="links_name">Product Scan</span>
                </a>
                <span class="tooltip">Product Scan</span>
            </li>

            <li>
                <a href="/../notification/notification.php"
                    class="notification-link <?php echo ($current_page == 'notification.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-bell'></i>
                    <span class="links_name">Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-badge"><?php echo $unread_count; ?></span>
                        <span class="blinking-dot"></span>
                    <?php endif; ?>
                </a>
                <span class="tooltip">Notifications</span>
            </li>



            <!-- <li>
                <a href="../Buyer/request-products.php"
                    class="<?php echo ($current_page == 'request-products.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-package'></i>
                    <span class="links_name">Request Products</span>
                </a>
                <span class="tooltip">Request Products</span>
            </li> -->
            <!-- Profile Info -->
            <li class="profile">
                <a href="/../Login-Signup/profile.php" class="profile-details">
                    <i class='bx bx-user-circle profile-icon' style="font-size:28px"></i>
                    <div class="name_job">
                        <div class="name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="job">Shop Owner</div>
                    </div>
                </a>
                <a href="/../Login-Signup/logout.php">
                    <i class='bx bx-log-out' id="log_out"></i>
                </a>
            </li>
            <li>
                <a href="/../shop-owner-app/cart.php"
                    class="<?php echo ($current_page == 'self-service-orders.php') ? 'text-white bg-white' : ''; ?>">
                    <i class='bx bx-store'></i>
                    <span class="links_name">Product Scan</span>
                </a>
                <span class="tooltip">Product Scan</span>
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