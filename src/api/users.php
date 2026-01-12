<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'get_profile':
        getProfile($db);
        break;
    case 'update_profile':
        updateProfile($db);
        break;
    case 'upload_photo':
        uploadPhoto($db);
        break;
    case 'search_users':
        searchUsers($db);
        break;
    case 'send_friend_request':
        sendFriendRequest($db);
        break;
    case 'accept_friend_request':
        acceptFriendRequest($db);
        break;
    case 'reject_friend_request':
        rejectFriendRequest($db);
        break;
    case 'unfriend':
        unfriend($db);
        break;
    case 'get_friends':
        getFriends($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getProfile($db)
{
    $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

    $sql = "SELECT id, full_name, email, role, bio, profile_picture, cover_photo, student_id, created_at 
            FROM users WHERE id = ? AND is_approved = 1";

    $user = $db->query($sql, [$userId]);

    if ($user) {
        // Get stats
        $postsSql = "SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND is_approved = 1";
        $postsCount = $db->query($postsSql, [$userId])[0]['count'];

        $friendsSql = "SELECT COUNT(*) as count FROM friendships WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'";
        $friendsCount = $db->query($friendsSql, [$userId, $userId])[0]['count'];

        $user[0]['posts_count'] = $postsCount;
        $user[0]['friends_count'] = $friendsCount;

        echo json_encode([
            'success' => true,
            'user' => $user[0]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function updateProfile($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $fullName = trim($data['full_name'] ?? '');
    $bio = trim($data['bio'] ?? '');
    $studentId = trim($data['student_id'] ?? '');

    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
        return;
    }

    $sql = "UPDATE users SET full_name = ?, bio = ?, student_id = ? WHERE id = ?";
    $result = $db->query($sql, [$fullName, $bio, $studentId, $userId]);

    if ($result) {
        $_SESSION['user_name'] = $fullName;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}

function uploadPhoto($db)
{
    $userId = $_SESSION['user_id'];
    $type = $_POST['type'] ?? 'profile'; // 'profile' or 'cover'

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        return;
    }

    $file = $_FILES['photo'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        return;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        return;
    }

    $uploadDir = '../assets/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . $userId . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $dbPath = 'assets/uploads/profiles/' . $fileName;

        if ($type === 'profile') {
            $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        } else {
            $sql = "UPDATE users SET cover_photo = ? WHERE id = ?";
        }

        $result = $db->query($sql, [$dbPath, $userId]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'url' => $dbPath
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
}

function searchUsers($db)
{
    $query = $_GET['query'] ?? '';
    $currentUserId = $_SESSION['user_id'];

    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        return;
    }

    $sql = "SELECT id, full_name, email, role, profile_picture 
            FROM users 
            WHERE id != ? 
            AND is_approved = 1 
            AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)
            LIMIT 20";

    $searchTerm = '%' . $query . '%';
    $users = $db->query($sql, [$currentUserId, $searchTerm, $searchTerm, $searchTerm]);

    echo json_encode([
        'success' => true,
        'users' => $users ?: []
    ]);
}

function sendFriendRequest($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id'] ?? 0);

    if (!$friendId || $friendId === $userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user']);
        return;
    }

    // Check if already friends or request exists
    $checkSql = "SELECT id FROM friendships 
                 WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
    $existing = $db->query($checkSql, [$userId, $friendId, $friendId, $userId]);

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Request already exists']);
        return;
    }

    $sql = "INSERT INTO friendships (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
    $result = $db->query($sql, [$userId, $friendId]);

    if ($result) {
        // Create notification
        $notifSql = "INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                     VALUES (?, 'friend_request', 'sent you a friend request', ?, NOW())";
        $db->query($notifSql, [$friendId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Friend request sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request']);
    }
}

function acceptFriendRequest($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id'] ?? 0);

    // Update the pending request to accepted
    $sql = "UPDATE friendships SET status = 'accepted' 
            WHERE user_id = ? AND friend_id = ? AND status = 'pending'";
    $result = $db->query($sql, [$friendId, $userId]);

    if ($result) {
        // Create notification
        $notifSql = "INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                     VALUES (?, 'friend_accept', 'accepted your friend request', ?, NOW())";
        $db->query($notifSql, [$friendId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept request']);
    }
}

function rejectFriendRequest($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id'] ?? 0);

    $sql = "DELETE FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'pending'";
    $result = $db->query($sql, [$friendId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Request rejected' : 'Failed to reject request'
    ]);
}

function unfriend($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id'] ?? 0);

    $sql = "DELETE FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
    $result = $db->query($sql, [$userId, $friendId, $friendId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Unfriended successfully' : 'Failed to unfriend'
    ]);
}

function getFriends($db)
{
    $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

    $sql = "SELECT u.id, u.full_name, u.email, u.role, u.profile_picture
            FROM users u
            INNER JOIN friendships f ON (
                (f.user_id = ? AND f.friend_id = u.id) OR 
                (f.friend_id = ? AND f.user_id = u.id)
            )
            WHERE f.status = 'accepted' AND u.is_approved = 1
            ORDER BY u.full_name ASC";

    $friends = $db->query($sql, [$userId, $userId]);

    echo json_encode([
        'success' => true,
        'friends' => $friends ?: []
    ]);
}
