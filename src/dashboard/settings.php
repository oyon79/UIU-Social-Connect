<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

// Prevent admins from accessing user dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$user = $db->query($sql, [$userId])[0];

$pageTitle = 'Settings - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body {
        background: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
        padding: 2rem;
    }

    .settings-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .settings-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }

    .settings-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-md);
    }

    .settings-section h3 {
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .setting-item {
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--gray-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .setting-item:last-child {
        border-bottom: none;
    }

    .toggle-switch {
        position: relative;
        width: 50px;
        height: 28px;
    }

    .toggle-switch input {
        display: none;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--gray-medium);
        border-radius: 28px;
        transition: 0.3s;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    .toggle-switch input:checked+.toggle-slider {
        background: var(--primary-orange);
    }

    .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(22px);
    }

    @media (max-width: 768px) {
        .main-container {
            margin-left: 0;
        }
    }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>

    <div class="settings-container">
        <div class="settings-header animate-fade-in">
            <h1>⚙️ Settings</h1>
            <p style="color: var(--gray-dark);">Manage your account preferences</p>
        </div>

        <!-- Account Settings -->
        <div class="settings-section animate-slide-up" style="animation-delay: 0.1s;">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Account Settings
            </h3>

            <form id="accountForm">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="fullName" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <small style="color: var(--gray-dark);">Email cannot be changed</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Student/Employee ID</label>
                    <input type="text" id="studentId" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>">
                </div>

                <button type="button" class="btn btn-primary" onclick="saveAccount()">Save Changes</button>
            </form>
        </div>

        <!-- Password Settings -->
        <div class="settings-section animate-slide-up" style="animation-delay: 0.2s;">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Change Password
            </h3>

            <form id="passwordForm">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" id="currentPassword" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" id="newPassword" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" id="confirmPassword" class="form-control">
                </div>

                <button type="button" class="btn btn-primary" onclick="changePassword()">Update Password</button>
            </form>
        </div>

        <!-- Privacy Settings -->
        <div class="settings-section animate-slide-up" style="animation-delay: 0.3s;">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Privacy Settings
            </h3>

            <div class="setting-item">
                <div>
                    <strong>Profile Visibility</strong>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0.25rem 0 0 0;">Allow others to view your profile</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div>
                    <strong>Show Online Status</strong>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0.25rem 0 0 0;">Let others see when you're online</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-section animate-slide-up" style="animation-delay: 0.4s;">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                Notifications
            </h3>

            <div class="setting-item">
                <div>
                    <strong>Email Notifications</strong>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0.25rem 0 0 0;">Receive notifications via email</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div>
                    <strong>Push Notifications</strong>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0.25rem 0 0 0;">Receive push notifications in browser</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Danger Zone -->
        <!-- <div class="settings-section animate-slide-up" style="animation-delay: 0.5s; border: 2px solid var(--error);">
            <h3 style="color: var(--error);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Danger Zone
            </h3>

            <div style="padding: 1rem; background: #FEE2E2; border-radius: 12px; margin-bottom: 1rem;">
                <p style="color: var(--error); margin: 0;">Once you delete your account, there is no going back. Please be certain.</p>
            </div>

            <button class="btn btn-danger" onclick="deleteAccount()">Delete Account</button>
        </div> -->
    </div>
</div>

<script>
    async function saveAccount() {
        const fullName = document.getElementById('fullName').value.trim();
        const studentId = document.getElementById('studentId').value.trim();

        if (!fullName) {
            showAlert('Name cannot be empty', 'error');
            return;
        }

        try {
            const response = await fetch('../api/users.php?action=update_profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    full_name: fullName,
                    student_id: studentId,
                    bio: ''
                })
            });

            const data = await response.json();
            if (data.success) {
                showAlert('Account updated successfully!', 'success');
            } else {
                showAlert(data.message || 'Failed to update', 'error');
            }
        } catch (error) {
            console.error('Error updating account:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

    async function changePassword() {
        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (!current || !newPass || !confirm) {
            showAlert('Please fill all fields', 'error');
            return;
        }

        if (newPass !== confirm) {
            showAlert('New passwords do not match', 'error');
            return;
        }

        if (newPass.length < 6) {
            showAlert('Password must be at least 6 characters', 'error');
            return;
        }

        try {
            const response = await fetch('../api/auth.php?action=change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    current_password: current,
                    new_password: newPass
                })
            });

            const text = await response.text();
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
                showAlert('Server error. Please try again.', 'error');
                return;
            }

            if (data.success) {
                showAlert('Password changed successfully!', 'success');
                document.getElementById('passwordForm').reset();
            } else {
                showAlert(data.message || 'Failed to change password', 'error');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} animate-slide-down`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    function deleteAccount() {
        if (!confirm('Are you sure you want to delete your account? This action cannot be undone!')) return;
        if (!confirm('This is your final warning. Delete account permanently?')) return;

        alert('Account deletion feature is disabled for safety. Please contact an administrator.');
    }
</script>

</body>

</html>