<?php
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
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getConversations($db)
{
    $userId = $_SESSION['user_id'];

    $sql = "SELECT DISTINCT
                CASE 
                    WHEN m.sender_id = ? THEN m.recipient_id
                    ELSE m.sender_id
                END as other_user_id,
                u.full_name as other_user_name,
                u.profile_picture,
                (SELECT content FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.recipient_id = other_user_id) 
                    OR (m2.recipient_id = ? AND m2.sender_id = other_user_id)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages m3
                 WHERE (m3.sender_id = ? AND m3.recipient_id = other_user_id) 
                    OR (m3.recipient_id = ? AND m3.sender_id = other_user_id)
                 ORDER BY m3.created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages m4
                 WHERE m4.sender_id = other_user_id 
                   AND m4.recipient_id = ? 
                   AND m4.is_read = 0) as unread_count
            FROM messages m
            INNER JOIN users u ON (
                CASE 
                    WHEN m.sender_id = ? THEN m.recipient_id
                    ELSE m.sender_id
                END = u.id
            )
            WHERE m.sender_id = ? OR m.recipient_id = ?
            ORDER BY last_message_time DESC";

    $conversations = $db->query($sql, [
        $userId,
        $userId,
        $userId,
        $userId,
        $userId,
        $userId,
        $userId,
        $userId,
        $userId
    ]);

    if ($conversations) {
        foreach ($conversations as &$conv) {
            if ($conv['last_message_time']) {
                $conv['last_message_time'] = getTimeAgo($conv['last_message_time']);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'conversations' => $conversations ?: []
    ]);
}

function getMessages($db)
{
    $userId = $_SESSION['user_id'];
    $otherUserId = intval($_GET['user_id'] ?? 0);

    if (!$otherUserId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    // Mark messages as read
    $markReadSql = "UPDATE messages SET is_read = 1 
                    WHERE sender_id = ? AND recipient_id = ? AND is_read = 0";
    $db->query($markReadSql, [$otherUserId, $userId]);

    // Get messages
    $sql = "SELECT m.*, 
                   s.full_name as sender_name,
                   CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_sent
            FROM messages m
            INNER JOIN users s ON m.sender_id = s.id
            WHERE (m.sender_id = ? AND m.recipient_id = ?)
               OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC";

    $messages = $db->query($sql, [$userId, $userId, $otherUserId, $otherUserId, $userId]);

    echo json_encode([
        'success' => true,
        'messages' => $messages ?: []
    ]);
}

function sendMessage($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $recipientId = intval($data['recipient_id'] ?? 0);
    $content = trim($data['content'] ?? '');

    if (!$recipientId || !$content) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    $sql = "INSERT INTO messages (sender_id, recipient_id, content, created_at) 
            VALUES (?, ?, ?, NOW())";

    $result = $db->query($sql, [$userId, $recipientId, $content]);

    if ($result) {
        // Create notification
        $notifSql = "INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                     VALUES (?, 'message', 'sent you a message', ?, NOW())";
        $db->query($notifSql, [$recipientId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

function markAsRead($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $senderId = intval($data['sender_id'] ?? 0);

    $sql = "UPDATE messages SET is_read = 1 
            WHERE sender_id = ? AND recipient_id = ?";

    $result = $db->query($sql, [$senderId, $userId]);

    echo json_encode(['success' => $result ? true : false]);
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
