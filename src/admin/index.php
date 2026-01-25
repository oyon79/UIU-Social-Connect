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

// Use singleton Database instance
$db = Database::getInstance();

// Get statistics
$totalUsersSql = "SELECT COUNT(*) as count FROM users";
$totalUsers = $db->query($totalUsersSql)[0]['count'];

$pendingUsersSql = "SELECT COUNT(*) as count FROM users WHERE is_approved = 0 AND is_active = 1";
$pendingUsers = $db->query($pendingUsersSql)[0]['count'];

$totalPostsSql = "SELECT COUNT(*) as count FROM posts";
$totalPosts = $db->query($totalPostsSql)[0]['count'];

$pendingPostsSql = "SELECT COUNT(*) as count FROM posts WHERE is_approved = 0";
$pendingPosts = $db->query($pendingPostsSql)[0]['count'];

$totalEventsSql = "SELECT COUNT(*) as count FROM events";
$totalEvents = $db->query($totalEventsSql)[0]['count'];

$pendingEventsSql = "SELECT COUNT(*) as count FROM events WHERE is_approved = 0";
$pendingEvents = $db->query($pendingEventsSql)[0]['count'];

$totalJobsSql = "SELECT COUNT(*) as count FROM jobs";
$totalJobs = $db->query($totalJobsSql)[0]['count'];

$pendingJobsSql = "SELECT COUNT(*) as count FROM jobs WHERE is_approved = 0";
$pendingJobs = $db->query($pendingJobsSql)[0]['count'];

$totalDocumentsSql = "SELECT COUNT(*) as count FROM documents";
$totalDocuments = $db->query($totalDocumentsSql)[0]['count'];

$pendingDocumentsSql = "SELECT COUNT(*) as count FROM documents WHERE is_approved = 0 AND rejection_reason IS NULL";
$pendingDocuments = $db->query($pendingDocumentsSql)[0]['count'];

$pageTitle = 'Admin Dashboard - UIU Social Connect';
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

        .admin-header h1 {
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            background-clip: text;
            /* standard property */
            -webkit-background-clip: text;
            /* WebKit for better support */
            color: transparent;
            /* fallback for browsers that don't support text-fill */
            -webkit-text-fill-color: transparent;
            /* WebKit-specific transparent text */
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
            box-shadow: var(--shadow-md);
        }

        .admin-nav-link.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
            box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 102, 0.1));
        }

        .stat-card-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-card-label {
            color: var(--gray-dark);
            font-size: 0.9375rem;
            margin-bottom: 1rem;
        }

        .stat-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .stat-card-badge.pending {
            background: #FFF7ED;
            color: var(--warning);
        }

        .stat-card-badge.success {
            background: #ECFDF5;
            color: var(--success);
        }

        .recent-activity {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }

        .recent-activity h3 {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #FFE5D1;
            transform: translateX(4px);
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.user {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
        }

        .activity-icon.post {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .activity-icon.event {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.9375rem;
        }

        .activity-content p {
            margin: 0;
            font-size: 0.8125rem;
            color: var(--gray-dark);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }

        .badge-warning {
            background: #F59E0B;
            color: white;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header animate-fade-in">
            <h1>👨‍💼 Admin Dashboard</h1>
            <p style="color: var(--gray-dark);">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening today.</p>
        </div>

        <!-- Navigation -->
        <div class="admin-nav animate-slide-up" style="animation-delay: 0.1s;">
            <a href="index.php" class="admin-nav-link active">
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
                <?php if ($pendingUsers + $pendingPosts + $pendingEvents + $pendingJobs + $pendingDocuments > 0): ?>
                    <span class="badge badge-warning"><?php echo $pendingUsers + $pendingPosts + $pendingEvents + $pendingJobs + $pendingDocuments; ?></span>
                <?php endif; ?>
            </a>
            <a href="users.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
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
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Documents
                <?php if ($pendingDocuments > 0): ?>
                    <span class="badge badge-warning"><?php echo $pendingDocuments; ?></span>
                <?php endif; ?>
            </a>
            <a href="../dashboard/newsfeed.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                Back to Site
            </a>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card animate-scale-in" style="animation-delay: 0.2s;">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-card-label">Total Users</div>
                    </div>
                    <div class="stat-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-orange)" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
                <?php if ($pendingUsers > 0): ?>
                    <span class="stat-card-badge pending">
                        <?php echo $pendingUsers; ?> pending approval
                    </span>
                <?php else: ?>
                    <span class="stat-card-badge success">
                        ✓ All approved
                    </span>
                <?php endif; ?>
            </div>

            <div class="stat-card animate-scale-in" style="animation-delay: 0.3s;">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo $totalPosts; ?></div>
                        <div class="stat-card-label">Total Posts</div>
                    </div>
                    <div class="stat-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-orange)" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                </div>
                <?php if ($pendingPosts > 0): ?>
                    <span class="stat-card-badge pending">
                        <?php echo $pendingPosts; ?> pending approval
                    </span>
                <?php else: ?>
                    <span class="stat-card-badge success">
                        ✓ All approved
                    </span>
                <?php endif; ?>
            </div>

            <div class="stat-card animate-scale-in" style="animation-delay: 0.4s;">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo $totalEvents; ?></div>
                        <div class="stat-card-label">Total Events</div>
                    </div>
                    <div class="stat-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-orange)" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                </div>
                <?php if ($pendingEvents > 0): ?>
                    <span class="stat-card-badge pending">
                        <?php echo $pendingEvents; ?> pending approval
                    </span>
                <?php else: ?>
                    <span class="stat-card-badge success">
                        ✓ All approved
                    </span>
                <?php endif; ?>
            </div>

            <div class="stat-card animate-scale-in" style="animation-delay: 0.5s;">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo $totalJobs; ?></div>
                        <div class="stat-card-label">Total Jobs</div>
                    </div>
                    <div class="stat-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-orange)" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                </div>
                <?php if ($pendingJobs > 0): ?>
                    <span class="stat-card-badge pending">
                        <?php echo $pendingJobs; ?> pending approval
                    </span>
                <?php else: ?>
                    <span class="stat-card-badge success">
                        ✓ All approved
                    </span>
                <?php endif; ?>
            </div>

            <div class="stat-card animate-scale-in" style="animation-delay: 0.6s;">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo $totalDocuments; ?></div>
                        <div class="stat-card-label">Total Documents</div>
                    </div>
                    <div class="stat-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-orange)" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                </div>
                <?php if ($pendingDocuments > 0): ?>
                    <span class="stat-card-badge pending">
                        <?php echo $pendingDocuments; ?> pending approval
                    </span>
                <?php else: ?>
                    <span class="stat-card-badge success">
                        ✓ All approved
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity animate-slide-up" style="animation-delay: 0.6s;">
            <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Recent Activity
            </h3>

            <div class="activity-list" id="activityList">
                <div class="text-center" style="padding: 2rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading activity...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load recent activity
        async function loadActivity() {
            try {
                const response = await fetch('../api/admin.php?action=get_recent_activity');
                const data = await response.json();

                const container = document.getElementById('activityList');

                if (data.success && data.activities && data.activities.length > 0) {
                    container.innerHTML = data.activities.map(activity => `
                        <div class="activity-item">
                            <div class="activity-icon ${activity.type}">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    ${getActivityIcon(activity.type)}
                                </svg>
                            </div>
                            <div class="activity-content">
                                <h4>${activity.title}</h4>
                                <p>${activity.description} • ${activity.time_ago}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="text-center" style="padding: 2rem;">
                            <p style="color: var(--gray-dark);">No recent activity</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading activity:', error);
            }
        }

        function getActivityIcon(type) {
            const icons = {
                user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
                post: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline>',
                event: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'
            };
            return icons[type] || icons.user;
        }

        // Load on page load
        loadActivity();
    </script>
</body>

</html>