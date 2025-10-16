<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection
session_start();

// Database connection check
if (!isset($conn) || !$conn) {
    die('Database connection not available');
}

// Initialize variables
$name = '';
$role = '';
$status = '';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch user details
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

// Define menu items for each role
$menus = [
    'Admin' => [
        ['Dashboard', 'dashboard.php', 'fa-home'],
        ['Manage Users', 'manage_users.php', 'fa-users'],
        ['Manage Properties', 'manage_properties.php', 'fa-building'],
        ['Reports', 'reports.php', 'fa-chart-bar'],
        ['Profile', 'profile.php', 'fa-user'],
        ['Settings', 'settings.php', 'fa-cog'],
        ['Logout', 'logout.php', 'fa-sign-out-alt']
    ],
    'Agent' => [
        ['Dashboard', 'dashboard.php', 'fa-home'],
        ['My Properties', 'my_properties.php', 'fa-building'],
        ['Add Property', 'add_property.php', 'fa-plus-square'],
        ['My Clients', 'my_clients.php', 'fa-users'],
        ['Profile', 'profile.php', 'fa-user'],
        ['Settings', 'settings.php', 'fa-cog'],
        ['Logout', 'logout.php', 'fa-sign-out-alt']
    ],
    'Client' => [
        ['Dashboard', 'dashboard.php', 'fa-home'],
        ['Search Properties', 'search_properties.php', 'fa-search'],
        ['My Favorites', 'my_favorites.php', 'fa-heart'],
        ['My Inquiries', 'my_inquiries.php', 'fa-envelope'],
        ['Profile', 'profile.php', 'fa-user'],
        ['Settings', 'settings.php', 'fa-cog'],
        ['Logout', 'logout.php', 'fa-sign-out-alt']
    ]
];

// Select the correct menu
$menu = isset($menus[$role]) ? $menus[$role] : [];
?>

<style>
/* Sidebar styles */
.sidebar {
    width: 250px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 100;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    transition: width 0.3s;
}

.sidebar-header {
    padding: 30px 20px 20px 20px;
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 1px;
    background: rgba(255, 255, 255, 0.05);
    text-align: center;
}

.sidebar-user {
    display: flex;
    align-items: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    margin: 20px;
    margin-bottom: 0;
}

.sidebar-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    margin-right: 15px;
    color: #fff;
    text-transform: uppercase;
}

.sidebar-user-details {
    display: flex;
    flex-direction: column;
}

.sidebar-user-name {
    font-weight: 600;
    font-size: 1rem;
}

.sidebar-user-role {
    font-size: 0.85rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar-menu {
    flex: 1;
    margin-top: 30px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 15px 30px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    border-left: 4px solid transparent;
    transition: all 0.2s;
    position: relative;
}

.sidebar-link.active,
.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-left: 4px solid #fff;
}

.sidebar-link i {
    margin-right: 15px;
    font-size: 1.1rem;
}

.sidebar-link.disabled,
.sidebar-link.disabled:hover {
    color: #bbb !important;
    pointer-events: none;
    background: none;
    border-left: 4px solid transparent;
}

.sidebar-footer {
    padding: 20px;
    text-align: center;
    font-size: 0.9rem;
    opacity: 0.7;
}

@media (max-width: 900px) {
    .sidebar {
        width: 70px;
    }

    .sidebar-header,
    .sidebar-user-details,
    .sidebar-footer {
        display: none;
    }

    .sidebar-link {
        padding: 15px 10px;
        justify-content: center;
    }

    .sidebar-link span {
        display: none;
    }
}
</style>

<div class="sidebar">
    <div class="sidebar-header">
        🏠 PropertyHub
    </div>
    <?php if (isset($_SESSION['user_id']) && !empty($name)): ?>
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php echo !empty($name) ? substr($name, 0, 1) : 'U'; ?>
        </div>
        <div class="sidebar-user-details">
            <span class="sidebar-user-name"><?php echo htmlspecialchars($name); ?></span>
            <span class="sidebar-user-role">
                <?php echo htmlspecialchars($role); ?>
                <?php if ($role === 'Agent' && !empty($status)): ?>
                <span class="agent-status status-<?php echo strtolower($status); ?>">
                    <?php echo htmlspecialchars($status); ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    <div class="sidebar-menu">
        <?php foreach ($menu as $item):
            $label = $item[0];
            $href = $item[1];
            $icon = $item[2];
            $active = ($current_page == $href) ? 'active' : '';
            $disabled = ($role === 'Agent' && $status === 'Pending' && $href !== 'logout.php') ? 'disabled' : '';
        ?>
        <a href="<?php echo $href; ?>" class="sidebar-link <?php echo $active . ' ' . $disabled; ?>">
            <i class="fa <?php echo $icon; ?>"></i>
            <span><?php echo $label; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="sidebar-footer">
        &copy; <?php echo date('Y'); ?> PropertyHub
    </div>
</div>
<!-- Font Awesome CDN for icons (add in your layout if not already present) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">