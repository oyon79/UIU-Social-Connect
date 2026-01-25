<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Approvals - Admin Panel';
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

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--gray-medium);
        }

        .tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-dark);
            transition: all 0.3s ease;
            position: relative;
        }

        .tab:hover {
            color: var(--primary-orange);
        }

        .tab.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
        }

        .tab-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            min-width: 20px;
            height: 20px;
            background: var(--error);
            color: white;
            border-radius: 10px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .approval-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .approval-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .approval-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .approval-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .approval-actions {
            display: flex;
            gap: 0.75rem;
        }

        .approval-content {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 12px;
        }

        .approval-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
        }

        .empty-state svg {
            margin-bottom: 1rem;
            color: var(--gray-dark);
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header animate-fade-in">
            <h1>✅ Approval Management</h1>
            <p style="color: var(--gray-dark);">Review and approve pending content</p>
        </div>

        <!-- Navigation -->
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
            <a href="approvals.php" class="admin-nav-link active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
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
            <a href="content.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
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

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('users')">
                Users
                <span class="tab-badge" id="usersCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('posts')">
                Posts
                <span class="tab-badge" id="postsCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('events')">
                Events
                <span class="tab-badge" id="eventsCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('jobs')">
                Jobs
                <span class="tab-badge" id="jobsCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('notices')">
                Notices
                <span class="tab-badge" id="noticesCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('marketplace')">
                Marketplace
                <span class="tab-badge" id="marketplaceCount">0</span>
            </button>
            <button class="tab" onclick="switchTab('groups')">
                Groups
                <span class="tab-badge" id="groupsCount">0</span>
            </button>
        </div>

        <!-- Tab Contents -->
        <div id="usersTab" class="tab-content active">
            <div id="usersList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading users...</p>
                </div>
            </div>
        </div>

        <div id="postsTab" class="tab-content">
            <div id="postsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading posts...</p>
                </div>
            </div>
        </div>

        <div id="eventsTab" class="tab-content">
            <div id="eventsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading events...</p>
                </div>
            </div>
        </div>

        <div id="jobsTab" class="tab-content">
            <div id="jobsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading jobs...</p>
                </div>
            </div>
        </div>

        <div id="noticesTab" class="tab-content">
            <div id="noticesList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading notices...</p>
                </div>
            </div>
        </div>

        <div id="marketplaceTab" class="tab-content">
            <div id="marketplaceList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading marketplace items...</p>
                </div>
            </div>
        </div>

        <div id="groupsTab" class="tab-content">
            <div id="groupsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading groups...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'users';

        // Load data on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();
            loadPosts();
            loadEvents();
            loadJobs();
            loadNotices();
            loadMarketplace();
            loadGroups();
        });

        function switchTab(tab) {
            currentTab = tab;

            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.closest('.tab').classList.add('active');

            // Update tab contents
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + 'Tab').classList.add('active');
        }

        async function loadUsers() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_users');
                const data = await response.json();

                const container = document.getElementById('usersList');
                document.getElementById('usersCount').textContent = data.users?.length || 0;

                if (data.success && data.users && data.users.length > 0) {
                    container.innerHTML = data.users.map(user => `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${user.full_name.charAt(0).toUpperCase()}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(user.full_name)}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(user.email)} • ${escapeHtml(user.role)}
                                            ${user.student_id ? ' • ID: ' + escapeHtml(user.student_id) : ''}
                                        </p>
                                        <p style="color: var(--gray-dark); font-size: 0.8125rem;">
                                            Registered ${getTimeAgo(user.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveUser(${user.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectUser(${user.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending user approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function loadPosts() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_posts');
                const data = await response.json();

                const container = document.getElementById('postsList');
                document.getElementById('postsCount').textContent = data.posts?.length || 0;

                if (data.success && data.posts && data.posts.length > 0) {
                    container.innerHTML = data.posts.map(post => `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${post.author_name.charAt(0).toUpperCase()}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(post.author_name)}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(post.author_role)} • ${getTimeAgo(post.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approvePost(${post.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectPost(${post.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                ${escapeHtml(post.content)}
                            </div>
                            ${post.image_url ? `<img src="../${post.image_url}" class="approval-image" alt="Post image">` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending post approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading posts:', error);
            }
        }

        async function loadEvents() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_events');
                const data = await response.json();

                const container = document.getElementById('eventsList');
                document.getElementById('eventsCount').textContent = data.events?.length || 0;

                if (data.success && data.events && data.events.length > 0) {
                    container.innerHTML = data.events.map(event => {
                        const eventDate = new Date(event.event_date + ' ' + (event.event_time || '00:00:00'));
                        const formattedDate = eventDate.toLocaleDateString('en-US', { 
                            weekday: 'short', 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric' 
                        });
                        const formattedTime = event.event_time ? new Date('2000-01-01 ' + event.event_time).toLocaleTimeString('en-US', { 
                            hour: 'numeric', 
                            minute: '2-digit' 
                        }) : '';
                        
                        return `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${event.organizer_name ? event.organizer_name.charAt(0).toUpperCase() : 'E'}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(event.title || 'Untitled Event')}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(event.organizer_name || 'Unknown')} • ${getTimeAgo(event.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveEvent(${event.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectEvent(${event.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                <p style="margin-bottom: 0.75rem;"><strong>Description:</strong></p>
                                <p style="margin-bottom: 1rem;">${escapeHtml(event.description || 'No description')}</p>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.875rem; color: var(--gray-dark);">
                                    <span>📅 ${formattedDate}${formattedTime ? ' at ' + formattedTime : ''}</span>
                                    <span>📍 ${escapeHtml(event.location || 'TBA')}</span>
                                    ${event.category ? `<span>🏷️ ${escapeHtml(event.category)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending event approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading events:', error);
                document.getElementById('eventsList').innerHTML = `
                    <div class="empty-state">
                        <h3>Error Loading Events</h3>
                        <p style="color: var(--gray-dark);">Please refresh the page</p>
                    </div>
                `;
            }
        }

        async function loadJobs() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_jobs');
                const data = await response.json();

                const container = document.getElementById('jobsList');
                document.getElementById('jobsCount').textContent = data.jobs?.length || 0;

                if (data.success && data.jobs && data.jobs.length > 0) {
                    container.innerHTML = data.jobs.map(job => {
                        const deadlineDate = job.deadline ? new Date(job.deadline).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric' 
                        }) : 'Not specified';
                        
                        return `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${job.poster_name ? job.poster_name.charAt(0).toUpperCase() : 'J'}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(job.title || 'Untitled Job')}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(job.poster_name || 'Unknown')} • ${getTimeAgo(job.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveJob(${job.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectJob(${job.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                <p style="margin-bottom: 0.75rem;"><strong>Company:</strong> ${escapeHtml(job.company || 'N/A')}</p>
                                <p style="margin-bottom: 0.75rem;"><strong>Description:</strong></p>
                                <p style="margin-bottom: 1rem;">${escapeHtml(job.description || 'No description')}</p>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.875rem; color: var(--gray-dark);">
                                    <span>💼 ${escapeHtml(job.job_type || 'N/A')}</span>
                                    <span>📍 ${escapeHtml(job.location || 'TBA')}</span>
                                    ${job.deadline ? `<span>📅 Deadline: ${deadlineDate}</span>` : ''}
                                    ${job.salary_range ? `<span>💰 ${escapeHtml(job.salary_range)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending job approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading jobs:', error);
                document.getElementById('jobsList').innerHTML = `
                    <div class="empty-state">
                        <h3>Error Loading Jobs</h3>
                        <p style="color: var(--gray-dark);">Please refresh the page</p>
                    </div>
                `;
            }
        }

        async function approveUser(userId) {
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
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_user:', text);
                    showAlert('Server error while approving user', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User approved successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve', 'error');
                }
            } catch (error) {
                console.error('Approve user request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectUser(userId) {
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
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_user:', text);
                    showAlert('Server error while rejecting user', 'error');
                    return;
                }

                if (data.success) {
                    loadUsers();
                    showAlert('User rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject', 'error');
                }
            } catch (error) {
                console.error('Reject user request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function approvePost(postId) {
            try {
                const response = await fetch('../api/approvals.php?action=approve_post', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: postId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_post:', text);
                    showAlert('Server error while approving post', 'error');
                    return;
                }

                if (data.success) {
                    loadPosts();
                    showAlert('Post approved', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve post', 'error');
                }
            } catch (error) {
                console.error('Approve post request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectPost(postId) {
            if (!confirm('Reject this post?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_post', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: postId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_post:', text);
                    showAlert('Server error while rejecting post', 'error');
                    return;
                }

                if (data.success) {
                    loadPosts();
                    showAlert('Post rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject post', 'error');
                }
            } catch (error) {
                console.error('Reject post request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function approveEvent(eventId) {
            if (!confirm('Approve this event?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_event', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: eventId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_event:', text);
                    showAlert('Server error while approving event', 'error');
                    return;
                }

                if (data.success) {
                    loadEvents();
                    showAlert('Event approved successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve event', 'error');
                }
            } catch (error) {
                console.error('Approve event request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectEvent(eventId) {
            if (!confirm('Reject this event? This will delete the event.')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_event', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: eventId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_event:', text);
                    showAlert('Server error while rejecting event', 'error');
                    return;
                }

                if (data.success) {
                    loadEvents();
                    showAlert('Event rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject event', 'error');
                }
            } catch (error) {
                console.error('Reject event request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function approveJob(jobId) {
            if (!confirm('Approve this job?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_job', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        job_id: jobId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_job:', text);
                    showAlert('Server error while approving job', 'error');
                    return;
                }

                if (data.success) {
                    loadJobs();
                    showAlert('Job approved successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve job', 'error');
                }
            } catch (error) {
                console.error('Approve job request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectJob(jobId) {
            if (!confirm('Reject this job? This will delete the job posting.')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_job', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        job_id: jobId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_job:', text);
                    showAlert('Server error while rejecting job', 'error');
                    return;
                }

                if (data.success) {
                    loadJobs();
                    showAlert('Job rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject job', 'error');
                }
            } catch (error) {
                console.error('Reject job request failed:', error);
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

        async function loadNotices() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_notices');
                const data = await response.json();

                const container = document.getElementById('noticesList');
                document.getElementById('noticesCount').textContent = data.notices?.length || 0;

                if (data.success && data.notices && data.notices.length > 0) {
                    container.innerHTML = data.notices.map(notice => {
                        const priorityClass = notice.priority === 'urgent' ? 'urgent' : 
                                           notice.priority === 'high' ? 'high' : 'normal';
                        const priorityColor = notice.priority === 'urgent' ? 'var(--error)' : 
                                            notice.priority === 'high' ? 'var(--warning)' : '#3B82F6';
                        
                        return `
                        <div class="approval-card animate-slide-up" style="border-left: 4px solid ${priorityColor};">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${notice.poster_name ? notice.poster_name.charAt(0).toUpperCase() : 'N'}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(notice.title || 'Untitled Notice')}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(notice.poster_name || 'Unknown')} • ${getTimeAgo(notice.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveNotice(${notice.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectNotice(${notice.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                <p style="margin-bottom: 0.75rem;">
                                    <span style="padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 600; background: ${priorityColor === 'var(--error)' ? '#FEE2E2' : priorityColor === 'var(--warning)' ? '#FFFBEB' : '#EFF6FF'}; color: ${priorityColor};">
                                        ${(notice.priority || 'normal').toUpperCase()}
                                    </span>
                                </p>
                                <p style="margin-bottom: 1rem;">${escapeHtml(notice.content || 'No content')}</p>
                                ${notice.category ? `<p style="font-size: 0.875rem; color: var(--gray-dark);">Category: ${escapeHtml(notice.category)}</p>` : ''}
                            </div>
                        </div>
                    `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending notice approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading notices:', error);
                document.getElementById('noticesList').innerHTML = `
                    <div class="empty-state">
                        <h3>Error Loading Notices</h3>
                        <p style="color: var(--gray-dark);">Please refresh the page</p>
                    </div>
                `;
            }
        }

        async function approveNotice(noticeId) {
            if (!confirm('Approve this notice?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_notice', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notice_id: noticeId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_notice:', text);
                    showAlert('Server error while approving notice', 'error');
                    return;
                }

                if (data.success) {
                    loadNotices();
                    showAlert('Notice approved successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve notice', 'error');
                }
            } catch (error) {
                console.error('Approve notice request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectNotice(noticeId) {
            if (!confirm('Reject this notice? This will delete the notice.')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_notice', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notice_id: noticeId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_notice:', text);
                    showAlert('Server error while rejecting notice', 'error');
                    return;
                }

                if (data.success) {
                    loadNotices();
                    showAlert('Notice rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject notice', 'error');
                }
            } catch (error) {
                console.error('Reject notice request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function loadMarketplace() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_marketplace');
                const data = await response.json();

                const container = document.getElementById('marketplaceList');
                document.getElementById('marketplaceCount').textContent = data.items?.length || 0;

                if (data.success && data.items && data.items.length > 0) {
                    container.innerHTML = data.items.map(item => {
                        return `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${item.seller_name ? item.seller_name.charAt(0).toUpperCase() : 'S'}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(item.title || 'Untitled Item')}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            ${escapeHtml(item.seller_name || 'Unknown')} • ${getTimeAgo(item.created_at)}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveMarketplace(${item.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectMarketplace(${item.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                ${item.image_url ? `
                                <div style="margin-bottom: 1rem;">
                                    <img src="../${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}" style="width: 100%; max-width: 300px; height: auto; border-radius: 8px;">
                                </div>
                                ` : ''}
                                <p style="margin-bottom: 0.75rem;"><strong>Price:</strong> ৳${parseFloat(item.price || 0).toFixed(2)}</p>
                                <p style="margin-bottom: 0.75rem;"><strong>Category:</strong> ${escapeHtml(item.category || 'Other')}</p>
                                <p style="margin-bottom: 0.75rem;"><strong>Condition:</strong> ${escapeHtml(item.condition_status || 'good')}</p>
                                <p style="margin-bottom: 1rem;"><strong>Description:</strong></p>
                                <p>${escapeHtml(item.description || 'No description provided.')}</p>
                            </div>
                        </div>
                    `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending marketplace approvals</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading marketplace:', error);
                document.getElementById('marketplaceList').innerHTML = `
                    <div class="empty-state">
                        <h3>Error Loading Marketplace</h3>
                        <p style="color: var(--gray-dark);">Please refresh the page</p>
                    </div>
                `;
            }
        }

        async function approveMarketplace(itemId) {
            if (!confirm('Approve this marketplace item?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_marketplace', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for approve_marketplace:', text);
                    showAlert('Server error while approving item', 'error');
                    return;
                }

                if (data.success) {
                    loadMarketplace();
                    showAlert('Item approved successfully', 'success');
                } else {
                    showAlert(data.message || 'Failed to approve item', 'error');
                }
            } catch (error) {
                console.error('Approve marketplace request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function rejectMarketplace(itemId) {
            if (!confirm('Reject this marketplace item? This will delete the item.')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_marketplace', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Invalid JSON response for reject_marketplace:', text);
                    showAlert('Server error while rejecting item', 'error');
                    return;
                }

                if (data.success) {
                    loadMarketplace();
                    showAlert('Item rejected', 'success');
                } else {
                    showAlert(data.message || 'Failed to reject item', 'error');
                }
            } catch (error) {
                console.error('Reject marketplace request failed:', error);
                showAlert('Connection error', 'error');
            }
        }

        async function loadGroups() {
            try {
                const response = await fetch('../api/approvals.php?action=get_pending_groups');
                const data = await response.json();

                const container = document.getElementById('groupsList');
                document.getElementById('groupsCount').textContent = data.groups?.length || 0;

                if (data.success && data.groups && data.groups.length > 0) {
                    container.innerHTML = data.groups.map(group => `
                        <div class="approval-card animate-slide-up">
                            <div class="approval-header">
                                <div class="approval-user">
                                    <div class="avatar">
                                        <span>${group.name ? group.name.charAt(0).toUpperCase() : 'G'}</span>
                                    </div>
                                    <div>
                                        <h4>${escapeHtml(group.name)}</h4>
                                        <p style="color: var(--gray-dark); font-size: 0.875rem;">
                                            Created by ${escapeHtml(group.creator_name)}
                                        </p>
                                        <p style="color: var(--gray-dark); font-size: 0.8125rem;">
                                            Created ${getTimeAgo(group.created_at)}
                                            ${group.category ? ' • ' + escapeHtml(group.category) : ''}
                                        </p>
                                    </div>
                                </div>
                                <div class="approval-actions">
                                    <button class="btn btn-success" onclick="approveGroup(${group.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectGroup(${group.id})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                            <div class="approval-content">
                                <p style="color: var(--gray-dark); margin-bottom: 0.75rem;">${escapeHtml(group.description || 'No description provided')}</p>
                            </div>
                            ${group.image_url ? `<img src="../${group.image_url}" class="approval-image" alt="Group image">` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <h3>All Caught Up!</h3>
                            <p style="color: var(--gray-dark);">No pending groups</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading groups:', error);
                document.getElementById('groupsList').innerHTML = `
                    <div class="empty-state">
                        <h3 style="color: var(--error);">Failed to load groups</h3>
                        <p style="color: var(--gray-dark);">An error occurred: ${error.message}</p>
                        <button class="btn btn-secondary mt-3" onclick="loadGroups()">Retry</button>
                    </div>
                `;
            }
        }

        async function approveGroup(groupId) {
            if (!confirm('Approve this group?')) return;

            try {
                const response = await fetch('../api/approvals.php?action=approve_group', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Group approved', 'success');
                    loadGroups();
                } else {
                    showAlert(data.message || 'Failed to approve group', 'error');
                }
            } catch (error) {
                showAlert('Connection error', 'error');
            }
        }

        async function rejectGroup(groupId) {
            if (!confirm('Reject this group? This will delete the group permanently.')) return;
            if (!confirm('Are you sure? This action cannot be undone!')) return;

            try {
                const response = await fetch('../api/approvals.php?action=reject_group', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Group rejected and deleted', 'success');
                    loadGroups();
                } else {
                    showAlert(data.message || 'Failed to reject group', 'error');
                }
            } catch (error) {
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