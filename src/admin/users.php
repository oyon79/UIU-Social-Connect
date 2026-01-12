<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'User Management - Admin Panel';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        body {
            background: var(--gray-light);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .admin-nav-link {
            padding: 0.875rem 1.75rem;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark-text);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-nav-link:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
        }

        .admin-nav-link.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border: 2px solid var(--gray-medium);
            border-radius: 12px;
            font-size: 1rem;
        }

        .filter-select {
            padding: 0.875rem 1.5rem;
            border: 2px solid var(--gray-medium);
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .users-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
        }

        th {
            padding: 1.25rem;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-light);
        }

        tbody tr:hover {
            background: var(--gray-light);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #ECFDF5;
            color: var(--success);
        }

        .status-badge.banned {
            background: #FEE2E2;
            color: var(--error);
        }

        .status-badge.rejected {
            background: #FFF7ED;
            color: var(--warning);
        }

        .status-badge.pending {
            background: #FFF7ED;
            color: var(--warning);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header animate-fade-in">
            <h1>👥 User Management</h1>
            <p style="color: var(--gray-dark);">Manage all platform users</p>
        </div>

        <div class="admin-nav">
            <a href="index.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            <a href="approvals.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Approvals
            </a>
            <a href="users.php" class="admin-nav-link active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Users
            </a>
            <a href="content.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                </svg>
                Content
            </a>
        </div>

        <div class="search-bar">
            <input type="text" class="search-input" id="searchInput" placeholder="Search users by name, email, or ID...">
            <select class="filter-select" id="roleFilter">
                <option value="">All Roles</option>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
                <option value="Alumni">Alumni</option>
                <option value="Staff">Staff</option>
                <option value="Club Forum">Club Forum</option>
            </select>
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
                <option value="banned">Banned</option>
            </select>
        </div>

        <div class="users-table" id="usersTable">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem;">
                            <div class="spinner"></div>
                            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading users...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let allUsers = [];

        document.addEventListener('DOMContentLoaded', loadUsers);
        document.getElementById('searchInput').addEventListener('input', filterUsers);
        document.getElementById('roleFilter').addEventListener('change', filterUsers);
        document.getElementById('statusFilter').addEventListener('change', filterUsers);

        async function loadUsers() {
            try {
                const response = await fetch('../api/admin.php?action=get_all_users', {
                    credentials: 'same-origin'
                });
                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Invalid JSON from get_all_users:', text);
                    const tbody = document.getElementById('usersTableBody');
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem;">
                                <p style="color: var(--error);">Server returned invalid response.</p>
                                <pre style="color: var(--gray-dark); white-space: pre-wrap; text-align:left;">${escapeHtml(text)}</pre>
                            </td>
                        </tr>
                    `;
                    return;
                }

                if (data.success) {
                    allUsers = data.users || [];
                    displayUsers(allUsers);
                } else {
                    console.error('Failed to load users:', data);
                    const tbody = document.getElementById('usersTableBody');
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem;">
                                <p style="color: var(--error);">Failed to load users: ${data.message || 'Server error'}</p>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem;">
                            <p style="color: var(--error);">Connection error while loading users.</p>
                        </td>
                    </tr>
                `;
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');

            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem;">
                            <p style="color: var(--gray-dark);">No users found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr class="animate-fade-in">
                    <td>
                        <div class="user-info">
                            <div class="avatar">
                                <span>${user.full_name.charAt(0).toUpperCase()}</span>
                            </div>
                            <div>
                                <strong>${escapeHtml(user.full_name)}</strong>
                                ${user.student_id ? `<br><small style="color: var(--gray-dark);">ID: ${escapeHtml(user.student_id)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${escapeHtml(user.role)}</td>
                    <td>
                            <span class="status-badge ${user.is_banned ? 'banned' : (!user.is_active && !user.is_approved ? 'rejected' : (user.is_approved ? 'active' : 'pending'))}">
                            ${user.is_banned ? 'Banned' : (!user.is_active && !user.is_approved ? 'Rejected' : (user.is_approved ? 'Active' : 'Pending'))}
                        </span>
                    </td>
                    <td>${user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'}</td>
                    <td>
                        <div class="action-buttons">
                            ${!user.is_banned ? `
                                <button class="btn btn-danger btn-icon" onclick="banUser(${user.id})" title="Ban User">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                    </svg>
                                </button>
                            ` : `
                                <button class="btn btn-success btn-icon" onclick="unbanUser(${user.id})" title="Unban User">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </button>
                            `}

                            ${(!user.is_approved && user.is_active) ? `
                                <button class="btn btn-success btn-icon" onclick="approveUserFromList(${user.id})" title="Approve User">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </button>
                                <button class="btn btn-danger btn-icon" onclick="rejectUserFromList(${user.id})" title="Reject User">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            ` : ''}

                            <button class="btn btn-outline btn-icon" onclick="viewUser(${user.id})" title="View Profile">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            const filtered = allUsers.filter(user => {
                const matchesSearch = (String(user.full_name || '').toLowerCase().includes(searchTerm)) ||
                    (String(user.email || '').toLowerCase().includes(searchTerm)) ||
                    (user.student_id && String(user.student_id).toLowerCase().includes(searchTerm));

                const matchesRole = !roleFilter || user.role === roleFilter;

                let matchesStatus = true;
                if (statusFilter === '1') matchesStatus = user.is_approved && !user.is_banned;
                else if (statusFilter === 'pending') matchesStatus = !user.is_approved && user.is_active; // pending
                else if (statusFilter === 'rejected') matchesStatus = !user.is_approved && !user.is_active;
                else if (statusFilter === 'banned') matchesStatus = user.is_banned;

                return matchesSearch && matchesRole && matchesStatus;
            });

            displayUsers(filtered);
        }

        async function banUser(userId) {
            if (!confirm('Ban this user? They will not be able to login.')) return;

            try {
                const response = await fetch('../api/admin.php?action=ban_user', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Invalid JSON from ban_user:', text);
                    showAlert('Server error', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User banned successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to ban user', 'error');
                }
            } catch (error) {
                console.error('Ban user failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function unbanUser(userId) {
            try {
                const response = await fetch('../api/admin.php?action=unban_user', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Invalid JSON from unban_user:', text);
                    showAlert('Server error', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User unbanned successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to unban user', 'error');
                }
            } catch (error) {
                console.error('Unban user failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        function viewUser(userId) {
            window.location.href = `../dashboard/profile.php?id=${userId}`;
        }

        // Approve/reject helpers for users list (calls approvals API)
        async function approveUserFromList(userId) {
            if (!confirm('Approve this user?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_user', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Invalid JSON from approve_user:', text);
                    showAlert('Server error', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User approved', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve user', 'error');
                }
            } catch (error) {
                console.error('Approve request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectUserFromList(userId) {
            const reason = prompt('Reason for rejection (optional):');
            if (reason === null) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_user', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        reason
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Invalid JSON from reject_user:', text);
                    showAlert('Server error', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject user', 'error');
                }
            } catch (error) {
                console.error('Reject request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} animate-slide-down`;
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    </script>
</body>

</html>