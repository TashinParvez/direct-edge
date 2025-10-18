<?php
// include/notification_helpers.php

/**
 * Creates a notification for a specific user or a group of users.
 *
 * @param mysqli $conn The database connection object.
 * @param int|array $user_ids A single user ID or an array of user IDs to notify.
 * @param string $type The notification type (must match ENUM values in the table).
 * @param string $message The notification message.
 * @param string|null $link An optional URL for the notification.
 * @return void
 */
function create_notification($conn, $user_ids, $type, $message, $link = null)
{
    if (!is_array($user_ids)) {
        $user_ids = [$user_ids]; // Convert single ID to an array
    }

    if (empty($user_ids)) {
        return; // No one to notify
    }

    $sql = "INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Handle error, e.g., log it
        error_log("Failed to prepare statement for notification: " . $conn->error);
        return;
    }

    foreach ($user_ids as $user_id) {
        $stmt->bind_param("isss", $user_id, $type, $message, $link);
        $stmt->execute();
    }

    $stmt->close();
}

/**
 * Fetches all user IDs for a specific role.
 *
 * @param mysqli $conn The database connection object.
 * @param string $role The role to fetch (e.g., 'Admin', 'Agent').
 * @return array An array of user IDs.
 */
function get_user_ids_by_role($conn, $role)
{
    $sql = "SELECT user_id FROM users WHERE role = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Failed to prepare statement for get_user_ids_by_role: " . $conn->error);
        return [];
    }

    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_ids = [];
    while ($row = $result->fetch_assoc()) {
        $user_ids[] = $row['user_id'];
    }

    $stmt->close();
    return $user_ids;
}
