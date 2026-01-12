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
    case 'create':
        createPost($db);
        break;
    case 'get_all':
        getAllPosts($db);
        break;
    case 'like':
        toggleLike($db);
        break;
    case 'comment':
        addComment($db);
        break;
    case 'get_comments':
        getComments($db);
        break;
    case 'delete':
        deletePost($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createPost($db)
{
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
        return;
    }

    $imageUrl = null;
    $videoUrl = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imageUrl = 'assets/uploads/posts/' . $fileName;
        }
    }

    // Handle video upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['video']['tmp_name'], $filePath)) {
            $videoUrl = 'assets/uploads/videos/' . $fileName;
        }
    }

    // Insert post (pending approval)
    $sql = "INSERT INTO posts (user_id, content, image_url, video_url, is_approved, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$userId, $content, $imageUrl, $videoUrl]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully! Waiting for admin approval.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create post']);
    }
}

function getAllPosts($db)
{
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $userId = $_SESSION['user_id'];

    // Get approved posts with user info and like status
    $sql = "SELECT 
                p.*,
                u.full_name as author_name,
                u.role as author_role,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            WHERE p.is_approved = 1
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";

    $posts = $db->query($sql, [$userId, $limit, $offset]);

    echo json_encode([
        'success' => true,
        'posts' => $posts ?: [],
        'page' => $page
    ]);
}

function toggleLike($db)
{
    $postId = intval($_POST['post_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        return;
    }

    // Check if already liked
    $checkSql = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
    $existing = $db->query($checkSql, [$postId, $userId]);

    if ($existing) {
        // Unlike
        $sql = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
        $db->query($sql, [$postId, $userId]);
        $liked = false;
    } else {
        // Like
        $sql = "INSERT INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $db->query($sql, [$postId, $userId]);
        $liked = true;
    }

    // Get updated likes count
    $countSql = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?";
    $result = $db->query($countSql, [$postId]);
    $likesCount = $result[0]['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $likesCount
    ]);
}

function addComment($db)
{
    $postId = intval($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    if (!$postId || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $sql = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
    $result = $db->query($sql, [$postId, $userId, $content]);

    if ($result) {
        // Get user info for the comment
        $userSql = "SELECT full_name, role FROM users WHERE id = ?";
        $user = $db->query($userSql, [$userId]);

        echo json_encode([
            'success' => true,
            'comment' => [
                'user_name' => $user[0]['full_name'],
                'user_role' => $user[0]['role'],
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
}

function getComments($db)
{
    $postId = intval($_GET['post_id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        return;
    }

    $sql = "SELECT 
                c.*,
                u.full_name as user_name,
                u.role as user_role
            FROM comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC";

    $comments = $db->query($sql, [$postId]);

    echo json_encode([
        'success' => true,
        'comments' => $comments ?: []
    ]);
}

function deletePost($db)
{
    $postId = intval($_POST['post_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    // Check if user owns the post
    $checkSql = "SELECT id FROM posts WHERE id = ? AND user_id = ?";
    $post = $db->query($checkSql, [$postId, $userId]);

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $sql = "DELETE FROM posts WHERE id = ?";
    $result = $db->query($sql, [$postId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Post deleted' : 'Failed to delete post'
    ]);
}
