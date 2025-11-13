document.addEventListener('DOMContentLoaded', function() {
    // Only run if user is logged in (check for notification elements)
    if (document.getElementById('notificationDropdown')) {
        // Load notifications on page load
        loadNotifications();
        
        // Set interval to refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
        
        // Bootstrap handles the dropdown toggle automatically with data-bs-toggle="dropdown"
    }
});

function loadNotifications() {
    fetch('/api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading notifications:', data.error);
                return;
            }
            
            updateNotificationBadge(data.unread_count);
            updateNotificationDropdown(data.notifications);
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateNotificationDropdown(notifications) {
    const menu = document.getElementById('notificationsMenu');
    if (!menu) return;
    
    // Clear existing notifications except the header and footer
    const notificationItems = menu.querySelector('.notification-items');
    notificationItems.innerHTML = '';
    
    if (notifications.length === 0) {
        const emptyItem = document.createElement('div');
        emptyItem.className = 'dropdown-item text-center text-muted py-3';
        emptyItem.innerHTML = '<i class="fas fa-bell-slash me-2"></i>No notifications';
        notificationItems.appendChild(emptyItem);
    } else {
        notifications.forEach(notification => {
            const item = document.createElement('a');
            item.className = `dropdown-item notification-item ${notification.is_read ? 'read' : 'unread'}`;
            item.href = `/feedback/?view=${notification.feedback_id}`;
            
            const date = new Date(notification.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            item.innerHTML = `
                <div class="d-flex align-items-start">
                    <div class="notification-icon me-2">
                        ${!notification.is_read ? '<span class="badge bg-danger">New</span>' : '<i class="fas fa-check-circle text-muted"></i>'}
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-text small text-truncate">${notification.message}</div>
                        <div class="notification-time small text-muted">
                            <i class="fas fa-clock me-1"></i>${formattedDate}
                        </div>
                    </div>
                </div>
            `;
            
            notificationItems.appendChild(item);
        });
    }
}

function markAsRead(notificationId) {
    window.location.href = `/notifications.php?mark_read=${notificationId}`;
}