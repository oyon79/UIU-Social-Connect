<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'get_pending_users':
        getPendingUsers($db);
        break;
    case 'get_pending_posts':
        getPendingPosts($db);
        break;
    case 'get_pending_events':
        getPendingEvents($db);
        break;
    case 'get_pending_jobs':
        getPendingJobs($db);
        break;
    case 'approve_user':
        approveUser($db);
        break;
    case 'reject_user':
        rejectUser($db);
        break;
    case 'approve_post':
        approvePost($db);
        break;
    case 'reject_post':
        rejectPost($db);
        break;
    case 'approve_event':
        approveEvent($db);
        break;
    case 'reject_event':
        rejectEvent($db);
        break;
    case 'approve_job':
        approveJob($db);
        break;
    case 'reject_job':
        rejectJob($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getPendingUsers($db)
{
    // Only include active users who are pending approval
    $sql = "SELECT id, full_name, email, role, student_id, created_at 
            FROM users 
            WHERE is_approved = 0 AND is_active = 1
            ORDER BY created_at DESC";

    $users = $db->query($sql);

    echo json_encode([
        'success' => true,
        'users' => $users ?: []
    ]);
}

function getPendingPosts($db)
{
    $sql = "SELECT p.*, u.full_name as author_name, u.role as author_role
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            WHERE p.is_approved = 0
            ORDER BY p.created_at DESC";

    $posts = $db->query($sql);

    echo json_encode([
        'success' => true,
        'posts' => $posts ?: []
    ]);
}

function getPendingEvents($db)
{
    $sql = "SELECT e.*, u.full_name as organizer_name
            FROM events e
            INNER JOIN users u ON e.user_id = u.id
            WHERE e.is_approved = 0
            ORDER BY e.created_at DESC";

    $events = $db->query($sql);

    echo json_encode([
        'success' => true,
        'events' => $events ?: []
    ]);
}

function getPendingJobs($db)
{
    $sql = "SELECT j.*, u.full_name as poster_name
            FROM jobs j
            INNER JOIN users u ON j.user_id = u.id
            WHERE j.is_approved = 0
            ORDER BY j.created_at DESC";

    $jobs = $db->query($sql);

    echo json_encode([
        'success' => true,
        'jobs' => $jobs ?: []
    ]);
}

function approveUser($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = intval($data['user_id'] ?? 0);

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    // Approve user and ensure active
    $sql = "UPDATE users SET is_approved = 1, is_active = 1 WHERE id = ?";
    $result = $db->query($sql, [$userId]);

    if ($result) {
        // Create notification for user (title/message columns)
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                     VALUES (?, 'approval', 'Account Approved', 'Your account has been approved! You can now login.', NOW())";
        $db->query($notifSql, [$userId]);

        // Log activity - use admin_id and details columns; record affected user and admin
        $logSql = "INSERT INTO activity_logs (user_id, admin_id, action, details, created_at) 
                   VALUES (?, ?, 'user_approved', ?, NOW())";
        $description = "User ID: {$userId} approved";
        $logResult = $db->query($logSql, [$userId, $_SESSION['user_id'], $description]);
        if ($logResult === false) {
            error_log("Failed to insert activity log for approved user {$userId}");
        }

        echo json_encode(['success' => true, 'message' => 'User approved successfully']);
    } else {
        $dbError = $db->getConnection()->error ?? '';
        echo json_encode(['success' => false, 'message' => 'Failed to approve user', 'error' => $dbError]);
    }
}

function rejectUser($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = intval($data['user_id'] ?? 0);
    $reason = trim($data['reason'] ?? '');

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    // Soft-reject user: mark as inactive and not approved, preserve data for audit
    $sql = "UPDATE users SET is_active = 0, is_approved = 0 WHERE id = ?";
    $result = $db->query($sql, [$userId]);

    if ($result) {
        // Notify user with reason (if provided)
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                     VALUES (?, 'rejection', 'Account Rejected', ?, NOW())";
        $content = "Your account registration was rejected." . ($reason ? " Reason: {$reason}" : '');
        $db->query($notifSql, [$userId, $content]);

        // Log activity - use admin_id and details columns; record affected user and admin
        $logSql = "INSERT INTO activity_logs (user_id, admin_id, action, details, created_at) 
                   VALUES (?, ?, 'user_rejected', ?, NOW())";
        $description = "User ID: {$userId} rejected. Reason: {$reason}";
        $logResult = $db->query($logSql, [$userId, $_SESSION['user_id'], $description]);
        if ($logResult === false) {
            error_log("Failed to insert activity log for rejected user {$userId}");
        }

        echo json_encode(['success' => true, 'message' => 'User rejected']);
    } else {
        $dbError = $db->getConnection()->error ?? '';
        echo json_encode(['success' => false, 'message' => 'Failed to reject user', 'error' => $dbError]);
    }
}

function approvePost($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $postId = intval($data['post_id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        return;
    }

    $sql = "UPDATE posts SET is_approved = 1 WHERE id = ?";
    $result = $db->query($sql, [$postId]);

    if ($result) {
        // Get post owner
        $postSql = "SELECT user_id FROM posts WHERE id = ?";
        $post = $db->query($postSql, [$postId]);

        if ($post) {
            // Notify user about post approval (include reference id/type)
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Post Approved', 'Your post has been approved!', ?, 'post', NOW())";
            $db->query($notifSql, [$post[0]['user_id'], $postId]);
        }

        echo json_encode(['success' => true, 'message' => 'Post approved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve post']);
    }
}

function rejectPost($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $postId = intval($data['post_id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        return;
    }

    // Get post owner before deleting
    $postSql = "SELECT user_id FROM posts WHERE id = ?";
    $post = $db->query($postSql, [$postId]);

    // Delete post
    $sql = "DELETE FROM posts WHERE id = ?";
    $result = $db->query($sql, [$postId]);

    if ($result && $post) {
        // Notify user about post rejection
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'rejection', 'Post Rejected', 'Your post was not approved.', ?, 'post', NOW())";
        $db->query($notifSql, [$post[0]['user_id'], $postId]);

        echo json_encode(['success' => true, 'message' => 'Post rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject post']);
    }
}

function approveEvent($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $eventId = intval($data['event_id'] ?? 0);

    $sql = "UPDATE events SET is_approved = 1 WHERE id = ?";
    $result = $db->query($sql, [$eventId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Event approved' : 'Failed to approve event'
    ]);
}

function rejectEvent($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $eventId = intval($data['event_id'] ?? 0);

    $sql = "DELETE FROM events WHERE id = ?";
    $result = $db->query($sql, [$eventId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Event rejected' : 'Failed to reject event'
    ]);
}

function approveJob($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);

    $sql = "UPDATE jobs SET is_approved = 1 WHERE id = ?";
    $result = $db->query($sql, [$jobId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Job approved' : 'Failed to approve job'
    ]);
}

function rejectJob($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);

    $sql = "DELETE FROM jobs WHERE id = ?";
    $result = $db->query($sql, [$jobId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Job rejected' : 'Failed to reject job'
    ]);
}
