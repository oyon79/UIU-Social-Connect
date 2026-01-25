<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Content Moderation - Admin Panel';
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
        body { background: var(--gray-light); }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .admin-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); }
        .admin-nav { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .admin-nav-link { padding: 0.875rem 1.75rem; background: white; border-radius: 12px; text-decoration: none; color: var(--dark-text); font-weight: 600; transition: all 0.3s ease; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 0.5rem; }
        .admin-nav-link:hover { background: var(--primary-orange); color: white; transform: translateY(-2px); }
        .admin-nav-link.active { background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light)); color: white; }
        
        .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .content-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
        .content-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .content-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .content-meta { display: flex; align-items: center; gap: 1rem; }
        .content-text { padding: 1rem; background: var(--gray-light); border-radius: 12px; margin-bottom: 1rem; }
        .content-image { width: 100%; max-height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 1rem; }
        .content-actions { display: flex; gap: 0.75rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header animate-fade-in">
            <h1>🛡️ Content Moderation</h1>
            <p style="color: var(--gray-dark);">Manage all approved content</p>
        </div>

        <div class="admin-nav">
            <a href="index.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            <a href="approvals.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                </svg>
                Approvals
            </a>
            <a href="users.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Users
            </a>
            <a href="content.php" class="admin-nav-link active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                </svg>
                Content
            </a>
            <a href="documents.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                Documents
            </a>
            <a href="../api/auth.php?action=logout" class="admin-nav-link" style="background: #EF4444; color: white;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </div>

        <div class="content-grid" id="contentGrid">
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem; color: var(--gray-dark);">Loading content...</p>
            </div>
        </div>
    </div>

    <script>
        loadContent();

        async function loadContent() {
            try {
                const response = await fetch('../api/admin.php?action=get_all_content');
                const data = await response.json();
                
                const grid = document.getElementById('contentGrid');
                
                if (data.success && data.posts && data.posts.length > 0) {
                    grid.innerHTML = data.posts.map(post => `
                        <div class="content-card animate-scale-in">
                            <div class="content-header">
                                <div class="content-meta">
                                    <div class="avatar">
                                        <span>${post.author_name.charAt(0).toUpperCase()}</span>
                                    </div>
                                    <div>
                                        <strong>${escapeHtml(post.author_name)}</strong>
                                        <br><small style="color: var(--gray-dark);">${getTimeAgo(post.created_at)}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="content-text">${escapeHtml(post.content)}</div>
                            ${post.image_url ? `<img src="../${post.image_url}" class="content-image">` : ''}
                            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--gray-dark);">
                                <span>❤️ ${post.likes_count || 0} likes</span>
                                <span>💬 ${post.comments_count || 0} comments</span>
                            </div>
                            <div class="content-actions">
                                <button class="btn btn-danger" onclick="deletePost(${post.id})">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    grid.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                            <p style="color: var(--gray-dark);">No content found</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function deletePost(postId) {
            if (!confirm('Delete this post? This action cannot be undone.')) return;

            try {
                const response = await fetch('../api/posts.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId })
                });

                const data = await response.json();
                if (data.success) {
                    loadContent();
                    showAlert('Post deleted successfully', 'success');
                }
            } catch (error) {
                showAlert('Connection error', 'error');
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

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
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
