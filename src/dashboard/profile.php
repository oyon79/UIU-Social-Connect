<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$currentUserId = $_SESSION['user_id'];

// Get profile user ID (viewing own profile or another user's)
$profileUserId = isset($_GET['id']) ? intval($_GET['id']) : $currentUserId;
$isOwnProfile = ($profileUserId === $currentUserId);

// Get user profile data
$sql = "SELECT id, full_name, email, role, bio, profile_picture, cover_photo, student_id, created_at 
        FROM users WHERE id = ? AND is_approved = 1";
$user = $db->query($sql, [$profileUserId]);

if (!$user) {
    header('Location: newsfeed.php');
    exit;
}

$user = $user[0];

// Get user stats
$postsSql = "SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND is_approved = 1";
$postsCount = $db->query($postsSql, [$profileUserId])[0]['count'];

$friendsSql = "SELECT COUNT(*) as count FROM friendships WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'";
$friendsCount = $db->query($friendsSql, [$profileUserId, $profileUserId])[0]['count'];

// Check friendship status
$friendshipStatus = 'none';
if (!$isOwnProfile) {
    $checkSql = "SELECT status, user_id FROM friendships 
                 WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
    $friendship = $db->query($checkSql, [$currentUserId, $profileUserId, $profileUserId, $currentUserId]);

    if ($friendship) {
        $friendshipStatus = $friendship[0]['status'];
        if ($friendshipStatus === 'pending' && $friendship[0]['user_id'] === $currentUserId) {
            $friendshipStatus = 'pending_sent';
        } elseif ($friendshipStatus === 'pending') {
            $friendshipStatus = 'pending_received';
        }
    }
}

$pageTitle = htmlspecialchars($user['full_name']) . ' - Profile';
require_once '../includes/header.php';
?>

<style>
    body {
        background-color: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
    }

    .profile-wrapper {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .profile-cover {
        width: 100%;
        height: 320px;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        margin-bottom: -80px;
        box-shadow: var(--shadow-md);
    }

    .profile-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-cover-edit {
        position: absolute;
        bottom: 1.5rem;
        right: 1.5rem;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .profile-cover-edit:hover {
        background: rgba(0, 0, 0, 0.7);
        transform: translateY(-2px);
    }

    .profile-header {
        background: white;
        border-radius: 20px;
        padding: 5rem 2rem 2rem;
        box-shadow: var(--shadow-md);
        position: relative;
        margin-bottom: 1.5rem;
    }

    .profile-avatar-container {
        position: absolute;
        top: -80px;
        left: 2rem;
    }

    .profile-avatar {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: 700;
        border: 6px solid white;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-avatar-edit {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 40px;
        height: 40px;
        background: var(--primary-orange);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 3px solid white;
        transition: all 0.3s ease;
    }

    .profile-avatar-edit:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.4);
    }

    .profile-info {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .profile-details h1 {
        margin-bottom: 0.5rem;
        font-size: 2rem;
    }

    .profile-meta {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        color: var(--gray-dark);
        margin-bottom: 1rem;
    }

    .profile-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .profile-bio {
        color: var(--dark-text);
        line-height: 1.6;
        margin-top: 1rem;
        max-width: 600px;
    }

    .profile-stats {
        display: flex;
        gap: 2rem;
        margin-top: 1.5rem;
    }

    .profile-stat {
        text-align: center;
    }

    .profile-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-orange);
        display: block;
    }

    .profile-stat-label {
        font-size: 0.875rem;
        color: var(--gray-dark);
    }

    .profile-actions {
        display: flex;
        gap: 1rem;
    }

    .profile-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }

    .profile-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .info-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
    }

    .info-card h3 {
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--gray-light);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-item-icon {
        width: 40px;
        height: 40px;
        background: var(--gray-light);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-orange);
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .main-container {
            margin-left: 0;
        }

        .profile-cover {
            height: 200px;
            margin-bottom: -60px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            font-size: 3rem;
        }

        .profile-info {
            flex-direction: column;
        }

        .profile-content {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>

    <div class="profile-wrapper">
        <!-- Cover Photo -->
        <div class="profile-cover animate-fade-in">
            <?php if ($user['cover_photo']): ?>
                <img src="../<?php echo htmlspecialchars($user['cover_photo']); ?>" alt="Cover">
            <?php endif; ?>

            <?php if ($isOwnProfile): ?>
                <button class="profile-cover-edit" onclick="document.getElementById('coverInput').click()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    Edit Cover
                </button>
                <input type="file" id="coverInput" accept="image/*" style="display: none;">
            <?php endif; ?>
        </div>

        <!-- Profile Header -->
        <div class="profile-header animate-slide-up">
            <div class="profile-avatar-container">
                <div class="profile-avatar">
                    <?php if ($user['profile_picture']): ?>
                        <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                    <?php endif; ?>

                    <?php if ($isOwnProfile): ?>
                        <div class="profile-avatar-edit" onclick="document.getElementById('avatarInput').click()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-info">
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>

                    <div class="profile-meta">
                        <span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>

                        <?php if ($user['student_id']): ?>
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                ID: <?php echo htmlspecialchars($user['student_id']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($user['bio']): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo $postsCount; ?></span>
                            <span class="profile-stat-label">Posts</span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo $friendsCount; ?></span>
                            <span class="profile-stat-label">Friends</span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($isOwnProfile): ?>
                        <button class="btn btn-primary" onclick="openEditModal()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Profile
                        </button>
                    <?php else: ?>
                        <?php if ($friendshipStatus === 'none'): ?>
                            <button class="btn btn-primary" onclick="sendFriendRequest(<?php echo $profileUserId; ?>)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                Add Friend
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_sent'): ?>
                            <button class="btn btn-secondary" disabled>
                                Request Sent
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_received'): ?>
                            <button class="btn btn-success" onclick="acceptFriendRequest(<?php echo $profileUserId; ?>)">
                                Accept Request
                            </button>
                        <?php elseif ($friendshipStatus === 'accepted'): ?>
                            <button class="btn btn-secondary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Friends
                            </button>
                        <?php endif; ?>

                        <button class="btn btn-outline" onclick="window.location.href='messages.php?user=<?php echo $profileUserId; ?>'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Message
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="info-card animate-slide-up" style="animation-delay: 0.1s;">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        About
                    </h3>

                    <div class="info-item">
                        <div class="info-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <strong>Email</strong><br>
                            <span style="font-size: 0.875rem; color: var(--gray-dark);">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div>
                            <strong>Joined</strong><br>
                            <span style="font-size: 0.875rem; color: var(--gray-dark);">
                                <?php echo date('F Y', strtotime($user['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts Feed -->
            <div>
                <div class="card animate-slide-up" style="animation-delay: 0.2s; margin-bottom: 1.5rem;">
                    <h3 style="margin-bottom: 1rem;">Posts</h3>
                    <div id="userPosts">
                        <div class="text-center" style="padding: 2rem;">
                            <div class="spinner"></div>
                            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading posts...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editModal" class="modal">
    <div class="modal-backdrop" onclick="closeEditModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Profile</h3>
            <button class="modal-close" onclick="closeEditModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="editProfileForm">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="editFullName" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea id="editBio" class="form-control" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Student/Employee ID</label>
                    <input type="text" id="editStudentId" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
        </div>
    </div>
</div>

<script>
    const profileUserId = <?php echo $profileUserId; ?>;
    const isOwnProfile = <?php echo $isOwnProfile ? 'true' : 'false'; ?>;

    // Load user posts
    loadUserPosts();

    async function loadUserPosts() {
        try {
            const response = await fetch(`../api/posts.php?action=get_user_posts&user_id=${profileUserId}`);
            const data = await response.json();

            const container = document.getElementById('userPosts');

            if (data.success && data.posts.length > 0) {
                container.innerHTML = data.posts.map(post => createPostHTML(post)).join('');
            } else {
                container.innerHTML = `
                <div class="text-center" style="padding: 3rem;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: var(--gray-dark);">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                    </svg>
                    <p style="color: var(--gray-dark);">No posts yet</p>
                </div>
            `;
            }
        } catch (error) {
            console.error('Error loading posts:', error);
        }
    }

    function createPostHTML(post) {
        return `
        <div class="post-card" style="margin-bottom: 1rem;">
            <div class="post-header">
                <div class="post-author">
                    <div class="avatar">
                        <span>${post.author_name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div class="post-author-info">
                        <h4>${escapeHtml(post.author_name)}</h4>
                        <p>${escapeHtml(post.author_role)} • ${getTimeAgo(post.created_at)}</p>
                    </div>
                </div>
            </div>
            <div class="post-content">${escapeHtml(post.content)}</div>
            ${post.image_url ? `<img src="../${post.image_url}" class="post-image">` : ''}
            <div class="post-stats">
                <span>${post.likes_count || 0} likes</span>
                <span>${post.comments_count || 0} comments</span>
            </div>
        </div>
    `;
    }

    function openEditModal() {
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    async function saveProfile() {
        const formData = {
            full_name: document.getElementById('editFullName').value,
            bio: document.getElementById('editBio').value,
            student_id: document.getElementById('editStudentId').value
        };

        try {
            const response = await fetch('../api/users.php?action=update_profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update profile');
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    // Handle photo uploads
    document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (file) await uploadPhoto(file, 'profile');
    });

    document.getElementById('coverInput')?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (file) await uploadPhoto(file, 'cover');
    });

    async function uploadPhoto(file, type) {
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('type', type);

        try {
            const response = await fetch('../api/users.php?action=upload_photo', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Upload failed');
            }
        } catch (error) {
            alert('Connection error');
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

    async function sendFriendRequest(userId) {
        try {
            const response = await fetch('../api/users.php?action=send_friend_request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    friend_id: userId
                })
            });

            const data = await response.json();
            if (data.success) location.reload();
        } catch (error) {
            alert('Connection error');
        }
    }

    async function acceptFriendRequest(userId) {
        try {
            const response = await fetch('../api/users.php?action=accept_friend_request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    friend_id: userId
                })
            });

            const data = await response.json();
            if (data.success) location.reload();
        } catch (error) {
            alert('Connection error');
        }
    }
</script>

</body>

</html>