<?php
require_once 'db.php';

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// Redirect if not admin
function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// Check if user is approved
function isUserApproved($user_id)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT is_approved FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return (bool)$row['is_approved'];
    }

    return false;
}

// Get current user data
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Sanitize input
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Format date
function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

// Format time ago
function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . 's ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    } else {
        return date('M d, Y', $time);
    }
}

// Upload file
function uploadFile($file, $type = 'image')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    // Validate file type
    $allowed_types = ($type === 'video') ? ALLOWED_VIDEO_TYPES : ALLOWED_IMAGE_TYPES;
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Validate file size
    $max_size = ($type === 'video') ? MAX_VIDEO_SIZE : MAX_FILE_SIZE;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;

    // Determine upload directory
    $subdir = ($type === 'video') ? 'videos/' : ($type === 'profile' ? 'profiles/' : 'posts/');
    $upload_dir = UPLOAD_PATH . $subdir;

    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move file
    $destination = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'url' => UPLOAD_URL . $subdir . $filename
        ];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Send JSON response
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Create notification
function createNotification($user_id, $type, $title, $message, $reference_id = null, $reference_type = null)
{
    try {
        $db = Database::getInstance();

        $sql = "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $db->query($sql, [$user_id, $type, $title, $message, $reference_id, $reference_type]);

        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Create bulk notifications
function createBulkNotifications($userIds, $type, $title, $message, $referenceId = null, $referenceType = null)
{
    try {
        foreach ($userIds as $userId) {
            createNotification($userId, $type, $title, $message, $referenceId, $referenceType);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error creating bulk notifications: " . $e->getMessage());
        return false;
    }
}

// Get unread notifications count
function getUnreadNotificationsCount($user_id)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get pending approvals count (for admin)
function getPendingApprovalsCount()
{
    $db = getDB();

    $counts = [
        'users' => 0,
        'posts' => 0,
        'events' => 0,
        'jobs' => 0,
        'notices' => 0,
        'groups' => 0,
        'marketplace' => 0
    ];

    // Count pending users
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE is_approved = 0");
    $counts['users'] = $result->fetch_assoc()['count'];

    // Count pending posts
    $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE is_approved = 0");
    $counts['posts'] = $result->fetch_assoc()['count'];

    // Count pending events
    $result = $db->query("SELECT COUNT(*) as count FROM events WHERE is_approved = 0");
    $counts['events'] = $result->fetch_assoc()['count'];

    // Count pending jobs
    $result = $db->query("SELECT COUNT(*) as count FROM jobs WHERE is_approved = 0");
    $counts['jobs'] = $result->fetch_assoc()['count'];

    // Count pending notices
    $result = $db->query("SELECT COUNT(*) as count FROM notices WHERE is_approved = 0");
    $counts['notices'] = $result->fetch_assoc()['count'];

    // Count pending groups
    $result = $db->query("SELECT COUNT(*) as count FROM groups WHERE is_approved = 0");
    $counts['groups'] = $result->fetch_assoc()['count'];

    // Count pending marketplace items
    $result = $db->query("SELECT COUNT(*) as count FROM marketplace_items WHERE is_approved = 0");
    $counts['marketplace'] = $result->fetch_assoc()['count'];

    $counts['total'] = array_sum($counts);

    return $counts;
}

// Log activity
function logActivity($user_id, $admin_id, $action, $details = null)
{
    $db = getDB();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Verify user exists if user_id is provided
    if ($user_id !== null) {
        $check = $db->prepare("SELECT id FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            // User doesn't exist, set user_id to null
            $user_id = null;
        }
        $check->close();
    }

    // Verify admin exists if admin_id is provided
    if ($admin_id !== null) {
        $check = $db->prepare("SELECT id FROM admins WHERE id = ?");
        $check->bind_param("i", $admin_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            // Admin doesn't exist, set admin_id to null
            $admin_id = null;
        }
        $check->close();
    }

    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $user_id, $admin_id, $action, $details, $ip_address, $user_agent);
    return $stmt->execute();
}

// Send email (placeholder - implement with PHPMailer or similar)
function sendEmail($to, $subject, $message)
{
    // TODO: Implement email sending
    // For now, just log it
    error_log("Email to: $to, Subject: $subject, Message: $message");
    return true;
}

// Generate password reset token
function generatePasswordResetToken($email)
{
    $db = getDB();
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires_at);

    if ($stmt->execute()) {
        return $token;
    }

    return false;
}

// Verify password reset token
function verifyPasswordResetToken($token)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['email'];
    }

    return false;
}

/**
 * Calculate running trimester based on batch and current date
 * UIU has 3 trimesters per year:
 * - January – April (Trimester 1)
 * - May – August (Trimester 2)
 * - September – December (Trimester 3)
 * 
 * @param string $batch Batch number (e.g., "222", "231", "241")
 * @return int Calculated trimester number
 */
function calculateRunningTrimester($batch)
{
    // Current date
    $currentYear = date('Y');
    $currentMonth = date('n'); // 1-12

    // Extract year and batch number from batch code
    // Format: XYZ where X = year-2000, YZ = batch
    // 222 = 2022, 2nd batch
    // 231 = 2023, 1st batch
    // 261 = 2026, 1st batch

    $batchYear = 2000 + (int)substr($batch, 0, 2);
    $batchNumber = (int)substr($batch, 2, 1);

    // Calculate starting trimester based on batch number
    // Batch 1 starts in January (Tri 1)
    // Batch 2 starts in May (Tri 2)
    // Batch 3 starts in September (Tri 3)
    $startingTrimester = $batchNumber;

    // Calculate years passed since batch started
    $yearsPassed = $currentYear - $batchYear;

    // Determine current trimester of the year
    if ($currentMonth >= 1 && $currentMonth <= 4) {
        $currentTrimesterInYear = 1;
    } elseif ($currentMonth >= 5 && $currentMonth <= 8) {
        $currentTrimesterInYear = 2;
    } else {
        $currentTrimesterInYear = 3;
    }

    // Calculate total trimesters passed
    $trimestersPassed = ($yearsPassed * 3) + ($currentTrimesterInYear - $startingTrimester);

    // Running trimester is trimesters passed + 1 (current)
    $runningTrimester = $trimestersPassed + 1;

    // Cap at 12 trimesters maximum
    return min($runningTrimester, 12);
}

/**
 * Get batch to trimester mapping as of today
 * 
 * @return array Associative array of batch => trimester
 */
function getBatchTrimesterMapping()
{
    return [
        '222' => 12,
        '223' => 11,
        '231' => 10,
        '232' => 9,
        '233' => 8,
        '241' => 7,
        '242' => 6,
        '243' => 5,
        '251' => 4,
        '252' => 3,
        '253' => 2,
        '261' => 1,
        '262' => 0 // Not started
    ];
}

/**
 * Get running trimester for a batch using predefined mapping
 * Fallback to calculation if not in mapping
 * 
 * @param string $batch Batch number
 * @return int Running trimester
 */
function getRunningTrimester($batch)
{
    $mapping = getBatchTrimesterMapping();

    if (isset($mapping[$batch])) {
        return $mapping[$batch];
    }

    // Fallback to calculation
    return calculateRunningTrimester($batch);
}
