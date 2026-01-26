<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/courses.php';

header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create_all':
        createGroupsForAllStudents();
        break;
    case 'create_for_user':
        $userId = intval($_GET['user_id'] ?? 0);
        if ($userId > 0) {
            createGroupsForUser($userId);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
    case 'get_students':
        getStudentsList();
        break;
    case 'get_student_info':
        $userId = intval($_GET['user_id'] ?? 0);
        if ($userId > 0) {
            getStudentInfo($userId);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Create course groups for all students who don't have them yet
 */
function createGroupsForAllStudents()
{
    $db = Database::getInstance();

    // Get all students with department, batch, and trimester
    $sql = "SELECT id, department, batch, trimester, full_name 
            FROM users 
            WHERE role = 'Student' 
            AND department IS NOT NULL 
            AND batch IS NOT NULL 
            AND trimester IS NOT NULL 
            AND is_approved = 1";

    $students = $db->query($sql, []);

    if (!$students || empty($students)) {
        echo json_encode([
            'success' => false,
            'message' => 'No eligible students found'
        ]);
        return;
    }

    $created = 0;
    $errors = 0;
    $details = [];

    foreach ($students as $student) {
        try {
            $result = createCourseGroupsForStudent(
                $db,
                $student['id'],
                $student['department'],
                $student['batch'],
                $student['trimester']
            );

            $created += $result['created'];
            $details[] = [
                'user' => $student['full_name'],
                'created' => $result['created'],
                'joined' => $result['joined']
            ];
        } catch (Exception $e) {
            $errors++;
            $details[] = [
                'user' => $student['full_name'],
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Created/joined {$created} course groups for " . count($students) . " students",
        'students_processed' => count($students),
        'groups_created' => $created,
        'errors' => $errors,
        'details' => $details
    ]);
}

/**
 * Create course groups for a specific user
 */
function createGroupsForUser($userId)
{
    $db = Database::getInstance();

    // Get user details - search by both id and student_id
    $sql = "SELECT id, department, batch, trimester, full_name, role 
            FROM users 
            WHERE id = ? OR student_id = ?";

    $users = $db->query($sql, [$userId, $userId]);

    if (!$users || empty($users)) {
        echo json_encode(['success' => false, 'message' => 'User not found. Please check the Student ID or User ID.']);
        return;
    }

    $user = $users[0];

    if ($user['role'] !== 'Student') {
        echo json_encode(['success' => false, 'message' => 'User is not a student']);
        return;
    }

    if (empty($user['department']) || empty($user['batch']) || empty($user['trimester'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Student missing required data (department, batch, or trimester)'
        ]);
        return;
    }

    try {
        $result = createCourseGroupsForStudent(
            $db,
            $user['id'],
            $user['department'],
            $user['batch'],
            $user['trimester']
        );

        echo json_encode([
            'success' => true,
            'message' => "Created/joined {$result['created']} groups for {$user['full_name']}",
            'user_name' => $user['full_name'],
            'groups_created' => $result['created'],
            'groups_joined' => $result['joined']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating groups: ' . $e->getMessage()
        ]);
    }
}

/**
 * Create course-based groups and auto-join student
 * 
 * @param Database $db Database instance
 * @param int $user_id User ID
 * @param string $department Department (CSE/EEE)
 * @param string $batch Batch number
 * @param int $trimester Running trimester number
 * @return array ['created' => int, 'joined' => int]
 */
function createCourseGroupsForStudent($db, $user_id, $department, $batch, $trimester)
{
    $created = 0;
    $joined = 0;

    // Get courses only for the current trimester (not all previous trimesters)
    $courses = getCoursesByTrimester($department, $trimester);

    if (empty($courses)) {
        return ['created' => 0, 'joined' => 0];
    }

    foreach ($courses as $course) {
        $course_code = $course['code'];
        $course_title = $course['title'];
        // Make group name batch-specific
        $group_name = "{$department} {$batch} - {$course_code}: {$course_title}";
        $group_description = "Course group for {$course_code} - {$course_title}. Department: {$department}, Batch: {$batch}, Trimester: {$trimester}.";

        // Check if group already exists for this batch
        $checkSql = "SELECT id FROM groups 
                    WHERE course_code = ? 
                    AND trimester_number = ? 
                    AND department = ? 
                    AND batch = ?
                    AND is_auto_created = 1";

        $existing = $db->query($checkSql, [$course_code, $trimester, $department, $batch]);

        if ($existing && !empty($existing)) {
            // Group exists, just join
            $group_id = $existing[0]['id'];
        } else {
            // Create new group (auto-approved, system-created using admin/first user)
            $insertSql = "INSERT INTO groups 
                        (name, description, category, group_type, creator_id, 
                         course_code, trimester_number, department, batch,
                         is_auto_created, is_approved, members_count, created_at) 
                        VALUES (?, ?, 'Academic', 'course', ?, ?, ?, ?, ?, 1, 1, 0, NOW())";

            $result = $db->query($insertSql, [
                $group_name,
                $group_description,
                $user_id, // Use current user as creator
                $course_code,
                $trimester,
                $department,
                $batch
            ]);

            if ($result) {
                $group_id = $db->getConnection()->insert_id;
                $created++;
            } else {
                continue; // Skip this group if creation failed
            }
        }

        // Check if user is already a member
        $memberCheckSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
        $isMember = $db->query($memberCheckSql, [$group_id, $user_id]);

        if (!$isMember || empty($isMember)) {
            // Add user as member
            $joinSql = "INSERT INTO group_members (group_id, user_id, role, joined_at) 
                       VALUES (?, ?, 'member', NOW())";

            $db->query($joinSql, [$group_id, $user_id]);
            $joined++;

            // Update member count
            $updateCountSql = "UPDATE groups 
                              SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) 
                              WHERE id = ?";
            $db->query($updateCountSql, [$group_id, $group_id]);
        }
    }

    return ['created' => $created, 'joined' => $joined];
}

/**
 * Get list of all students for dropdown
 */
function getStudentsList()
{
    $db = Database::getInstance();

    $sql = "SELECT id, full_name, email, student_id, department, batch, trimester 
            FROM users 
            WHERE role = 'Student' 
            AND is_approved = 1
            ORDER BY full_name ASC";

    $students = $db->query($sql, []);

    if (!$students) {
        echo json_encode(['success' => false, 'message' => 'Error fetching students']);
        return;
    }

    echo json_encode(['success' => true, 'students' => $students]);
}

/**
 * Get detailed info about a specific student
 */
function getStudentInfo($userId)
{
    $db = Database::getInstance();

    $sql = "SELECT id, full_name, email, student_id, department, batch, trimester, role, is_approved 
            FROM users 
            WHERE id = ?";

    $users = $db->query($sql, [$userId]);

    if (!$users || empty($users)) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        return;
    }

    $student = $users[0];

    if ($student['role'] !== 'Student') {
        echo json_encode(['success' => false, 'message' => 'User is not a student']);
        return;
    }

    echo json_encode(['success' => true, 'student' => $student]);
}
