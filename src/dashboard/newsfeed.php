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

$pageTitle = 'Newsfeed - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body {
        background-color: var(--gray-light);
        margin: 0;
        padding: 0;
    }
    
    .main-container {
        margin-left: 280px;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    
    .content-wrapper {
        max-width: 680px;
        margin: 0 auto;
        padding: 2rem 1rem;
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
    
    <div class="content-wrapper">
        <!-- Create Post Box -->
        <div class="create-post-box animate-fade-in">
            <div class="create-post-input">
                <div class="avatar">
                    <span><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                </div>
                <textarea 
                    id="postContent" 
                    placeholder="What's on your mind, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>?"
                    class="create-post-input-field"
                    rows="3"
                ></textarea>
            </div>
            
            <div class="create-post-actions">
                <button class="create-post-action-btn" onclick="document.getElementById('imageInput').click()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Photo</span>
                </button>
                
                <button class="create-post-action-btn" onclick="document.getElementById('videoInput').click()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                    </svg>
                    <span>Video</span>
                </button>
                
                <button class="btn btn-primary" id="postBtn" style="margin-left: auto;">Post</button>
            </div>
            
            <input type="file" id="imageInput" accept="image/*" style="display: none;">
            <input type="file" id="videoInput" accept="video/*" style="display: none;">
            
            <!-- Image Preview -->
            <div id="imagePreview" style="display: none; margin-top: 1rem;">
                <img id="previewImg" style="max-width: 100%; border-radius: 12px;">
                <button class="btn btn-ghost btn-sm" onclick="removeImage()" style="margin-top: 0.5rem;">Remove</button>
            </div>
        </div>

        <!-- Feed Loading -->
        <div id="feedLoading" class="animate-slide-up" style="animation-delay: 0.1s;">
            <div class="card">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-card" style="margin-top: 1rem;"></div>
            </div>
        </div>

        <!-- Posts Feed -->
        <div id="postsFeed" style="display: none;">
            <!-- Posts will be loaded here via JavaScript -->
        </div>

        <!-- Load More -->
        <div class="text-center" style="margin: 2rem 0;">
            <button class="btn btn-secondary" id="loadMoreBtn">Load More Posts</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let selectedImage = null;

// Load posts on page load
document.addEventListener('DOMContentLoaded', () => {
    loadPosts();
});

// Create post
document.getElementById('postBtn').addEventListener('click', async () => {
    const content = document.getElementById('postContent').value.trim();
    
    if (!content && !selectedImage) {
        alert('Please write something or add an image');
        return;
    }
    
    const formData = new FormData();
    formData.append('content', content);
    if (selectedImage) {
        formData.append('image', selectedImage);
    }
    
    const btn = document.getElementById('postBtn');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner spinner-sm" style="border-top-color: white;"></div>';
    
    try {
        const response = await fetch('../api/posts.php?action=create', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('postContent').value = '';
            removeImage();
            showAlert('Post created! Waiting for admin approval.', 'success');
            loadPosts();
        } else {
            showAlert(data.message || 'Failed to create post', 'error');
        }
    } catch (error) {
        showAlert('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Post';
    }
});

// Image selection
document.getElementById('imageInput').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedImage = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

function removeImage() {
    selectedImage = null;
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}

// Load posts
async function loadPosts(page = 1) {
    try {
        const response = await fetch(`../api/posts.php?action=get_all&page=${page}`);
        const data = await response.json();
        
        if (data.success) {
            const feedContainer = document.getElementById('postsFeed');
            document.getElementById('feedLoading').style.display = 'none';
            feedContainer.style.display = 'block';
            
            if (page === 1) {
                feedContainer.innerHTML = '';
            }
            
            if (data.posts.length === 0 && page === 1) {
                feedContainer.innerHTML = `
                    <div class="card text-center" style="padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: var(--gray-dark);">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <h3>No posts yet</h3>
                        <p style="color: var(--gray-dark);">Be the first to share something!</p>
                    </div>
                `;
                return;
            }
            
            data.posts.forEach((post, index) => {
                const postElement = createPostElement(post);
                postElement.style.animationDelay = `${index * 0.1}s`;
                feedContainer.appendChild(postElement);
            });
            
            currentPage = page;
        }
    } catch (error) {
        console.error('Error loading posts:', error);
    }
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post-card animate-slide-up';
    
    const timeAgo = getTimeAgo(post.created_at);
    
    div.innerHTML = `
        <div class="post-header">
            <div class="post-author">
                <div class="avatar">
                    <span>${post.author_name.charAt(0).toUpperCase()}</span>
                </div>
                <div class="post-author-info">
                    <h4>${escapeHtml(post.author_name)}</h4>
                    <p>${escapeHtml(post.author_role)} • ${timeAgo}</p>
                </div>
            </div>
            <button class="post-menu-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                </svg>
            </button>
        </div>
        
        <div class="post-content">
            ${escapeHtml(post.content)}
        </div>
        
        ${post.image_url ? `<img src="../${post.image_url}" class="post-image" alt="Post image">` : ''}
        ${post.video_url ? `<video src="../${post.video_url}" class="post-video" controls></video>` : ''}
        
        <div class="post-stats">
            <span>${post.likes_count || 0} likes</span>
            <span>${post.comments_count || 0} comments • ${post.shares_count || 0} shares</span>
        </div>
        
        <div class="post-actions">
            <button class="post-action-btn ${post.user_liked ? 'active' : ''}" onclick="toggleLike(${post.id})">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="${post.user_liked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span>Like</span>
            </button>
            
            <button class="post-action-btn" onclick="showComments(${post.id})">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Comment</span>
            </button>
            
            <button class="post-action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                <span>Share</span>
            </button>
        </div>
    `;
    
    return div;
}

function toggleLike(postId) {
    // Implement like functionality
    console.log('Toggle like for post:', postId);
}

function showComments(postId) {
    // Implement comments modal
    console.log('Show comments for post:', postId);
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
    alert.className = `alert alert-${type} animate-slide-down`;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `<span>${message}</span>`;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Load more posts
document.getElementById('loadMoreBtn').addEventListener('click', () => {
    loadPosts(currentPage + 1);
});
</script>

</body>
</html>
