<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'get_recent_activity':
        getRecentActivity($db);
        break;
    case 'get_all_users':
        getAllUsers($db);
        break;
    case 'get_all_content':
        getAllContent($db);
        break;
    case 'ban_user':
        banUser($db);
        break;
    case 'unban_user':
        unbanUser($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getRecentActivity($db)
{
    $activities = [];

    // Recent user registrations
    $usersSql = "SELECT full_name, created_at FROM users ORDER BY created_at DESC LIMIT 5";
    $users = $db->query($usersSql);
    if ($users) {
        foreach ($users as $user) {
            $activities[] = [
                'type' => 'user',
                'title' => 'New User Registration',
                'description' => htmlspecialchars($user['full_name']) . ' joined the platform',
                'time_ago' => getTimeAgo($user['created_at'])
            ];
        }
    }

    // Recent posts
    $postsSql = "SELECT p.created_at, u.full_name 
                 FROM posts p 
                 INNER JOIN users u ON p.user_id = u.id 
                 ORDER BY p.created_at DESC LIMIT 5";
    $posts = $db->query($postsSql);
    if ($posts) {
        foreach ($posts as $post) {
            $activities[] = [
                'type' => 'post',
                'title' => 'New Post Created',
                'description' => htmlspecialchars($post['full_name']) . ' created a post',
                'time_ago' => getTimeAgo($post['created_at'])
            ];
        }
    }

    // Sort by most recent
    usort($activities, function ($a, $b) {
        return strcmp($b['time_ago'], $a['time_ago']);
    });

    echo json_encode([
        'success' => true,
        'activities' => array_slice($activities, 0, 10)
    ]);
}

function getAllUsers($db)
{
    // Ensure legacy DBs have the is_banned column (uses IF NOT EXISTS when supported)
    $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0");

    $sql = "SELECT id, full_name, email, role, student_id, is_approved, is_banned, is_active, created_at 
            FROM users 
            ORDER BY created_at DESC";

    $users = $db->query($sql);

    // If query failed, return diagnostic to help debugging
    if ($users === false) {
        $err = $db->getConnection()->error ?? 'Unknown DB error';
        echo json_encode(['success' => false, 'message' => 'Failed to fetch users', 'error' => $err]);
        return;
    }

    // Normalize boolean/int flags so JS receives proper types
    foreach ($users as &$u) {
        $u['is_approved'] = isset($u['is_approved']) ? (int)$u['is_approved'] : 0;
        $u['is_banned'] = isset($u['is_banned']) ? (int)$u['is_banned'] : 0;
        $u['is_active'] = isset($u['is_active']) ? (int)$u['is_active'] : 0;
    }

    echo json_encode([
        'success' => true,
        'users' => $users ?: []
    ]);
}

function getAllContent($db)
{
    $sql = "SELECT p.*, u.full_name as author_name,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            WHERE p.is_approved = 1
            ORDER BY p.created_at DESC
            LIMIT 50";

    $posts = $db->query($sql);

    echo json_encode([
        'success' => true,
        'posts' => $posts ?: []
    ]);
}

function banUser($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = intval($data['user_id'] ?? 0);

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    $sql = "UPDATE users SET is_banned = 1 WHERE id = ?";
    $result = $db->query($sql, [$userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'User banned' : 'Failed to ban user'
    ]);
}

function unbanUser($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = intval($data['user_id'] ?? 0);

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    $sql = "UPDATE users SET is_banned = 0 WHERE id = ?";
    $result = $db->query($sql, [$userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'User unbanned' : 'Failed to unban user'
    ]);
}

function getTimeAgo($timestamp)
{
    $now = new DateTime();
    $ago = new DateTime($timestamp);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
