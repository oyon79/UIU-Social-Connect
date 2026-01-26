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
        getAllJobs($db);
        break;
    case 'create':
        createJob($db);
        break;
    case 'apply':
        applyToJob($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getAllJobs($db)
{
    // Get all approved jobs
    $sql = "SELECT j.*
            FROM jobs j
            WHERE j.is_approved = 1
            ORDER BY j.created_at DESC";

    $jobs = $db->query($sql);

    if ($jobs === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Database query failed'
        ]);
        return;
    }

    // Add applications_count (try to get from job_applications table if it exists)
    if ($jobs) {
        foreach ($jobs as &$job) {
            $job['applications_count'] = 0; // Default to 0
            
            // Try to get count if job_applications table exists
            $countSql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
            $countResult = $db->query($countSql, [$job['id']]);
            if ($countResult !== false && !empty($countResult)) {
                $job['applications_count'] = intval($countResult[0]['count'] ?? 0);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'jobs' => $jobs ?: []
    ]);
}

function createJob($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $company = trim($data['company'] ?? '');
    $description = trim($data['description'] ?? '');
    $jobType = trim($data['job_type'] ?? 'Full-time');
    $location = trim($data['location'] ?? '');

    if (!$title || !$company || !$description || !$location) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        return;
    }

    $sql = "INSERT INTO jobs (user_id, title, company, description, job_type, location, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$userId, $title, $company, $description, $jobType, $location]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Job posted, waiting for approval' : 'Failed to post job'
    ]);
}

function applyToJob($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);

    if (!$jobId) {
        echo json_encode(['success' => false, 'message' => 'Invalid job']);
        return;
    }

    // Check if already applied
    $checkSql = "SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?";
    $existing = $db->query($checkSql, [$jobId, $userId]);

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Already applied']);
        return;
    }

    $sql = "INSERT INTO job_applications (job_id, user_id, created_at) VALUES (?, ?, NOW())";
    $result = $db->query($sql, [$jobId, $userId]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Application submitted' : 'Failed to apply'
    ]);
}
