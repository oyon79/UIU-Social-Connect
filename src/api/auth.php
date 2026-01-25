<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get request data - check both GET parameter and JSON body
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

// Handle logout separately (can be GET or POST, and needs redirect)
if ($action === 'logout') {
    handleLogout();
    exit; // Exit after redirect
}

// For other actions, set JSON header
header('Content-Type: application/json');

switch ($action) {
    case 'login':
        handleLogin($input);
        break;

    case 'register':
        handleRegister($input);
        break;

    case 'forgot_password':
        handleForgotPassword($input);
        break;

    case 'reset_password':
        handleResetPassword($input);
        break;

    case 'change_password':
        handleChangePassword($input);
        break;

    case 'check_session':
        checkSession();
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

// Handle Login
function handleLogin($input)
{
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required']);
    }

    $db = getDB();

    // Check if admin login
    if ($email === 'admin@gmail.com') {
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            // For demo, use plain password. In production, use password_verify()
            if ($password === '123456' || password_verify($password, $admin['password'])) {
                // Set admin session keys
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['is_admin'] = true;

                // Also set the user session keys expected by pages that check user session
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['full_name'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['is_approved'] = true;

                logActivity(null, $admin['id'], 'Admin Login', 'Admin logged in successfully');

                jsonResponse([
                    'success' => true,
                    'message' => 'Admin login successful',
                    'user' => [
                        'id' => $admin['id'],
                        'name' => $admin['full_name'],
                        'email' => $admin['email'],
                        'role' => 'admin',
                        'is_approved' => true
                    ]
                ]);
            }
        }

        jsonResponse(['success' => false, 'message' => 'Invalid admin credentials']);
    }

    // Regular user login
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if user is approved
            if (!$user['is_approved']) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Your account is pending admin approval. Please wait for approval before logging in.'
                ]);
            }

            // Check if user is active
            if (!$user['is_active']) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact admin.'
                ]);
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_approved'] = $user['is_approved'];

            logActivity($user['id'], null, 'User Login', 'User logged in successfully');

            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'is_approved' => (bool)$user['is_approved'],
                    'profile_image' => $user['profile_image']
                ]
            ]);
        }
    }

    jsonResponse(['success' => false, 'message' => 'Invalid email or password']);
}

// Handle Registration
function handleRegister($input)
{
    require_once '../includes/courses.php';
    
    $full_name = sanitize($input['full_name'] ?? '');
    $email = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = sanitize($input['role'] ?? 'Student');
    $student_id = sanitize($input['student_id'] ?? '');
    $department = sanitize($input['department'] ?? '');
    $batch = sanitize($input['batch'] ?? '');
    $skills = $input['skills'] ?? [];

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'All required fields must be filled']);
    }

    // Validate email format (UIU email - case-insensitive)
    if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.uiu\.ac\.bd$/i', $email)) {
        jsonResponse(['success' => false, 'message' => 'Email must be in UIU format: username@domain.uiu.ac.bd']);
    }

    // Validate password length
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters']);
    }
    
    // Role-specific validation
    if ($role === 'Student') {
        if (empty($student_id) || empty($department) || empty($batch)) {
            jsonResponse(['success' => false, 'message' => 'Student ID, Department, and Batch are required for students']);
        }
        if (empty($skills) || count($skills) < 1 || count($skills) > 5) {
            jsonResponse(['success' => false, 'message' => 'Students must select 1 to 5 skills']);
        }
    } elseif ($role === 'Alumni' || $role === 'Faculty') {
        if (empty($student_id)) {
            jsonResponse(['success' => false, 'message' => 'ID is required']);
        }
        if (empty($skills) || count($skills) < 1 || count($skills) > 5) {
            jsonResponse(['success' => false, 'message' => 'Please select 1 to 5 skills']);
        }
    }

    $db = getDB();

    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Email already registered']);
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Calculate trimester for students
    $trimester = null;
    if ($role === 'Student' && !empty($batch)) {
        $trimester = getRunningTrimester($batch);
    }
    
    // Convert skills array to JSON
    $skills_json = !empty($skills) ? json_encode($skills) : null;

    // Insert user (not approved by default)
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, student_id, department, batch, trimester, skills, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("sssssssis", $full_name, $email, $hashed_password, $role, $student_id, $department, $batch, $trimester, $skills_json);

    if ($stmt->execute()) {
        $user_id = $db->insert_id;

        // Auto-create and join course groups for students
        if ($role === 'Student' && !empty($department) && !empty($batch) && !empty($trimester)) {
            createCourseGroupsForStudent($db, $user_id, $department, $batch, $trimester);
        }

        logActivity($user_id, null, 'User Registration', 'New user registered: ' . $email);

        jsonResponse([
            'success' => true,
            'message' => 'Registration successful! Your account is pending admin approval.',
            'user_id' => $user_id
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

/**
 * Create course-based groups and auto-join student
 * 
 * @param object $db Database connection
 * @param int $user_id User ID
 * @param string $department Department (CSE/EEE)
 * @param string $batch Batch number
 * @param int $trimester Running trimester number
 */
function createCourseGroupsForStudent($db, $user_id, $department, $batch, $trimester) {
    require_once '../includes/courses.php';
    
    // Loop through all trimesters up to running trimester
    for ($tri = 1; $tri <= $trimester; $tri++) {
        $courses = getCoursesByTrimester($department, $tri);
        
        foreach ($courses as $course) {
            $course_code = $course['code'];
            $course_title = $course['title'];
            $group_name = "{$department} - {$course_code}: {$course_title}";
            $group_description = "Automated course group for {$course_code} - {$course_title}. Batch {$batch}, Trimester {$tri}.";
            
            // Check if group already exists
            $checkStmt = $db->prepare("SELECT id FROM groups WHERE course_code = ? AND trimester_number = ? AND department = ? AND is_auto_created = 1");
            $checkStmt->bind_param("sis", $course_code, $tri, $department);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Group exists, just join
                $group = $result->fetch_assoc();
                $group_id = $group['id'];
            } else {
                // Create new group (auto-approved, system-created using first user)
                $insertStmt = $db->prepare("INSERT INTO groups (name, description, category, group_type, creator_id, course_code, trimester_number, department, is_auto_created, is_approved, members_count) VALUES (?, ?, 'Academic', 'course', ?, ?, ?, ?, 1, 1, 0)");
                $insertStmt->bind_param("ssissi", $group_name, $group_description, $user_id, $course_code, $tri, $department);
                $insertStmt->execute();
                $group_id = $db->insert_id;
            }
            
            // Join student to group (if not already a member)
            $joinCheckStmt = $db->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $joinCheckStmt->bind_param("ii", $group_id, $user_id);
            $joinCheckStmt->execute();
            
            if ($joinCheckStmt->get_result()->num_rows == 0) {
                $joinStmt = $db->prepare("INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())");
                $joinStmt->bind_param("ii", $group_id, $user_id);
                $joinStmt->execute();
                
                // Update member count
                $updateStmt = $db->prepare("UPDATE groups SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) WHERE id = ?");
                $updateStmt->bind_param("ii", $group_id, $group_id);
                $updateStmt->execute();
            }
        }
    }
}

// Handle Logout
function handleLogout()
{
    // Log activity before destroying session
    if (isset($_SESSION['user_id'])) {
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], null, 'User Logout', 'User logged out');
        }
    } elseif (isset($_SESSION['admin_id'])) {
        if (function_exists('logActivity')) {
            logActivity(null, $_SESSION['admin_id'], 'Admin Logout', 'Admin logged out');
        }
    }

    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Redirect to login page
    header('Location: ../index.php');
    exit;
}

// Handle Forgot Password
function handleForgotPassword($input)
{
    $email = sanitize($input['email'] ?? '');

    if (empty($email)) {
        jsonResponse(['success' => false, 'message' => 'Email is required']);
    }

    $db = getDB();

    // Check if user exists
    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Generate reset token
        $token = generatePasswordResetToken($email);

        if ($token) {
            // In production, send email with reset link
            $reset_link = SITE_URL . "/reset-password.php?token=" . $token;

            // For demo purposes, just log it
            error_log("Password reset link for {$email}: {$reset_link}");

            // TODO: Send email
            // sendEmail($email, 'Password Reset', "Click here to reset your password: {$reset_link}");

            jsonResponse([
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'reset_link' => $reset_link // Remove this in production
            ]);
        }
    }

    // Don't reveal if email exists or not (security)
    jsonResponse([
        'success' => true,
        'message' => 'If that email exists, a password reset link has been sent'
    ]);
}

// Handle Reset Password
function handleResetPassword($input)
{
    $token = sanitize($input['token'] ?? '');
    $new_password = $input['new_password'] ?? '';

    if (empty($token) || empty($new_password)) {
        jsonResponse(['success' => false, 'message' => 'Token and new password are required']);
    }

    if (strlen($new_password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters']);
    }

    // Verify token
    $email = verifyPasswordResetToken($token);

    if (!$email) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset token']);
    }

    $db = getDB();

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        // Delete used token
        $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        logActivity(null, null, 'Password Reset', "Password reset for: {$email}");

        jsonResponse([
            'success' => true,
            'message' => 'Password reset successful. You can now login with your new password.'
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Failed to reset password']);
}

// Handle Change Password
function handleChangePassword($input)
{
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        jsonResponse(['success' => false, 'message' => 'Current password and new password are required']);
        return;
    }

    if (strlen($new_password) < 6) {
        jsonResponse(['success' => false, 'message' => 'New password must be at least 6 characters']);
        return;
    }

    $db = getDB();
    $userId = $_SESSION['user_id'];

    // Get current user password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }

        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $userId);

        if ($stmt->execute()) {
            logActivity($userId, null, 'Password Change', 'User changed password successfully');
            jsonResponse([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            return;
        }
    }

    jsonResponse(['success' => false, 'message' => 'Failed to change password']);
}

// Check Session
function checkSession()
{
    if (isLoggedIn()) {
        $user = getCurrentUser();
        jsonResponse([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'is_approved' => (bool)$user['is_approved'],
                'profile_image' => $user['profile_image']
            ]
        ]);
    } elseif (isAdminLoggedIn()) {
        jsonResponse([
            'success' => true,
            'logged_in' => true,
            'is_admin' => true,
            'user' => [
                'id' => $_SESSION['admin_id'],
                'name' => $_SESSION['admin_name'],
                'role' => 'admin'
            ]
        ]);
    }

    jsonResponse([
        'success' => true,
        'logged_in' => false
    ]);
}
