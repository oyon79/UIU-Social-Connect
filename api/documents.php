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
    case 'upload':
        uploadDocument($db);
        break;
    case 'get_all':
        getAllDocuments($db);
        break;
    case 'get_my_documents':
        getMyDocuments($db);
        break;
    case 'download':
        downloadDocument($db);
        break;
    case 'search':
        searchDocuments($db);
        break;
    case 'delete':
        deleteDocument($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function uploadDocument($db)
{
    $userId = $_SESSION['user_id'];

    $noteType = trim($_POST['note_type'] ?? '');
    $noteName = trim($_POST['note_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($noteType) || empty($noteName)) {
        echo json_encode(['success' => false, 'message' => 'Note type and name are required']);
        return;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        return;
    }

    $file = $_FILES['file'];

    // Validate file size (100MB max)
    $maxSize = 100 * 1024 * 1024; // 100MB in bytes
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 100MB limit']);
        return;
    }

    $uploadDir = '../assets/uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $dbPath = 'assets/uploads/documents/' . $fileName;

        $sql = "INSERT INTO documents (user_id, note_type, note_name, description, file_path, file_size, file_type, is_approved, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";

        $result = $db->query($sql, [
            $userId,
            $noteType,
            $noteName,
            $description,
            $dbPath,
            $file['size'],
            $file['type']
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully! Waiting for admin approval.'
            ]);
        } else {
            // Delete uploaded file if database insert fails
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Failed to save document details']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
}

function getAllDocuments($db)
{
    $userId = $_SESSION['user_id'];
    $search = $_GET['search'] ?? '';
    $noteType = $_GET['note_type'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'recent'; // recent, downloads, name

    // Show approved documents from others OR user's own documents (approved or pending)
    $conditions = ['(d.is_approved = 1 OR d.user_id = ?)'];
    $params = [$userId];

    // Search filter
    if (!empty($search)) {
        $conditions[] = '(d.note_name LIKE ? OR d.description LIKE ?)';
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Note type filter
    if (!empty($noteType)) {
        $conditions[] = 'd.note_type = ?';
        $params[] = $noteType;
    }

    // Order by
    $orderBy = 'd.created_at DESC';
    switch ($sortBy) {
        case 'downloads':
            $orderBy = 'd.download_count DESC, d.created_at DESC';
            break;
        case 'name':
            $orderBy = 'd.note_name ASC';
            break;
    }

    $sql = "SELECT d.*, u.full_name as uploader_name, u.profile_image as uploader_image 
            FROM documents d
            INNER JOIN users u ON d.user_id = u.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY " . $orderBy;

    $documents = $db->query($sql, $params);

    if ($documents) {
        foreach ($documents as &$doc) {
            // Format file size
            $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        }
    }

    echo json_encode([
        'success' => true,
        'documents' => $documents ?: []
    ]);
}

function getMyDocuments($db)
{
    $userId = $_SESSION['user_id'];

    $sql = "SELECT * FROM documents 
            WHERE user_id = ? 
            ORDER BY created_at DESC";

    $documents = $db->query($sql, [$userId]);

    if ($documents) {
        foreach ($documents as &$doc) {
            $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        }
    }

    echo json_encode([
        'success' => true,
        'documents' => $documents ?: []
    ]);
}

function downloadDocument($db)
{
    $userId = $_SESSION['user_id'];
    $docId = intval($_GET['doc_id'] ?? 0);

    if (!$docId) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        return;
    }

    // Allow download if document is approved OR user owns it
    $sql = "SELECT * FROM documents WHERE id = ? AND (is_approved = 1 OR user_id = ?)";
    $doc = $db->query($sql, [$docId, $userId]);

    if (!$doc || empty($doc)) {
        echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
        return;
    }

    $document = $doc[0];
    $filePath = '../' . $document['file_path'];

    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => 'File not found on server']);
        return;
    }

    // Increment download count
    $updateSql = "UPDATE documents SET download_count = download_count + 1 WHERE id = ?";
    $db->query($updateSql, [$docId]);

    // Get original file extension
    $fileExtension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
    $downloadName = $document['note_name'];

    // Add extension if not present
    if (!preg_match('/\.' . preg_quote($fileExtension, '/') . '$/i', $downloadName)) {
        $downloadName .= '.' . $fileExtension;
    }

    // Force download with proper headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($filePath);
    exit;
}

function searchDocuments($db)
{
    $query = $_GET['query'] ?? '';

    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'documents' => []]);
        return;
    }

    $sql = "SELECT d.*, u.full_name as uploader_name 
            FROM documents d
            INNER JOIN users u ON d.user_id = u.id
            WHERE d.is_approved = 1 
            AND (d.note_name LIKE ? OR d.description LIKE ? OR u.full_name LIKE ?)
            ORDER BY d.created_at DESC
            LIMIT 50";

    $searchTerm = '%' . $query . '%';
    $documents = $db->query($sql, [$searchTerm, $searchTerm, $searchTerm]);

    if ($documents) {
        foreach ($documents as &$doc) {
            $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        }
    }

    echo json_encode([
        'success' => true,
        'documents' => $documents ?: []
    ]);
}

function deleteDocument($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $docId = intval($data['doc_id'] ?? 0);

    if (!$docId) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        return;
    }

    // Check if user owns the document
    $sql = "SELECT * FROM documents WHERE id = ? AND user_id = ?";
    $doc = $db->query($sql, [$docId, $userId]);

    if (!$doc || empty($doc)) {
        echo json_encode(['success' => false, 'message' => 'Document not found or unauthorized']);
        return;
    }

    $document = $doc[0];
    $filePath = '../' . $document['file_path'];

    // Delete from database
    $deleteSql = "DELETE FROM documents WHERE id = ?";
    if ($db->query($deleteSql, [$docId])) {
        // Delete file from server
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete document']);
    }
}

function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}
