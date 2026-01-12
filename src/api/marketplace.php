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
        $sql = "SELECT m.*, u.full_name as seller_name FROM marketplace_items m INNER JOIN users u ON m.user_id = u.id WHERE m.is_approved = 1 AND m.is_sold = 0 ORDER BY m.created_at DESC";
        $items = $db->query($sql);
        echo json_encode(['success' => true, 'items' => $items ?: []]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO marketplace_items (user_id, name, description, price, category, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $result = $db->query($sql, [$_SESSION['user_id'], $data['name'], $data['description'], $data['price'], $data['category']]);
        echo json_encode(['success' => $result ? true : false]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
