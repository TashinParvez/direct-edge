<!-- SidebarAgent.php (Updated) -->
<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = '';
$role = ''; // role: admin, agent, shop-owner, user
$status = '';

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

$isPending = ($role === 'Agent' && $status === 'Pending');
$linkClass = $isPending ? 'text-gray-500 cursor-not-allowed pointer-events-none' : '';

// Try to get unread notification count for this user (optional)
$unread_count = 0;
if (isset($user_id)) {
    // Avoid redeclaring functions if notification-backend is already included elsewhere
    $nbPath = __DIR__ . '/../notification/notification-backend.php';
    if (file_exists($nbPath)) {
        // Include in a limited scope: use getUnreadCount if available after include
        include_once $nbPath;
        if (function_exists('getUnreadCount')) {
            // getUnreadCount expects $conn and $user_id
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
                <a href="../agent-app/agent-farmer-dashboard.php"
                    class="<?php echo ($current_page == 'agent-farmer-dashboard.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="../agent-app/farmers-list.php"
                    class="<?php echo ($current_page == 'farmers-list.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-group'></i>
                    <span class="links_name">Farmers Under Agent</span>
                </a>
                <span class="tooltip">Farmers Under Agent</span>
            </li>
            <li>
                <a href="../agent-app/add-farmers-info.php"
                    class="<?php echo ($current_page == 'add-farmers-info.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-user-plus'></i>
                    <span class="links_name">Add Farmer</span>
                </a>
                <span class="tooltip">Add Farmer</span>
            </li>
            <li>
                <a href="../agent-app/payment-details.php"
                    class="<?php echo ($current_page == 'payment-details.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-credit-card'></i>
                    <span class="links_name">Payment info</span>
                </a>
                <span class="tooltip">Payment info</span>
            </li>
            <li>
                <a href="../agent-app/inventory-request.php"
                    class="<?php echo ($current_page == 'inventory-request.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-package'></i>
                    <span class="links_name">Inventory Request</span>
                </a>
                <span class="tooltip">Inventory Request</span>
            </li>
            <li>
                <a href="../notification/notification.php"
                    class="notification-link <?php echo ($current_page == 'notification.php') ? 'text-white bg-white' : $linkClass; ?>">
                    <i class='bx bx-bell'></i>
                    <span class="links_name">Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-badge"><?php echo $unread_count; ?></span>
                        <span class="blinking-dot"></span>
                    <?php endif; ?>
                </a>
                <span class="tooltip">Notifications</span>
            </li>

            <!-- Profile Info -->
            <li class="profile">
                <?php $profileHref = isset($_SESSION['user_id']) ? '../agent-app/agent-profile.php' : '../Login-Signup/login.php'; ?>
                <a href="<?php echo $profileHref; ?>" class="profile-details <?php echo $linkClass; ?>">
                    <img src="https://www.svgrepo.com/show/23012/profile-user.svg" alt="profileImg">
                    <div class="name_job">
                        <div class="name"><?php echo htmlspecialchars($name ?: 'Guest'); ?></div>
                        <div class="job"><?php echo ($role === 'Agent') ? 'Agent' : ''; ?></div>
                    </div>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../Login-Signup/logout.php">
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