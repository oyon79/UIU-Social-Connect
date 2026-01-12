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
        $sql = "SELECT n.*, u.full_name as posted_by FROM notices n INNER JOIN users u ON n.user_id = u.id WHERE n.is_approved = 1 ORDER BY n.created_at DESC";
        $notices = $db->query($sql);
        echo json_encode(['success' => true, 'notices' => $notices ?: []]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO notices (user_id, title, content, priority, is_approved, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $result = $db->query($sql, [$_SESSION['user_id'], $data['title'], $data['content'], $data['priority']]);
        echo json_encode(['success' => $result ? true : false]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
