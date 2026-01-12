<?php
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
    case 'get_all':
        $sql = "SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count FROM groups g WHERE is_approved = 1 ORDER BY created_at DESC";
        $groups = $db->query($sql);
        echo json_encode(['success' => true, 'groups' => $groups ?: []]);
        break;

    case 'get_user_groups':
        // Get groups where user is a member
        $userId = $_SESSION['user_id'];
        $sql = "SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count
                FROM groups g
                INNER JOIN group_members gm ON g.id = gm.group_id
                WHERE g.is_approved = 1 AND gm.user_id = ?
                ORDER BY g.created_at DESC
                LIMIT 10";
        $groups = $db->query($sql, [$userId]);
        echo json_encode(['success' => true, 'groups' => $groups ?: []]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO groups (name, description, creator_id, is_approved, created_at) VALUES (?, ?, ?, 0, NOW())";
        $result = $db->query($sql, [$data['name'], $data['description'], $_SESSION['user_id']]);
        echo json_encode(['success' => $result ? true : false]);
        break;

    case 'join':
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())";
        $result = $db->query($sql, [$data['group_id'], $_SESSION['user_id']]);
        echo json_encode(['success' => $result ? true : false]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
