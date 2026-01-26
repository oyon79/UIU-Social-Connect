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
        $sql = "SELECT n.*, u.full_name as posted_by 
                FROM notices n 
                INNER JOIN users u ON n.user_id = u.id 
                WHERE n.is_approved = 1 
                ORDER BY 
                    CASE n.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        ELSE 4 
                    END,
                    n.created_at DESC";
        $notices = $db->query($sql);

        if ($notices === false) {
            echo json_encode(['success' => false, 'message' => 'Database query failed']);
            break;
        }

        echo json_encode(['success' => true, 'notices' => $notices ?: []]);
        break;

    case 'get_important':
        // Get important notices (high or urgent priority)
        $sql = "SELECT n.*, u.full_name as posted_by 
                FROM notices n 
                INNER JOIN users u ON n.user_id = u.id 
                WHERE n.is_approved = 1 AND n.priority IN ('high', 'urgent')
                ORDER BY 
                    CASE n.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        ELSE 3 
                    END,
                    n.created_at DESC
                LIMIT 5";
        $notices = $db->query($sql);
        echo json_encode(['success' => true, 'notices' => $notices ?: []]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $priority = trim($data['priority'] ?? 'normal');

        if (!$title || !$content) {
            echo json_encode(['success' => false, 'message' => 'Title and content are required']);
            break;
        }

        // Validate priority
        $allowedPriorities = ['low', 'medium', 'high', 'urgent', 'normal'];
        if (!in_array($priority, $allowedPriorities)) {
            $priority = 'normal';
        }

        $sql = "INSERT INTO notices (user_id, title, content, priority, is_approved, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $result = $db->query($sql, [$_SESSION['user_id'], $title, $content, $priority]);

        echo json_encode([
            'success' => $result ? true : false,
            'message' => $result ? 'Notice posted, waiting for approval' : 'Failed to post notice'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
