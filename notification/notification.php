<?php include 'notification-backend.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="notification.css">
</head>

<body>
    <div class="container" id="notificationsContainer">
        <div class="header">
            <div class="header-content">
                <div class="bell-icon-wrapper">
                    <svg class="bell-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="badge<?php echo ($unread_count > 0) ? ' show' : ''; ?>" id="badge"><?php echo ($unread_count > 0) ? $unread_count : ''; ?></span>
                </div>
                <div>
                    <h1 class="title">Notifications</h1>
                    <p class="subtitle" id="subtitle"><?php echo ($unread_count > 0) ? "You have {$unread_count} new notification" . ($unread_count > 1 ? 's' : '') : 'No new notifications'; ?></p>
                </div>
            </div>
            <?php if ($unread_count > 0): ?>
                <button class="mark-read-btn" id="markAllBtn" onclick="markAllAsRead()">
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8.5 13.5 L12.5 17.5 L21.5 6.5" />
                        <path d="M2.5 13.5 L6.5 17.5 L13.5 8.5" />
                        <!-- front check -->
                    </svg>
                    Mark all as read
                </button>
            <?php endif; ?>
        </div>

        <div class="notifications-list" id="notificationsList">
            <?php if (empty($display_notifications)): ?>
                <div class="empty-state">
                    <h3>No notifications</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($display_notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'new'; ?>" data-id="<?php echo $notification['id']; ?>" <?php if (!$notification['is_read']): ?>onclick="markAsRead(<?php echo $notification['id']; ?>)" <?php endif; ?>>
                        <?php if (!$notification['is_read']): ?>
                            <div class="ping-indicator">
                                <span class="ping-animation"></span>
                                <span class="ping-dot"></span>
                            </div>
                        <?php endif; ?>
                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo getNotificationIcon($notification['type']); ?>
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            <div class="notification-message">
                                <?php if ($notification['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation()">
                                        View details →
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="timestamp"><?php echo getRelativeTime($notification['created_at']); ?></div>
                        </div>
                        <button class="delete-btn" onclick="event.stopPropagation(); deleteNotification(<?php echo $notification['id']; ?>)" aria-label="Delete notification">
                            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="view-all-wrapper">
            <?php if (count($notifications) > 0): ?>
                <button class="view-all-btn" id="viewAllBtn">
                    <?php echo $show_all ? 'Show less' : 'View all notifications'; ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="toast" id="toast">
            <div class="toast-content">
                <strong id="toastTitle"></strong>
                <p id="toastDescription"></p>
            </div>
        </div>
    </div>

    <script src="notification.js"></script>
    <script>
        // Initialize showAll from server-side state
        showAll = <?php echo $show_all ? 'true' : 'false'; ?>;
        console.log('Initial showAll set to:', showAll);
    </script>
</body>

</html>