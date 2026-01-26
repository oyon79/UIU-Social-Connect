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
    case 'get_all':
        getAllItems($db);
        break;
    case 'get_by_id':
        getItemById($db);
        break;
    case 'get_my_items':
        getMyItems($db);
        break;
    case 'create':
        createItem($db);
        break;
    case 'update':
        updateItem($db);
        break;
    case 'delete':
        deleteItem($db);
        break;
    case 'mark_sold':
        markAsSold($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getAllItems($db)
{
    $sql = "SELECT m.*, u.full_name as seller_name, u.profile_image as seller_image
            FROM marketplace_items m 
            INNER JOIN users u ON m.user_id = u.id 
            WHERE m.is_approved = 1 AND m.is_sold = 0 
            ORDER BY m.created_at DESC";

    $items = $db->query($sql);

    if ($items === false) {
        echo json_encode(['success' => false, 'message' => 'Database query failed']);
        return;
    }

    echo json_encode(['success' => true, 'items' => $items ?: []]);
}

function getItemById($db)
{
    $itemId = intval($_GET['id'] ?? 0);

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    $sql = "SELECT m.*, u.full_name as seller_name, u.profile_image as seller_image, u.id as seller_id
            FROM marketplace_items m 
            INNER JOIN users u ON m.user_id = u.id 
            WHERE m.id = ? AND m.is_approved = 1";

    $items = $db->query($sql, [$itemId]);

    if ($items && count($items) > 0) {
        echo json_encode(['success' => true, 'item' => $items[0]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
}

function getMyItems($db)
{
    $userId = $_SESSION['user_id'];

    $sql = "SELECT m.*, u.full_name as seller_name
            FROM marketplace_items m 
            INNER JOIN users u ON m.user_id = u.id 
            WHERE m.user_id = ? 
            ORDER BY m.created_at DESC";

    $items = $db->query($sql, [$userId]);

    echo json_encode(['success' => true, 'items' => $items ?: []]);
}

function createItem($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Handle form data if JSON is not available (for file uploads)
    if (empty($data)) {
        $data = $_POST;
    }

    $title = trim($data['title'] ?? $data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $category = trim($data['category'] ?? 'Other');
    $conditionStatus = trim($data['condition_status'] ?? 'good');

    if (!$title || !$description || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Title, description, and valid price are required']);
        return;
    }

    $imageUrl = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/marketplace/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            $fileName = uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                $imageUrl = 'assets/uploads/marketplace/' . $fileName;
            }
        }
    }

    $sql = "INSERT INTO marketplace_items (user_id, title, description, price, category, condition_status, image_url, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$userId, $title, $description, $price, $category, $conditionStatus, $imageUrl]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Item listed! Waiting for admin approval.' : 'Failed to list item'
    ]);
}

function updateItem($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Handle form data if JSON is not available (for file uploads)
    if (empty($data)) {
        $data = $_POST;
    }

    $itemId = intval($data['id'] ?? 0);

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    // Check if user owns this item
    $checkSql = "SELECT user_id, image_url FROM marketplace_items WHERE id = ?";
    $item = $db->query($checkSql, [$itemId]);

    if (!$item || $item[0]['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or item not found']);
        return;
    }

    $title = trim($data['title'] ?? $data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $category = trim($data['category'] ?? 'Other');
    $conditionStatus = trim($data['condition_status'] ?? 'good');

    if (!$title || !$description || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Title, description, and valid price are required']);
        return;
    }

    $imageUrl = $item[0]['image_url'];
    $removeImage = isset($data['remove_image']) && $data['remove_image'] == '1';

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($imageUrl && file_exists('../' . $imageUrl)) {
            unlink('../' . $imageUrl);
        }

        $uploadDir = '../assets/uploads/marketplace/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            $fileName = uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                $imageUrl = 'assets/uploads/marketplace/' . $fileName;
            }
        }
    } elseif ($removeImage) {
        // Remove existing image
        if ($imageUrl && file_exists('../' . $imageUrl)) {
            unlink('../' . $imageUrl);
        }
        $imageUrl = null;
    }

    $sql = "UPDATE marketplace_items 
            SET title = ?, description = ?, price = ?, category = ?, condition_status = ?, image_url = ?
            WHERE id = ? AND user_id = ?";

    $result = $db->query($sql, [$title, $description, $price, $category, $conditionStatus, $imageUrl, $itemId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Item updated successfully' : 'Failed to update item'
    ]);
}

function deleteItem($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($data['id'] ?? 0);

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    // Check if user owns this item
    $checkSql = "SELECT user_id, image_url FROM marketplace_items WHERE id = ?";
    $item = $db->query($checkSql, [$itemId]);

    if (!$item || $item[0]['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or item not found']);
        return;
    }

    // Delete image if exists
    if ($item[0]['image_url'] && file_exists('../' . $item[0]['image_url'])) {
        unlink('../' . $item[0]['image_url']);
    }

    $sql = "DELETE FROM marketplace_items WHERE id = ? AND user_id = ?";
    $result = $db->query($sql, [$itemId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Item deleted successfully' : 'Failed to delete item'
    ]);
}

function markAsSold($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($data['id'] ?? 0);

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }

    // Check if user owns this item
    $checkSql = "SELECT user_id FROM marketplace_items WHERE id = ?";
    $item = $db->query($checkSql, [$itemId]);

    if (!$item || $item[0]['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or item not found']);
        return;
    }

    $sql = "UPDATE marketplace_items SET is_sold = 1 WHERE id = ? AND user_id = ?";
    $result = $db->query($sql, [$itemId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Item marked as sold' : 'Failed to update item'
    ]);
}
