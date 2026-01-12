<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Notice Board - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 2rem; }
    .notices-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
    .notices-list { display: flex; flex-direction: column; gap: 1.5rem; }
    .notice-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: var(--shadow-md); border-left: 4px solid var(--primary-orange); }
    .notice-card.urgent { border-left-color: var(--error); }
    .notice-priority { padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 600; display: inline-block; margin-bottom: 1rem; }
    .notice-priority.urgent { background: #FEE2E2; color: var(--error); }
    .notice-priority.normal { background: #EFF6FF; color: #3B82F6; }
    @media (max-width: 768px) { .main-container { margin-left: 0; } }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="notices-header animate-fade-in">
        <div>
            <h1>📢 Notice Board</h1>
            <p style="color: var(--gray-dark);">Important announcements and updates</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Post Notice
        </button>
    </div>

    <div class="notices-list" id="noticesList">
        <div class="text-center" style="padding: 3rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading notices...</p>
        </div>
    </div>
</div>

<div id="createNoticeModal" class="modal">
    <div class="modal-backdrop" onclick="closeCreateModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Post Notice</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createNoticeForm">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" id="noticeTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Content</label>
                    <textarea id="noticeContent" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select id="noticePriority" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createNotice()">Post Notice</button>
        </div>
    </div>
</div>

<script>
    loadNotices();

    async function loadNotices() {
        try {
            const response = await fetch('../api/notices.php?action=get_all');
            const data = await response.json();
            
            console.log('Notices API Response:', data); // Debug log
            
            const list = document.getElementById('noticesList');
            
            if (data.success && data.notices && data.notices.length > 0) {
                list.innerHTML = data.notices.map(notice => {
                    const priorityClass = notice.priority === 'urgent' ? 'urgent' : 
                                        notice.priority === 'high' ? 'high' : 'normal';
                    return `
                    <div class="notice-card ${priorityClass} animate-slide-up">
                        <span class="notice-priority ${priorityClass}">${(notice.priority || 'normal').toUpperCase()}</span>
                        <h3 style="margin-bottom: 0.75rem;">${escapeHtml(notice.title || 'Untitled Notice')}</h3>
                        <p style="color: var(--gray-dark); margin-bottom: 1rem;">${escapeHtml(notice.content || 'No content')}</p>
                        <div style="font-size: 0.875rem; color: var(--gray-dark);">
                            Posted by ${escapeHtml(notice.posted_by || 'Admin')} • ${getTimeAgo(notice.created_at)}
                        </div>
                    </div>
                `;
                }).join('');
            } else {
                console.log('No notices found. Data:', data); // Debug log
                list.innerHTML = `
                    <div class="text-center" style="padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <h3>No Notices</h3>
                        <p style="color: var(--gray-dark);">No announcements at the moment</p>
                        ${!data.success ? `<p style="color: var(--error); margin-top: 1rem;">Error: ${data.message || 'Failed to load notices'}</p>` : ''}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading notices:', error);
            const list = document.getElementById('noticesList');
            list.innerHTML = `
                <div class="text-center" style="padding: 3rem;">
                    <h3>Error Loading Notices</h3>
                    <p style="color: var(--error);">${error.message}</p>
                    <button class="btn btn-primary" onclick="loadNotices()" style="margin-top: 1rem;">Retry</button>
                </div>
            `;
        }
    }

    function openCreateModal() {
        document.getElementById('createNoticeModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createNoticeModal').classList.remove('active');
    }

    async function createNotice() {
        const title = document.getElementById('noticeTitle').value.trim();
        const content = document.getElementById('noticeContent').value.trim();
        const priority = document.getElementById('noticePriority').value;

        if (!title || !content) {
            alert('Please fill all fields');
            return;
        }

        try {
            const response = await fetch('../api/notices.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, content, priority })
            });

            const data = await response.json();
            if (data.success) {
                closeCreateModal();
                alert('Notice posted! Waiting for admin approval.');
                document.getElementById('createNoticeForm').reset();
                loadNotices();
            } else {
                alert(data.message || 'Failed to post notice');
            }
        } catch (error) {
            alert('Connection error. Please try again.');
        }
    }

    function getTimeAgo(timestamp) {
        const now = new Date();
        const postTime = new Date(timestamp);
        const diffInSeconds = Math.floor((now - postTime) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        return postTime.toLocaleDateString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
