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
    
    /* Comments Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.2s ease;
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-light);
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        cursor: pointer;
        color: var(--gray-dark);
        line-height: 1;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }
    
    .modal-close:hover {
        background-color: var(--gray-light);
        color: var(--text-color);
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .comment-item {
        transition: background-color 0.2s;
    }
    
    .comment-item:hover {
        background-color: var(--gray-light);
    }
    
    .post-action-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .like-btn.active {
        color: var(--primary);
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
let hasMorePosts = true;
let loadingPosts = false;
let processingLike = new Set();
let processingComment = new Set();

// Centralized API request helper
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = 'Request failed';
            try {
                const errorData = JSON.parse(errorText);
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            }
            return { success: false, message: errorMessage };
        }
        
        const data = await response.json();
        return { success: data.success !== false, data: data, message: data.message || '' };
    } catch (error) {
        console.error('API request error:', error);
        return { 
            success: false, 
            message: error.message === 'Failed to fetch' ? 'Connection error. Please check your internet.' : 'An error occurred' 
        };
    }
}

// Load posts on page load
document.addEventListener('DOMContentLoaded', () => {
    loadPosts();
});

// Create post
document.getElementById('postBtn').addEventListener('click', async () => {
    const content = document.getElementById('postContent').value.trim();
    
    if (!content && !selectedImage) {
        showAlert('Please write something or add an image', 'error');
        return;
    }
    
    // Validate content length
    if (content.length > 5000) {
        showAlert('Post content is too long. Maximum 5000 characters.', 'error');
        return;
    }
    
    // Validate image if selected
    if (selectedImage) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (selectedImage.size > maxSize) {
            showAlert('Image size must be less than 5MB', 'error');
            return;
        }
        
        if (!allowedTypes.includes(selectedImage.type)) {
            showAlert('Invalid image type. Please use JPEG, PNG, GIF, or WebP.', 'error');
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('content', content);
    if (selectedImage) {
        formData.append('image', selectedImage);
    }
    
    const btn = document.getElementById('postBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner spinner-sm" style="border-top-color: white;"></div>';
    
    const result = await apiRequest('../api/posts.php?action=create', {
        method: 'POST',
        body: formData
    });
    
    if (result.success) {
        document.getElementById('postContent').value = '';
        removeImage();
        showAlert('Post created! Waiting for admin approval.', 'success');
        loadPosts(1); // Reload from page 1
    } else {
        showAlert(result.message || 'Failed to create post', 'error');
    }
    
    btn.disabled = false;
    btn.textContent = originalText;
});

// Image selection
document.getElementById('imageInput').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (file.size > maxSize) {
            showAlert('Image size must be less than 5MB', 'error');
            e.target.value = '';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            showAlert('Invalid image type. Please use JPEG, PNG, GIF, or WebP.', 'error');
            e.target.value = '';
            return;
        }
        
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
    if (loadingPosts) return;
    
    loadingPosts = true;
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    if (page === 1) {
        document.getElementById('feedLoading').style.display = 'block';
        document.getElementById('postsFeed').style.display = 'none';
    } else {
        loadMoreBtn.disabled = true;
        const originalText = loadMoreBtn.textContent;
        loadMoreBtn.innerHTML = '<div class="spinner spinner-sm"></div> Loading...';
    }
    
    const result = await apiRequest(`../api/posts.php?action=get_all&page=${page}`);
    
    loadingPosts = false;
    
    if (!result.success) {
        document.getElementById('feedLoading').style.display = 'none';
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = 'Load More Posts';
        showAlert(result.message || 'Failed to load posts', 'error');
        return;
    }
    
    const data = result.data;
    const feedContainer = document.getElementById('postsFeed');
    
    if (!data || data.success === false) {
        document.getElementById('feedLoading').style.display = 'none';
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = 'Load More Posts';
        showAlert(data?.message || 'Failed to load posts', 'error');
        return;
    }
    
    document.getElementById('feedLoading').style.display = 'none';
    feedContainer.style.display = 'block';
    
    if (page === 1) {
        feedContainer.innerHTML = '';
        hasMorePosts = true;
        loadMoreBtn.style.display = 'block'; // Make sure button is visible on reload
    }
    
    if (data.posts.length === 0) {
        if (page === 1) {
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
        } else {
            hasMorePosts = false;
            loadMoreBtn.style.display = 'none';
        }
        currentPage = page;
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = 'Load More Posts';
        return;
    }
    
    // Handle pagination completion
    if (data.posts.length < 10) {
        hasMorePosts = false;
        loadMoreBtn.style.display = 'none';
    }
    
    data.posts.forEach((post, index) => {
        const postElement = createPostElement(post);
        postElement.style.animationDelay = `${index * 0.1}s`;
        feedContainer.appendChild(postElement);
    });
    
    currentPage = page;
    loadMoreBtn.disabled = false;
    loadMoreBtn.textContent = 'Load More Posts';
    
    if (!hasMorePosts && page > 1) {
        loadMoreBtn.style.display = 'none';
    }
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post-card animate-slide-up';
    div.setAttribute('data-post-id', post.id);
    
    const timeAgo = getTimeAgo(post.created_at || '');
    const authorName = escapeHtml(post.author_name || 'Unknown');
    const authorRole = escapeHtml(post.author_role || '');
    const authorInitial = authorName.charAt(0).toUpperCase();
    const content = escapeHtml(post.content || '');
    const likesCount = post.likes_count || 0;
    const commentsCount = post.comments_count || 0;
    const sharesCount = post.shares_count || 0;
    const isLiked = post.user_liked > 0;
    
    div.innerHTML = `
        <div class="post-header">
            <div class="post-author">
                <div class="avatar">
                    <span>${authorInitial}</span>
                </div>
                <div class="post-author-info">
                    <h4>${authorName}</h4>
                    <p>${authorRole} • ${timeAgo}</p>
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
            ${content}
        </div>
        
        ${post.image_url ? `<img src="../${post.image_url}" class="post-image" alt="Post image" onerror="this.style.display='none'">` : ''}
        ${post.video_url ? `<video src="../${post.video_url}" class="post-video" controls onerror="this.style.display='none'"></video>` : ''}
        
        <div class="post-stats" data-post-id="${post.id}">
            <span class="likes-count">${likesCount} ${likesCount === 1 ? 'like' : 'likes'}</span>
            <span class="comments-count">${commentsCount} ${commentsCount === 1 ? 'comment' : 'comments'}</span>
            ${sharesCount > 0 ? `<span class="shares-count">${sharesCount} ${sharesCount === 1 ? 'share' : 'shares'}</span>` : ''}
        </div>
        
        <div class="post-actions">
            <button class="post-action-btn like-btn ${isLiked ? 'active' : ''}" data-post-id="${post.id}" onclick="toggleLike(${post.id})">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="${isLiked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
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
            
            <button class="post-action-btn share-btn" onclick="sharePost(${post.id})">
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

async function toggleLike(postId) {
    if (processingLike.has(postId)) return;
    
    processingLike.add(postId);
    const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
    const likesCountEl = document.querySelector(`.post-stats[data-post-id="${postId}"] .likes-count`);
    const svg = likeBtn.querySelector('svg');
    const originalFill = svg.getAttribute('fill');
    const originalClass = likeBtn.className;
    
    // Optimistic update
    const isCurrentlyLiked = likeBtn.classList.contains('active');
    const currentCount = parseInt(likesCountEl.textContent) || 0;
    const newCount = isCurrentlyLiked ? currentCount - 1 : currentCount + 1;
    
    likeBtn.classList.toggle('active');
    svg.setAttribute('fill', isCurrentlyLiked ? 'none' : 'currentColor');
    likesCountEl.textContent = `${newCount} ${newCount === 1 ? 'like' : 'likes'}`;
    likeBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('post_id', postId);
    
    const result = await apiRequest('../api/posts.php?action=like', {
        method: 'POST',
        body: formData
    });
    
    if (result.success && result.data) {
        const { liked, likes_count } = result.data;
        likesCountEl.textContent = `${likes_count} ${likes_count === 1 ? 'like' : 'likes'}`;
        
        if (liked) {
            likeBtn.classList.add('active');
            svg.setAttribute('fill', 'currentColor');
        } else {
            likeBtn.classList.remove('active');
            svg.setAttribute('fill', 'none');
        }
    } else {
        // Rollback on error
        likeBtn.className = originalClass;
        svg.setAttribute('fill', originalFill);
        likesCountEl.textContent = `${currentCount} ${currentCount === 1 ? 'like' : 'likes'}`;
        showAlert(result.message || 'Failed to update like', 'error');
    }
    
    likeBtn.disabled = false;
    processingLike.delete(postId);
}

async function showComments(postId) {
    if (processingComment.has(postId)) return;
    
    // Create or get comments modal
    let modal = document.getElementById('commentsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'commentsModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
                <div class="modal-header">
                    <h3>Comments</h3>
                    <button class="modal-close" onclick="closeCommentsModal()">&times;</button>
                </div>
                <div class="modal-body" id="commentsList">
                    <div class="text-center" style="padding: 2rem;">
                        <div class="spinner"></div>
                        <p>Loading comments...</p>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--gray-light);">
                    <div style="display: flex; gap: 0.5rem;">
                        <textarea id="commentInput" placeholder="Write a comment..." rows="2" style="flex: 1; padding: 0.75rem; border: 1px solid var(--gray-light); border-radius: 8px; resize: none;"></textarea>
                        <button class="btn btn-primary" id="submitCommentBtn" onclick="submitComment(${postId})">Post</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeCommentsModal();
        });
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                closeCommentsModal();
            }
        });
    }
    
    modal.style.display = 'block';
    const commentsList = document.getElementById('commentsList');
    commentsList.innerHTML = '<div class="text-center" style="padding: 2rem;"><div class="spinner"></div><p>Loading comments...</p></div>';
    
    processingComment.add(postId);
    
    const result = await apiRequest(`../api/posts.php?action=get_comments&post_id=${postId}`);
    
    processingComment.delete(postId);
    
    if (!result.success || !result.data) {
        commentsList.innerHTML = `
            <div class="text-center" style="padding: 2rem;">
                <p style="color: var(--error);">${result.message || 'Failed to load comments'}</p>
                <button class="btn btn-secondary" onclick="showComments(${postId})">Retry</button>
            </div>
        `;
        return;
    }
    
    const comments = result.data.comments || [];
    
    if (comments.length === 0) {
        commentsList.innerHTML = `
            <div class="text-center" style="padding: 2rem; color: var(--gray-dark);">
                <p>No comments yet. Be the first to comment!</p>
            </div>
        `;
    } else {
        commentsList.innerHTML = comments.map(comment => {
            const timeAgo = getTimeAgo(comment.created_at);
            return `
                <div class="comment-item" style="padding: 1rem; border-bottom: 1px solid var(--gray-light);">
                    <div style="display: flex; gap: 0.75rem;">
                        <div class="avatar" style="width: 32px; height: 32px; font-size: 14px;">
                            <span>${(comment.user_name || 'U').charAt(0).toUpperCase()}</span>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                ${escapeHtml(comment.user_name || 'Unknown')}
                                <span style="color: var(--gray-dark); font-weight: normal; font-size: 0.875rem; margin-left: 0.5rem;">${escapeHtml(comment.user_role || '')} • ${timeAgo}</span>
                            </div>
                            <div style="color: var(--text-color);">${escapeHtml(comment.content || '')}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Update submit button to use current postId
    const submitBtn = document.getElementById('submitCommentBtn');
    submitBtn.setAttribute('onclick', `submitComment(${postId})`);
    
    // Set up Enter key handler for comment input
    const commentInput = document.getElementById('commentInput');
    commentInput.onkeydown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!submitBtn.disabled) {
                submitComment(postId);
            }
        }
    };
    
    // Focus comment input
    commentInput.focus();
}

function closeCommentsModal() {
    const modal = document.getElementById('commentsModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('commentInput').value = '';
    }
}

async function submitComment(postId) {
    const input = document.getElementById('commentInput');
    const content = input.value.trim();
    const submitBtn = document.getElementById('submitCommentBtn');
    
    if (!content) {
        showAlert('Please write a comment', 'error');
        return;
    }
    
    if (processingComment.has(postId)) return;
    
    processingComment.add(postId);
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting...';
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('content', content);
    
    const result = await apiRequest('../api/posts.php?action=comment', {
        method: 'POST',
        body: formData
    });
    
    processingComment.delete(postId);
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    
    if (result.success && result.data) {
        input.value = '';
        showAlert('Comment posted!', 'success');
        
        // Refresh comments list
        await showComments(postId);
        
        // Update comment count in post
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        if (postElement) {
            const commentsCountEl = postElement.querySelector('.comments-count');
            if (commentsCountEl) {
                const currentCount = parseInt(commentsCountEl.textContent) || 0;
                const newCount = currentCount + 1;
                commentsCountEl.textContent = `${newCount} ${newCount === 1 ? 'comment' : 'comments'}`;
            }
        }
    } else {
        showAlert(result.message || 'Failed to post comment', 'error');
    }
}

async function sharePost(postId) {
    const postUrl = `${window.location.origin}${window.location.pathname}?post=${postId}`;
    
    try {
        if (navigator.share) {
            // Use Web Share API if available
            await navigator.share({
                title: 'Check out this post',
                text: 'Check out this post on UIU Social Connect',
                url: postUrl
            });
            showAlert('Post shared!', 'success');
        } else if (navigator.clipboard) {
            // Fallback to clipboard
            await navigator.clipboard.writeText(postUrl);
            showAlert('Post link copied to clipboard!', 'success');
        } else {
            // Final fallback - select text
            const textArea = document.createElement('textarea');
            textArea.value = postUrl;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showAlert('Post link copied to clipboard!', 'success');
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            showAlert('Failed to share post', 'error');
        }
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
    if (!loadingPosts && hasMorePosts) {
        loadPosts(currentPage + 1);
    }
});

</script>

</body>
</html>
