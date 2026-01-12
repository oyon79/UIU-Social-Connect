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
            <span class="badge" style="position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 4px; font-size: 11px;">3</span>
        </button>

        <!-- Messages -->
        <button class="navbar-icon-btn" onclick="window.location.href='messages.php'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="badge" style="position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 4px; font-size: 11px; background: var(--error);">5</span>
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
        <button class="btn btn-ghost btn-sm">Mark all read</button>
    </div>
    
    <div id="notificationsList">
        <!-- Notifications will be loaded here -->
        <div class="dropdown-item">
            <div style="display: flex; gap: 1rem;">
                <div class="avatar avatar-sm" style="flex-shrink: 0;">
                    <span>J</span>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 14px;"><strong>John Doe</strong> liked your post</p>
                    <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">2 hours ago</p>
                </div>
            </div>
        </div>
        
        <div class="dropdown-item">
            <div style="display: flex; gap: 1rem;">
                <div class="avatar avatar-sm" style="flex-shrink: 0; background: var(--success);">
                    <span>A</span>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 14px;"><strong>Admin</strong> approved your post</p>
                    <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">5 hours ago</p>
                </div>
            </div>
        </div>
        
        <div class="dropdown-item">
            <div style="display: flex; gap: 1rem;">
                <div class="avatar avatar-sm" style="flex-shrink: 0;">
                    <span>S</span>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 14px;"><strong>Sarah Wilson</strong> commented on your post</p>
                    <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">1 day ago</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
        notificationsDropdown.style.display = isVisible ? 'none' : 'block';
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
