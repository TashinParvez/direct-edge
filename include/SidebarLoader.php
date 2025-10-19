<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/connect-db.php';

$role = $_SESSION['role'] ?? '';

// If role is not in session, try to fetch it from DB
if (empty($role) && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $role = $user['role'];
        $_SESSION['role'] = $role; // Store it in session for next time
    }
    $stmt->close();
}

if (!empty($role)) {
    if ($role === 'Agent') {
        include __DIR__ . '/SidebarAgent.php';
    } elseif ($role === 'Shop-Owner') {
        include __DIR__ . '/SidebarShop.php';
    } elseif ($role === 'Admin') { // Assuming 'Admin' for warehouse manager
        include __DIR__ . '/SidebarWarehouse.php';
    } else {
        // Fallback for other user types like 'User'
        include __DIR__ . '/Sidebar.php';
    }
} else {
    // If role is not set, include a default sidebar
    include __DIR__ . '/Sidebar.php';
}
