<?php
// Suppress error display, log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'get_notifications':
        getNotifications($db);
        break;
    case 'get_unread_count':
        getUnreadCount($db);
        break;
    case 'mark_read':
        markAsRead($db);
        break;
    case 'mark_all_read':
        markAllAsRead($db);
        break;
    case 'delete_notification':
        deleteNotification($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getNotifications($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;

        $sql = "SELECT n.*, u.full_name, u.profile_image 
                FROM notifications n
                LEFT JOIN users u ON n.reference_id = u.id AND n.type IN ('like', 'comment', 'friend_request')
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?";

        $notifications = $db->query($sql, [$userId, (int)$limit, (int)$offset]);

        // Format notifications with relative time
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = getTimeAgo($notification['created_at']);
            $notification['is_read'] = (bool)$notification['is_read'];
            
            // Get avatar initial if no profile image or if it's a system notification
            if (empty($notification['full_name'])) {
                $notification['full_name'] = 'System';
                $notification['avatar_initial'] = 'S';
            } else {
                $notification['avatar_initial'] = strtoupper(substr($notification['full_name'], 0, 1));
            }
        }

        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications']);
    }
}

function getUnreadCount($db)
{
    try {
        $userId = $_SESSION['user_id'];

        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = $db->query($sql, [$userId]);

        echo json_encode([
            'success' => true,
            'count' => (int)$result[0]['count']
        ]);
    } catch (Exception $e) {
        error_log("Error getting unread count: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch count']);
    }
}

function markAsRead($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $notificationId = $_POST['notification_id'] ?? null;

        if (!$notificationId) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            return;
        }

        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $db->query($sql, [$notificationId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
}

function markAllAsRead($db)
{
    try {
        $userId = $_SESSION['user_id'];

        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $db->query($sql, [$userId]);

        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
}

function deleteNotification($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $notificationId = $_POST['notification_id'] ?? null;

        if (!$notificationId) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            return;
        }

        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $db->query($sql, [$notificationId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Notification deleted']);
    } catch (Exception $e) {
        error_log("Error deleting notification: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
    }
}

function getTimeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
