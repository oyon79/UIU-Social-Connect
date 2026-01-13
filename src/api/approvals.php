<?php
// Suppress error display, log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
    case 'get_pending_notices':
        getPendingNotices($db);
        break;
    case 'get_pending_marketplace':
        getPendingMarketplace($db);
        break;
    case 'get_pending_groups':
        getPendingGroups($db);
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
    case 'approve_notice':
        approveNotice($db);
        break;
    case 'reject_notice':
        rejectNotice($db);
        break;
    case 'approve_marketplace':
        approveMarketplace($db);
        break;
    case 'reject_marketplace':
        rejectMarketplace($db);
        break;
    case 'approve_group':
        approveGroup($db);
        break;
    case 'reject_group':
        rejectGroup($db);
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

function getPendingNotices($db)
{
    $sql = "SELECT n.*, u.full_name as poster_name
            FROM notices n
            INNER JOIN users u ON n.user_id = u.id
            WHERE n.is_approved = 0
            ORDER BY n.created_at DESC";

    $notices = $db->query($sql);

    echo json_encode([
        'success' => true,
        'notices' => $notices ?: []
    ]);
}

function getPendingMarketplace($db)
{
    $sql = "SELECT m.*, u.full_name as seller_name
            FROM marketplace_items m
            INNER JOIN users u ON m.user_id = u.id
            WHERE m.is_approved = 0
            ORDER BY m.created_at DESC";

    $items = $db->query($sql);

    echo json_encode([
        'success' => true,
        'items' => $items ?: []
    ]);
}

function getPendingGroups($db)
{
    try {
        $sql = "SELECT g.*, u.full_name as creator_name
                FROM groups g
                INNER JOIN users u ON g.creator_id = u.id
                WHERE g.is_approved = 0
                ORDER BY g.created_at DESC";

        $groups = $db->query($sql);

        if ($groups === false) {
            throw new Exception("Database query failed: " . $db->getConnection()->error);
        }

        echo json_encode([
            'success' => true,
            'groups' => $groups ?: []
        ]);
    } catch (Exception $e) {
        error_log("Error in getPendingGroups: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load groups: ' . $e->getMessage(),
            'groups' => []
        ]);
    }
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
    $adminId = $_SESSION['user_id'];

    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        return;
    }

    $sql = "UPDATE events SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $result = $db->query($sql, [$adminId, $eventId]);

    if ($result) {
        // Get event owner
        $eventSql = "SELECT user_id FROM events WHERE id = ?";
        $event = $db->query($eventSql, [$eventId]);

        if ($event) {
            // Notify user about event approval
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Event Approved', 'Your event has been approved!', ?, 'event', NOW())";
            $db->query($notifSql, [$event[0]['user_id'], $eventId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Event approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve event'
        ]);
    }
}

function rejectEvent($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $eventId = intval($data['event_id'] ?? 0);

    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        return;
    }

    // Get event owner before deleting
    $eventSql = "SELECT user_id FROM events WHERE id = ?";
    $event = $db->query($eventSql, [$eventId]);

    // Delete event
    $sql = "DELETE FROM events WHERE id = ?";
    $result = $db->query($sql, [$eventId]);

    if ($result && $event) {
        // Notify user about event rejection
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'rejection', 'Event Rejected', 'Your event was not approved.', ?, 'event', NOW())";
        $db->query($notifSql, [$event[0]['user_id'], $eventId]);

        echo json_encode([
            'success' => true,
            'message' => 'Event rejected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject event'
        ]);
    }
}

function approveJob($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);
    $adminId = $_SESSION['user_id'];

    if (!$jobId) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
        return;
    }

    $sql = "UPDATE jobs SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $result = $db->query($sql, [$adminId, $jobId]);

    if ($result) {
        // Get job owner
        $jobSql = "SELECT user_id FROM jobs WHERE id = ?";
        $job = $db->query($jobSql, [$jobId]);

        if ($job) {
            // Notify user about job approval
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Job Approved', 'Your job posting has been approved!', ?, 'job', NOW())";
            $db->query($notifSql, [$job[0]['user_id'], $jobId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Job approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve job'
        ]);
    }
}

function rejectJob($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);

    if (!$jobId) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
        return;
    }

    // Get job owner before deleting
    $jobSql = "SELECT user_id FROM jobs WHERE id = ?";
    $job = $db->query($jobSql, [$jobId]);

    // Delete job
    $sql = "DELETE FROM jobs WHERE id = ?";
    $result = $db->query($sql, [$jobId]);

    if ($result && $job) {
        // Notify user about job rejection
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'rejection', 'Job Rejected', 'Your job posting was not approved.', ?, 'job', NOW())";
        $db->query($notifSql, [$job[0]['user_id'], $jobId]);

        echo json_encode([
            'success' => true,
            'message' => 'Job rejected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject job'
        ]);
    }
}

function approveNotice($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $noticeId = intval($data['notice_id'] ?? 0);
    $adminId = $_SESSION['user_id'];

    if (!$noticeId) {
        echo json_encode(['success' => false, 'message' => 'Invalid notice ID']);
        return;
    }

    $sql = "UPDATE notices SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $result = $db->query($sql, [$adminId, $noticeId]);

    if ($result) {
        // Get notice owner
        $noticeSql = "SELECT user_id FROM notices WHERE id = ?";
        $notice = $db->query($noticeSql, [$noticeId]);

        if ($notice) {
            // Notify user about notice approval
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Notice Approved', 'Your notice has been approved!', ?, 'notice', NOW())";
            $db->query($notifSql, [$notice[0]['user_id'], $noticeId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Notice approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve notice'
        ]);
    }
}

function rejectNotice($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $noticeId = intval($data['notice_id'] ?? 0);

    if (!$noticeId) {
        echo json_encode(['success' => false, 'message' => 'Invalid notice ID']);
        return;
    }

    // Get notice owner before deleting
    $noticeSql = "SELECT user_id FROM notices WHERE id = ?";
    $notice = $db->query($noticeSql, [$noticeId]);

    // Delete notice
    $sql = "DELETE FROM notices WHERE id = ?";
    $result = $db->query($sql, [$noticeId]);

    if ($result && $notice) {
        // Notify user about notice rejection
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'rejection', 'Notice Rejected', 'Your notice was not approved.', ?, 'notice', NOW())";
        $db->query($notifSql, [$notice[0]['user_id'], $noticeId]);

        echo json_encode([
            'success' => true,
            'message' => 'Notice rejected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject notice'
        ]);
    }
}

function approveMarketplace($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($data['item_id'] ?? 0);
    $adminId = $_SESSION['user_id'];

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    $sql = "UPDATE marketplace_items SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $result = $db->query($sql, [$adminId, $itemId]);

    if ($result) {
        // Get item owner
        $itemSql = "SELECT user_id FROM marketplace_items WHERE id = ?";
        $item = $db->query($itemSql, [$itemId]);

        if ($item) {
            // Notify user about item approval
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Item Approved', 'Your marketplace item has been approved!', ?, 'marketplace', NOW())";
            $db->query($notifSql, [$item[0]['user_id'], $itemId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Item approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve item'
        ]);
    }
}

function rejectMarketplace($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($data['item_id'] ?? 0);

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    // Get item owner and image before deleting
    $itemSql = "SELECT user_id, image_url FROM marketplace_items WHERE id = ?";
    $item = $db->query($itemSql, [$itemId]);

    // Delete image if exists
    if ($item && $item[0]['image_url'] && file_exists('../' . $item[0]['image_url'])) {
        unlink('../' . $item[0]['image_url']);
    }

    // Delete item
    $sql = "DELETE FROM marketplace_items WHERE id = ?";
    $result = $db->query($sql, [$itemId]);

    if ($result && $item) {
        // Notify user about item rejection
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'rejection', 'Item Rejected', 'Your marketplace item was not approved.', ?, 'marketplace', NOW())";
        $db->query($notifSql, [$item[0]['user_id'], $itemId]);

        echo json_encode([
            'success' => true,
            'message' => 'Item rejected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject item'
        ]);
    }
}

function approveGroup($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = intval($data['group_id'] ?? 0);
    $adminId = $_SESSION['user_id'];

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    $sql = "UPDATE groups SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $result = $db->query($sql, [$adminId, $groupId]);

    if ($result) {
        // Get group creator
        $groupSql = "SELECT creator_id FROM groups WHERE id = ?";
        $group = $db->query($groupSql, [$groupId]);

        if ($group && !empty($group)) {
            // Notify user about group approval
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'approval', 'Group Approved', 'Your group has been approved!', ?, 'group', NOW())";
            $db->query($notifSql, [$group[0]['creator_id'], $groupId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Group approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve group'
        ]);
    }
}

function rejectGroup($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = intval($data['group_id'] ?? 0);
    $adminId = $_SESSION['user_id'];

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    // Get group creator and image before deletion
    $groupSql = "SELECT creator_id, image_url FROM groups WHERE id = ?";
    $group = $db->query($groupSql, [$groupId]);

    // Delete image if exists
    if ($group && !empty($group) && $group[0]['image_url'] && file_exists('../' . $group[0]['image_url'])) {
        unlink('../' . $group[0]['image_url']);
    }

    // Delete the group
    $sql = "DELETE FROM groups WHERE id = ?";
    $result = $db->query($sql, [$groupId]);

    if ($result) {
        if ($group && !empty($group)) {
            // Notify user about group rejection
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'rejection', 'Group Rejected', 'Your group was not approved.', ?, 'group', NOW())";
            $db->query($notifSql, [$group[0]['creator_id'], $groupId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Group rejected and deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject group'
        ]);
    }
}
