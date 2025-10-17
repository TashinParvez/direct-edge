// Notification JavaScript - AJAX-powered real-time notifications

let currentNotifications = [];
let showAll = false; // Will be initialized from server state
let lastUnreadCount = 0;
let updateInterval;
let relativeTimeInterval;

// AJAX helper function
function ajaxRequest(action, params = {}) {
    // Add cache-busting parameter
    params._ = new Date().getTime();
    return fetch(`notification-backend.php?ajax=1&action=${action}&${new URLSearchParams(params)}`)
        .then(response => response.json())
        .catch(error => {
            console.error('AJAX Error:', error);
            return { success: false, error: error.message };
        });
}

// Show toast
function showToast(title, description) {
    const toast = document.getElementById('toast');
    const toastTitle = document.getElementById('toastTitle');
    const toastDescription = document.getElementById('toastDescription');

    toastTitle.textContent = title;
    toastDescription.textContent = description;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Update notification count badge
function updateBadge(count) {
    const badges = document.querySelectorAll('#badge');
    badges.forEach(badge => {
        badge.textContent = count;
    });

    // Update subtitle text
    const subtitle = document.querySelector('#subtitle');
    if (subtitle) {
        subtitle.textContent = count > 0 
            ? `You have ${count} new notification${count !== 1 ? 's' : ''}`
            : 'No new notifications';
    }

    // Show/hide mark all as read button
    const markAllBtn = document.getElementById('markAllBtn');
    if (markAllBtn) {
        markAllBtn.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Update sidebar notification badge and blinking dot
function updateSidebarNotificationStatus(unreadCount) {
    const sidebarNotifLinks = document.querySelectorAll('.notification-link');

    sidebarNotifLinks.forEach(link => {
        let badge = link.querySelector('.notif-badge');
        let dot = link.querySelector('.blinking-dot');

        if (unreadCount > 0) {
            // Create badge if it doesn't exist
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                link.appendChild(badge);
            }
            badge.textContent = unreadCount;

            // Create blinking dot if it doesn't exist
            if (!dot) {
                dot = document.createElement('span');
                dot.className = 'blinking-dot';
                link.appendChild(dot);
            }
        } else {
            // Remove badge and dot if count is zero
            if (badge) badge.remove();
            if (dot) dot.remove();
        }
    });
}

// Render notifications
function renderNotifications(notifications, unreadCount) {
    const list = document.getElementById('notificationsList');
    const viewAllBtn = document.getElementById('viewAllBtn');

    console.log('Rendering notifications, showAll:', showAll, 'notifications count:', notifications.length);

    updateBadge(unreadCount);

    if (!notifications || notifications.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <h3>No notifications</h3>
                <p>You're all caught up!</p>
            </div>
        `;
        if (viewAllBtn) viewAllBtn.style.display = 'none';
        return;
    }

    const toShow = showAll ? notifications : notifications.slice(0, 4);
    console.log('Showing', toShow.length, 'notifications out of', notifications.length);

    list.innerHTML = toShow.map(notif => `
        <div class="notification-card ${notif.is_read ? 'read' : 'new'}" data-id="${notif.id}" ${notif.is_read ? '' : `onclick="markAsRead(${notif.id})"`}>
            ${notif.is_read ? '' : `
                <div class="ping-indicator">
                    <span class="ping-animation"></span>
                    <span class="ping-dot"></span>
                </div>
            `}
            <div class="notification-content">
                <div class="notification-title">
                    ${notif.icon} ${notif.message}
                </div>
                <div class="notification-message">
                    ${notif.link ? `<a href="${notif.link}" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation()">View details →</a>` : ''}
                </div>
                <div class="timestamp">${notif.relative_time}</div>
            </div>
            <button class="delete-btn" onclick="event.stopPropagation(); deleteNotification(${notif.id})" aria-label="Delete notification">
                <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    `).join('');

    // Update view all button
    if (viewAllBtn) {
        if (notifications.length > 1) {
            viewAllBtn.style.display = 'block';
            viewAllBtn.textContent = (showAll && notifications.length > 4) ? 'Show less' : 'View all notifications';
            console.log('Updated viewAllBtn text to:', viewAllBtn.textContent);
        } else {
            viewAllBtn.style.display = 'none';
        }
    }

    // Add animations
    addCardAnimations();
    makeCardsClickable();
}

// Load notifications via AJAX
function loadNotifications() {
    ajaxRequest('get_notifications').then(response => {
        if (response.success) {
            currentNotifications = response.notifications;
            lastUnreadCount = response.unread_count;
            renderNotifications(currentNotifications, response.unread_count);
            updateSidebarNotificationStatus(response.unread_count); // Update sidebar as well
        }
    });
}

// Mark notification as read
function markAsRead(notificationId) {
    const card = document.querySelector(`[data-id="${notificationId}"]`);
    if (card && card.classList.contains('new')) {
        // Optimistic update
        card.classList.remove('new');
        card.classList.add('read');
        const pingIndicator = card.querySelector('.ping-indicator');
        if (pingIndicator) pingIndicator.remove();

        ajaxRequest('mark_read', { notification_id: notificationId }).then(response => {
            if (response.success) {
                loadNotifications(); // Refresh to get updated counts
                showToast('Notification marked as read', 'This notification has been marked as read.');
            }
        });
    }
}

// Mark all notifications as read
function markAllAsRead() {
    const markAllBtn = document.getElementById('markAllBtn');
    if (markAllBtn) {
        markAllBtn.disabled = true;
        markAllBtn.textContent = 'Marking...';
    }

    ajaxRequest('mark_all_read').then(response => {
        if (response.success) {
            loadNotifications();
            showToast('All notifications marked as read', "You're all caught up!");
        }
        if (markAllBtn) {
            markAllBtn.disabled = false;
            markAllBtn.textContent = 'Mark all as read';
        }
    });
}

// Delete notification
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) return;

    const card = document.querySelector(`[data-id="${notificationId}"]`);
    if (card) {
        card.classList.add('deleting');
        setTimeout(() => {
            ajaxRequest('delete', { notification_id: notificationId }).then(response => {
                if (response.success) {
                    loadNotifications();
                    showToast('Notification deleted', 'The notification has been removed.');
                } else {
                    card.classList.remove('deleting');
                }
            });
        }, 400);
    }
}

// View all notifications
function viewAllNotifications() {
    showAll = !showAll;
    renderNotifications(currentNotifications, lastUnreadCount);
    showToast('Viewing all notifications', 'Showing all available notifications');
}

// Check for new notifications periodically
function startPolling() {
    updateInterval = setInterval(() => {
        ajaxRequest('get_unread_count').then(response => {
            if (response.success) {
                // Update both the main notification UI and the sidebar
                if (response.unread_count !== lastUnreadCount) {
                    lastUnreadCount = response.unread_count;
                    updateBadge(response.unread_count);
                    updateSidebarNotificationStatus(response.unread_count); // Update sidebar

                    if (response.unread_count > 0) {
                        loadNotifications(); // Refresh main list if new notifications
                    }
                }
            }
        });
    }, 5000); // Check every 5 seconds for faster updates
}

// Start updating relative times periodically
function startRelativeTimeUpdates() {
    relativeTimeInterval = setInterval(() => {
        loadNotifications(); // Reload notifications to update relative times
    }, 30000); // Update every 30 seconds
}

// Stop polling
function stopPolling() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
    if (relativeTimeInterval) {
        clearInterval(relativeTimeInterval);
    }
}

// Function to make unread dots blink
function startBlinkingDots() {
    const dots = document.querySelectorAll('.ping-indicator');
    dots.forEach(dot => {
        dot.style.animation = 'blink 2s infinite';
    });
}

// Stop blinking animation
function stopBlinkingDots() {
    const dots = document.querySelectorAll('.ping-indicator');
    dots.forEach(dot => {
        dot.style.animation = 'none';
    });
}

// Add smooth animations for notification cards
function addCardAnimations() {
    const cards = document.querySelectorAll('.notification-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
}

// Make notification cards clickable with visual feedback
function makeCardsClickable() {
    const cards = document.querySelectorAll('.notification-card.new');
    cards.forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
        });
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    // Load initial notifications
    loadNotifications();

    // Start polling for new notifications
    startPolling();

    // Start updating relative times
    startRelativeTimeUpdates();

    // Start blinking dots
    startBlinkingDots();

    // Stop blinking after 5 seconds
    setTimeout(stopBlinkingDots, 5000);

    // Handle mark all as read button
    const markAllBtn = document.getElementById('markAllBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', markAllAsRead);
    }

    // Handle view all button
    const viewAllBtn = document.getElementById('viewAllBtn');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', viewAllNotifications);
    }

    // Initial sidebar update
    ajaxRequest('get_unread_count').then(response => {
        if (response.success) {
            updateSidebarNotificationStatus(response.unread_count);
        }
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    stopPolling();
});