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
    case 'get_friends_online':
        getFriendsOnline($db);
        break;
    case 'get_teachers':
        getTeachers($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getProfile($db)
{
    $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

    $sql = "SELECT id, full_name, email, role, bio, profile_image, cover_image, student_id, created_at 
            FROM users WHERE id = ? AND is_approved = 1";

    $user = $db->query($sql, [$userId]);

    if ($user && !empty($user)) {
        // Get stats
        $postsSql = "SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND is_approved = 1";
        $postsResult = $db->query($postsSql, [$userId]);
        $postsCount = $postsResult ? $postsResult[0]['count'] : 0;

        $friendsSql = "SELECT COUNT(*) as count FROM friendships 
                      WHERE (user1_id = ? OR user2_id = ?)";
        $friendsResult = $db->query($friendsSql, [$userId, $userId]);
        $friendsCount = $friendsResult ? $friendsResult[0]['count'] : 0;

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
            $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        } else {
            $sql = "UPDATE users SET cover_image = ? WHERE id = ?";
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

    $sql = "SELECT id, full_name, email, role, profile_image 
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

    // Check if already friends
    $checkFriendshipSql = "SELECT id FROM friendships 
                          WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
    $existingFriendship = $db->query($checkFriendshipSql, [$userId, $friendId, $friendId, $userId]);

    if ($existingFriendship && !empty($existingFriendship)) {
        echo json_encode(['success' => false, 'message' => 'Already friends']);
        return;
    }

    // Check if request already exists
    $checkRequestSql = "SELECT id FROM friend_requests 
                       WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
    $existingRequest = $db->query($checkRequestSql, [$userId, $friendId, $friendId, $userId]);

    if ($existingRequest && !empty($existingRequest)) {
        echo json_encode(['success' => false, 'message' => 'Request already exists']);
        return;
    }

    $sql = "INSERT INTO friend_requests (sender_id, receiver_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
    $result = $db->query($sql, [$userId, $friendId]);

    if ($result) {
        // Create notification
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                     VALUES (?, 'friend_request', 'New Friend Request', ?, ?, 'user', NOW())";
        $db->query($notifSql, [$friendId, $_SESSION['user_name'] . ' sent you a friend request', $userId]);

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

    if (!$friendId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user']);
        return;
    }

    $conn = $db->getConnection();
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Update the pending request to accepted
        $updateSql = "UPDATE friend_requests SET status = 'accepted', updated_at = NOW() 
                     WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
        $updateResult = $db->query($updateSql, [$friendId, $userId]);

        if ($updateResult) {
            // Create friendship record (ensure user1_id < user2_id for consistency)
            $user1Id = min($userId, $friendId);
            $user2Id = max($userId, $friendId);
            
            // Check if friendship already exists
            $checkSql = "SELECT id FROM friendships WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
            $existing = $db->query($checkSql, [$user1Id, $user2Id, $user2Id, $user1Id]);
            
            if (!$existing || empty($existing)) {
                $friendshipSql = "INSERT INTO friendships (user1_id, user2_id, created_at) VALUES (?, ?, NOW())";
                $db->query($friendshipSql, [$user1Id, $user2Id]);
            }

            // Create notification
            $notifSql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                        VALUES (?, 'friend_request', 'Friend Request Accepted', ?, ?, 'user', NOW())";
            $db->query($notifSql, [$friendId, ($_SESSION['user_name'] ?? 'Someone') . ' accepted your friend request', $userId]);

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to accept request']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function rejectFriendRequest($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id'] ?? 0);

    $sql = "UPDATE friend_requests SET status = 'rejected', updated_at = NOW() 
           WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
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
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
    $result = $db->query($sql, [$userId, $friendId, $friendId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Unfriended successfully' : 'Failed to unfriend'
    ]);
}

function getFriends($db)
{
    $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

    $sql = "SELECT u.id, u.full_name, u.email, u.role, u.profile_image, u.is_active
            FROM users u
            INNER JOIN friendships f ON (
                (f.user1_id = ? AND f.user2_id = u.id) OR 
                (f.user2_id = ? AND f.user1_id = u.id)
            )
            WHERE u.is_approved = 1
            ORDER BY u.full_name ASC";

    $friends = $db->query($sql, [$userId, $userId]);

    if ($friends) {
        foreach ($friends as &$friend) {
            $friend['is_online'] = (bool)($friend['is_active'] ?? 0);
        }
    }

    echo json_encode([
        'success' => true,
        'friends' => $friends ?: []
    ]);
}

function getFriendsOnline($db)
{
    $userId = $_SESSION['user_id'];

    // Get friends (limit to 10 for sidebar)
    $sql = "SELECT u.id, u.full_name, u.role, u.profile_image
            FROM users u
            INNER JOIN friendships f ON (
                (f.user1_id = ? AND f.user2_id = u.id) OR 
                (f.user2_id = ? AND f.user1_id = u.id)
            )
            WHERE u.is_approved = 1 AND u.is_active = 1
            ORDER BY u.full_name ASC
            LIMIT 10";

    $friends = $db->query($sql, [$userId, $userId]);

    echo json_encode([
        'success' => true,
        'friends' => $friends ?: []
    ]);
}

function getTeachers($db)
{
    // Get faculty/teachers (limit to 10 for sidebar)
    $sql = "SELECT id, full_name, role, profile_image, department
            FROM users
            WHERE is_approved = 1 AND is_active = 1 AND role = 'Faculty'
            ORDER BY full_name ASC
            LIMIT 10";

    $teachers = $db->query($sql);

    echo json_encode([
        'success' => true,
        'teachers' => $teachers ?: []
    ]);
}
