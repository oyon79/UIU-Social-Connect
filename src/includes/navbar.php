<?php
// Get user data from session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'Student';
?>

<nav class="navbar">
    <!-- Search Bar -->
    <div class="navbar-search">
        <div class="input-group">
            <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input 
                type="text" 
                id="globalSearch" 
                placeholder="Search UIU Social Connect..." 
                style="width: 100%; height: 40px; padding: 0 1rem 0 3rem; background: var(--bg-primary); border: 1px solid transparent; border-radius: 20px; font-size: 15px; transition: all 0.3s ease;"
                onfocus="this.style.background='var(--bg-secondary)'; this.style.borderColor='var(--border-medium)';"
                onblur="this.style.background='var(--bg-primary)'; this.style.borderColor='transparent';"
            >
        </div>
    </div>

    <!-- Actions -->
    <div class="navbar-actions">
        <!-- Notifications -->
        <button class="navbar-icon-btn" id="notificationsBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="badge" id="notificationCount" style="position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 4px; font-size: 11px; display: none;">0</span>
        </button>

        <!-- Messages -->
        <button class="navbar-icon-btn" onclick="window.location.href='messages.php'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="badge" id="messageCount" style="position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 4px; font-size: 11px; background: var(--error); display: none;">0</span>
        </button>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="navbar-icon-btn dropdown-toggle" id="userDropdown">
                <div class="avatar avatar-sm">
                    <span><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                </div>
            </button>

            <div class="dropdown-menu" id="userDropdownMenu">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-light);">
                    <h4 style="margin: 0; font-size: 15px;"><?php echo htmlspecialchars($userName); ?></h4>
                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($userRole); ?></p>
                </div>
                
                <a href="profile.php" class="dropdown-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>My Profile</span>
                </a>
                
                <a href="settings.php" class="dropdown-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                    <span>Settings</span>
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a href="../api/auth.php?action=logout" class="dropdown-item" style="color: var(--error);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Notifications Dropdown -->
<div class="dropdown-menu" id="notificationsDropdown" style="display: none; position: fixed; right: 80px; top: 60px; width: 360px; max-height: 480px; overflow-y: auto;">
    <div style="padding: 1rem; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
        <h4 style="margin: 0; font-size: 17px; font-weight: 600;">Notifications</h4>
        <button class="btn btn-ghost btn-sm" id="markAllReadBtn" style="font-size: 13px;">Mark all read</button>
    </div>
    
    <div id="notificationsList">
        <!-- Notifications will be loaded here dynamically -->
        <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
            <div class="loader" style="border: 3px solid var(--gray-light); border-top: 3px solid var(--primary-orange); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            <p style="margin-top: 1rem;">Loading notifications...</p>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border-light);
    cursor: pointer;
    transition: background 0.2s ease;
}

.notification-item:hover {
    background: var(--bg-secondary);
}

.notification-item.unread {
    background: rgba(255, 122, 0, 0.05);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--primary-orange);
}
</style>

<script>
    // Load counts on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadNotificationCount();
        loadMessageCount();
        
        // Refresh counts every 30 seconds
        setInterval(() => {
            loadNotificationCount();
            loadMessageCount();
        }, 30000);
    });

    // Load notification count
    async function loadNotificationCount() {
        try {
            const response = await fetch('../api/notifications.php?action=get_unread_count');
            const data = await response.json();
            
            if (data.success) {
                const badge = document.getElementById('notificationCount');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error loading notification count:', error);
        }
    }

    // Load message count
    async function loadMessageCount() {
        try {
            const response = await fetch('../api/messages.php?action=get_unread_count');
            const data = await response.json();
            
            if (data.success) {
                const badge = document.getElementById('messageCount');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error loading message count:', error);
        }
    }

    // Load notifications
    async function loadNotifications() {
        const container = document.getElementById('notificationsList');
        container.innerHTML = '<div style="padding: 2rem; text-align: center;"><div class="loader" style="border: 3px solid var(--gray-light); border-top: 3px solid var(--primary-orange); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div></div>';

        try {
            const response = await fetch('../api/notifications.php?action=get_notifications&limit=20');
            const data = await response.json();

            if (data.success && data.notifications.length > 0) {
                container.innerHTML = data.notifications.map(notification => `
                    <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                         style="position: relative;"
                         onclick="markNotificationRead(${notification.id}, '${notification.reference_type}', ${notification.reference_id})">
                        <div style="display: flex; gap: 1rem; align-items: start;">
                            <div class="avatar avatar-sm" style="flex-shrink: 0; ${getNotificationColor(notification.type)}">
                                <span>${notification.avatar_initial || 'S'}</span>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0 0 0.25rem 0; font-size: 14px;">${notification.message}</p>
                                <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">${notification.time_ago}</p>
                            </div>
                            ${!notification.is_read ? '<div style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary-orange);"></div>' : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div style="padding: 3rem 1rem; text-align: center; color: var(--text-secondary);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.3; margin: 0 auto;">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <p style="margin-top: 1rem;">No notifications yet</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--error);">Failed to load notifications</div>';
        }
    }

    // Mark notification as read
    async function markNotificationRead(notificationId, referenceType, referenceId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            await fetch('../api/notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });

            // Refresh counts and list
            loadNotificationCount();
            loadNotifications();

            // Navigate if reference exists
            if (referenceType && referenceId) {
                navigateToReference(referenceType, referenceId);
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Navigate based on notification type
    function navigateToReference(type, id) {
        const routes = {
            'post': 'newsfeed.php',
            'event': 'events.php',
            'job': 'jobs.php',
            'notice': 'notices.php',
            'message': 'messages.php',
            'profile': `profile.php?id=${id}`
        };
        
        if (routes[type]) {
            window.location.href = routes[type];
        }
    }

    // Get notification color based on type
    function getNotificationColor(type) {
        const colors = {
            'like': 'background: #EF4444;',
            'comment': 'background: #3B82F6;',
            'friend_request': 'background: #10B981;',
            'message': 'background: #8B5CF6;',
            'event': 'background: #F59E0B;',
            'job': 'background: #06B6D4;',
            'notice': 'background: #EC4899;',
            'approval': 'background: #10B981;'
        };
        return colors[type] || 'background: var(--primary-orange);';
    }

    // Mark all as read
    document.getElementById('markAllReadBtn')?.addEventListener('click', async (e) => {
        e.stopPropagation();
        
        try {
            const response = await fetch('../api/notifications.php?action=mark_all_read', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                loadNotificationCount();
                loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    });

    // User dropdown toggle
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    userDropdown?.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdownMenu.classList.toggle('active');
        notificationsDropdown.style.display = 'none';
    });

    // Notifications dropdown
    const notificationsBtn = document.getElementById('notificationsBtn');
    const notificationsDropdown = document.getElementById('notificationsDropdown');

    notificationsBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = notificationsDropdown.style.display === 'block';
        
        if (!isVisible) {
            notificationsDropdown.style.display = 'block';
            loadNotifications(); // Load notifications when opening
        } else {
            notificationsDropdown.style.display = 'none';
        }
        
        userDropdownMenu.classList.remove('active');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        userDropdownMenu?.classList.remove('active');
        if (notificationsDropdown) {
            notificationsDropdown.style.display = 'none';
        }
    });

    // Global search
    const globalSearch = document.getElementById('globalSearch');
    globalSearch?.addEventListener('input', debounce(async (e) => {
        const query = e.target.value.trim();
        if (query.length >= 2) {
            // Implement search functionality
            console.log('Searching for:', query);
        }
    }, 300));

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
</script>
