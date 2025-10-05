<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection


// Start session to get user_id (assuming user is logged in)
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php'); // Redirect to login if not authenticated
//     exit();
// }

// $user_id = $_SESSION['user_id'];
$user_id = 1; // For testing purposes, replace with actual user ID from session

// Set timezone to Asia/Dhaka (+06)
date_default_timezone_set('Asia/Dhaka');

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_notifications':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $notifications = getNotifications($conn, $user_id, $limit);
            $unread_count = getUnreadCount($conn, $user_id);

            // Format notifications for JSON response
            $formatted_notifications = array_map(function ($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'message' => $notif['message'],
                    'link' => $notif['link'],
                    'is_read' => (bool)$notif['is_read'],
                    'created_at' => $notif['created_at'],
                    'icon' => getNotificationIcon($notif['type']),
                    'relative_time' => getRelativeTime($notif['created_at'])
                ];
            }, $notifications);

            echo json_encode([
                'success' => true,
                'notifications' => $formatted_notifications,
                'unread_count' => $unread_count
            ]);
            break;

        case 'get_unread_count':
            $unread_count = getUnreadCount($conn, $user_id);
            echo json_encode([
                'success' => true,
                'unread_count' => $unread_count
            ]);
            break;

        case 'mark_read':
            $notification_id = (int)($_GET['notification_id'] ?? 0);
            markNotificationAsRead($conn, $notification_id, $user_id);
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_read':
            markAllNotificationsAsRead($conn, $user_id);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $notification_id = (int)($_GET['notification_id'] ?? 0);
            deleteNotification($conn, $notification_id, $user_id);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

    mysqli_close($conn);
    exit();
}

// Functions for notification operations

function getNotifications($conn, $user_id, $limit = null)
{
    $sql = "SELECT id, type, message, link, is_read, created_at FROM notifications
            WHERE user_id = ? ORDER BY created_at DESC";

    if ($limit !== null) {
        $sql .= " LIMIT ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $notifications;
}

function getUnreadCount($conn, $user_id)
{
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int)$row['count'];
}

function markAllNotificationsAsRead($conn, $user_id)
{
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function markNotificationAsRead($conn, $notification_id, $user_id)
{
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function deleteNotification($conn, $notification_id, $user_id)
{
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function getRelativeTime($timestamp)
{
    // Set timezone to Asia/Dhaka (+06)
    date_default_timezone_set('Asia/Dhaka');

    $now = new DateTime();
    try {
        $notification_time = new DateTime($timestamp);
    } catch (Exception $e) {
        // Fallback if timestamp is invalid
        return 'Invalid date';
    }

    // Check if notification time is in the future
    if ($notification_time > $now) {
        return 'Future date';
    }

    $diff = $now->diff($notification_time);

    // Calculate total seconds for precision
    $seconds = ($diff->days * 24 * 3600) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

    if ($seconds < 30) {
        return 'just now';
    } elseif ($seconds < 3600) { // Less than 1 hour
        $minutes = ceil($seconds / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($seconds < 86400) { // Less than 1 day
        $hours = ceil($seconds / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($seconds < 604800) { // Less than 7 days
        $days = ceil($seconds / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return $notification_time->format('M j, Y');
    }
}

function getNotificationIcon($type)
{
    $icons = [
        'low_stock' => '📦',
        'new_order' => '🛒',
        'request_approved' => '✅',
        'request_rejected' => '❌',
        'request_pending' => '⏳',
        'system' => '⚙️',
        'offer' => '🎁 offer',
        'profile_update' => '👤',
        'message' => '💬',
        'warning' => '⚠️',
        'info' => 'ℹ️',
        'success' => '✅',
        'error' => '❌'
    ];

    return $icons[$type] ?? '🔔';
}

// Get data for display
$notifications = getNotifications($conn, $user_id);
$unread_count = getUnreadCount($conn, $user_id);
$show_all = isset($_GET['show_all']);
$display_notifications = $show_all ? $notifications : array_slice($notifications, 0, 4);

// Close database connection
mysqli_close($conn);
