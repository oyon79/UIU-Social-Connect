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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'get_conversations':
        getConversations($db);
        break;
    case 'get_messages':
        getMessages($db);
        break;
    case 'send_message':
        sendMessage($db);
        break;
    case 'mark_read':
        markAsRead($db);
        break;
    case 'search_users':
        searchUsers($db);
        break;
    case 'get_all_users':
        getAllUsers($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getConversations($db)
{
    try {
        $userId = $_SESSION['user_id'];

        // Get unique conversation partners
        $partnersSql = "SELECT DISTINCT
                            CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id
                        FROM messages
                        WHERE sender_id = ? OR receiver_id = ?";
        
        $partners = $db->query($partnersSql, [$userId, $userId, $userId]);
        
        if (!$partners || empty($partners)) {
            echo json_encode([
                'success' => true,
                'conversations' => []
            ]);
            return;
        }
        
        $conversations = [];
        
        foreach ($partners as $partner) {
            $otherUserId = $partner['other_user_id'];
            
            // Get user info
            $userSql = "SELECT id, full_name, profile_image, is_active 
                       FROM users 
                       WHERE id = ? AND is_approved = 1";
            $user = $db->query($userSql, [$otherUserId]);
            
            if (!$user || empty($user)) continue;
            
            $user = $user[0];
            
            // Get last message
            $lastMsgSql = "SELECT message, created_at 
                          FROM messages 
                          WHERE (sender_id = ? AND receiver_id = ?) 
                             OR (sender_id = ? AND receiver_id = ?)
                          ORDER BY created_at DESC 
                          LIMIT 1";
            $lastMsg = $db->query($lastMsgSql, [$userId, $otherUserId, $otherUserId, $userId]);
            
            // Get unread count
            $unreadSql = "SELECT COUNT(*) as count 
                         FROM messages 
                         WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
            $unread = $db->query($unreadSql, [$otherUserId, $userId]);
            
            $lastMsgTime = null;
            $sortTime = 0;
            if ($lastMsg && !empty($lastMsg)) {
                $lastMsgTime = getTimeAgo($lastMsg[0]['created_at']);
                $sortTime = strtotime($lastMsg[0]['created_at']);
            }
            
            $conversations[] = [
                'other_user_id' => $otherUserId,
                'other_user_name' => $user['full_name'],
                'profile_image' => $user['profile_image'] ?? null,
                'is_online' => (bool)($user['is_active'] ?? 0),
                'last_message' => $lastMsg && !empty($lastMsg) ? $lastMsg[0]['message'] : null,
                'last_message_time' => $lastMsgTime,
                'unread_count' => $unread && !empty($unread) ? (int)$unread[0]['count'] : 0,
                '_sort_time' => $sortTime // Temporary field for sorting
            ];
        }
        
        // Sort by last message time (most recent first)
        usort($conversations, function($a, $b) {
            return ($b['_sort_time'] ?? 0) - ($a['_sort_time'] ?? 0);
        });
        
        // Remove temporary sort field
        foreach ($conversations as &$conv) {
            unset($conv['_sort_time']);
        }
        unset($conv);
        
        echo json_encode([
            'success' => true,
            'conversations' => $conversations
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getConversations: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load conversations: ' . $e->getMessage()
        ]);
    }
}

function getMessages($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $otherUserId = intval($_GET['user_id'] ?? 0);

        if (!$otherUserId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            return;
        }

        // Mark messages as read
        $markReadSql = "UPDATE messages SET is_read = 1 
                        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        $db->query($markReadSql, [$otherUserId, $userId]);

        // Get messages
        $sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message as content, m.image_url, m.video_url, m.is_read, m.created_at,
                       s.full_name as sender_name,
                       CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_sent
                FROM messages m
                INNER JOIN users s ON m.sender_id = s.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC";

        $messages = $db->query($sql, [$userId, $userId, $otherUserId, $otherUserId, $userId]);

        if ($messages === false) {
            throw new Exception("Database query failed: " . $db->getConnection()->error);
        }

        echo json_encode([
            'success' => true,
            'messages' => $messages ?: []
        ]);
    } catch (Exception $e) {
        error_log("Error in getMessages: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load messages: ' . $e->getMessage()
        ]);
    }
}

function sendMessage($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $receiverId = intval($data['recipient_id'] ?? $data['receiver_id'] ?? 0);
        $content = trim($data['content'] ?? $data['message'] ?? '');

        if (!$receiverId || !$content) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        if ($receiverId === $userId) {
            echo json_encode(['success' => false, 'message' => 'Cannot send message to yourself']);
            return;
        }

        // Check if receiver exists and is approved
        $checkUserSql = "SELECT id FROM users WHERE id = ? AND is_approved = 1";
        $receiver = $db->query($checkUserSql, [$receiverId]);
        
        if (!$receiver || empty($receiver)) {
            echo json_encode(['success' => false, 'message' => 'User not found or not approved']);
            return;
        }

        $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                VALUES (?, ?, ?, NOW())";

        $result = $db->query($sql, [$userId, $receiverId, $content]);

        if ($result === false) {
            throw new Exception("Database error: " . $db->getConnection()->error);
        }

        if ($result) {
            // Create notification (ignore if it fails)
            $senderName = $_SESSION['user_name'] ?? 'Someone';
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                         VALUES (?, 'message', 'New Message', ?, ?, 'user', NOW())";
            @$db->query($notifSql, [$receiverId, $senderName . ' sent you a message', $userId]);

            echo json_encode(['success' => true, 'message' => 'Message sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
    } catch (Exception $e) {
        error_log("Error in sendMessage: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message: ' . $e->getMessage()
        ]);
    }
}

function markAsRead($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $senderId = intval($data['sender_id'] ?? 0);

    if (!$senderId) {
        echo json_encode(['success' => false, 'message' => 'Invalid sender ID']);
        return;
    }

    $sql = "UPDATE messages SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";

    $result = $db->query($sql, [$senderId, $userId]);

    echo json_encode(['success' => $result ? true : false]);
}

function getAllUsers($db)
{
    try {
        $userId = $_SESSION['user_id'];

        $sql = "SELECT id, full_name, email, role, profile_image, is_active
                FROM users 
                WHERE id != ? 
                AND is_approved = 1 
                ORDER BY full_name ASC
                LIMIT 100";

        $users = $db->query($sql, [$userId]);

        if ($users) {
            foreach ($users as &$user) {
                $user['is_online'] = (bool)($user['is_active'] ?? 0);
            }
        }

        echo json_encode([
            'success' => true,
            'users' => $users ?: []
        ]);
    } catch (Exception $e) {
        error_log("Error in getAllUsers: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load users: ' . $e->getMessage()
        ]);
    }
}

function searchUsers($db)
{
    $userId = $_SESSION['user_id'];
    $query = trim($_GET['query'] ?? '');

    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        return;
    }

    $sql = "SELECT id, full_name, email, role, profile_image, is_active as is_online
            FROM users 
            WHERE id != ? 
            AND is_approved = 1 
            AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)
            ORDER BY full_name ASC
            LIMIT 20";

    $searchTerm = '%' . $query . '%';
    $users = $db->query($sql, [$userId, $searchTerm, $searchTerm, $searchTerm]);

    if ($users) {
        foreach ($users as &$user) {
            $user['is_online'] = (bool)($user['is_online'] ?? 0);
        }
    }

    echo json_encode([
        'success' => true,
        'users' => $users ?: []
    ]);
}

function getTimeAgo($timestamp)
{
    $now = new DateTime();
    $ago = new DateTime($timestamp);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y';
    if ($diff->m > 0) return $diff->m . 'mo';
    if ($diff->d > 0) return $diff->d . 'd';
    if ($diff->h > 0) return $diff->h . 'h';
    if ($diff->i > 0) return $diff->i . 'm';
    return 'now';
}
