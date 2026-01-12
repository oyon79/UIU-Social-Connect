<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Groups & Clubs - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 2rem; }
    .groups-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
    .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
    .group-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
    .group-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .group-cover { width: 100%; height: 140px; background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light)); }
    .group-content { padding: 1.5rem; }
    .group-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
    .group-meta { display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: var(--gray-dark); margin-bottom: 1rem; }
    @media (max-width: 768px) { .main-container { margin-left: 0; } }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="groups-header animate-fade-in">
        <div>
            <h1>👥 Groups & Clubs</h1>
            <p style="color: var(--gray-dark);">Join communities and connect with others</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Group
        </button>
    </div>

    <div class="groups-grid" id="groupsGrid">
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading groups...</p>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div id="createGroupModal" class="modal">
    <div class="modal-backdrop" onclick="closeCreateModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create Group</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createGroupForm">
                <div class="form-group">
                    <label class="form-label">Group Name</label>
                    <input type="text" id="groupName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="groupDescription" class="form-control" rows="4" required></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createGroup()">Create</button>
        </div>
    </div>
</div>

<script>
    loadGroups();

    async function loadGroups() {
        try {
            const response = await fetch('../api/groups.php?action=get_all');
            const data = await response.json();
            
            const grid = document.getElementById('groupsGrid');
            
            if (data.success && data.groups && data.groups.length > 0) {
                grid.innerHTML = data.groups.map(group => `
                    <div class="group-card animate-scale-in">
                        <div class="group-cover"></div>
                        <div class="group-content">
                            <h3 class="group-name">${escapeHtml(group.name)}</h3>
                            <p style="color: var(--gray-dark); margin-bottom: 1rem;">${escapeHtml(group.description)}</p>
                            <div class="group-meta">
                                <span>👥 ${group.members_count || 0} members</span>
                            </div>
                            <button class="btn btn-primary btn-block" onclick="joinGroup(${group.id})">
                                Join Group
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>No Groups Yet</h3>
                        <p style="color: var(--gray-dark);">Create the first group!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function openCreateModal() {
        document.getElementById('createGroupModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createGroupModal').classList.remove('active');
    }

    async function createGroup() {
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDescription').value.trim();

        if (!name || !description) {
            alert('Please fill all fields');
            return;
        }

        try {
            const response = await fetch('../api/groups.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, description })
            });

            const data = await response.json();
            if (data.success) {
                closeCreateModal();
                alert('Group created! Waiting for admin approval.');
                document.getElementById('createGroupForm').reset();
                loadGroups();
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    async function joinGroup(groupId) {
        try {
            const response = await fetch('../api/groups.php?action=join', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ group_id: groupId })
            });

            const data = await response.json();
            if (data.success) {
                alert('Joined group!');
                loadGroups();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
