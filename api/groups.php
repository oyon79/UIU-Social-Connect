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
        getAllGroups($db);
        break;
    case 'get_by_id':
        getGroupById($db);
        break;
    case 'get_user_groups':
        getUserGroups($db);
        break;
    case 'get_members':
        getGroupMembers($db);
        break;
    case 'create':
        createGroup($db);
        break;
    case 'update':
        updateGroup($db);
        break;
    case 'delete':
        deleteGroup($db);
        break;
    case 'join':
        joinGroup($db);
        break;
    case 'leave':
        leaveGroup($db);
        break;
    case 'check_membership':
        checkMembership($db);
        break;
    case 'get_messages':
        getGroupMessages($db);
        break;
    case 'send_message':
        sendGroupMessage($db);
        break;
    case 'add_members':
        addMembersToGroup($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getAllGroups($db)
{
    $userId = $_SESSION['user_id'];
    $filterType = $_GET['filter'] ?? 'all'; // 'all', 'my_courses', 'joined'

    // Get user info for filtering
    $userSql = "SELECT department, batch, trimester, role FROM users WHERE id = ?";
    $userInfo = $db->query($userSql, [$userId]);

    $whereClauses = ["g.is_approved = 1"];
    $params = [$userId];

    // Filter based on type
    if ($filterType === 'my_courses' && $userInfo && !empty($userInfo)) {
        $user = $userInfo[0];
        // Course groups are only for students
        if ($user['role'] !== 'Student') {
            echo json_encode(['success' => true, 'groups' => [], 'message' => 'Course groups are only available for students']);
            return;
        }
        // Show only course groups for student's department, batch, and CURRENT trimester only
        if ($user['department'] && $user['trimester']) {
            $whereClauses[] = "(g.is_auto_created = 1 AND g.department = ? AND g.trimester_number = ? AND (g.batch = ? OR g.batch IS NULL))";
            $params[] = $user['department'];
            $params[] = $user['trimester'];
            $params[] = $user['batch'];
        }
    } elseif ($filterType === 'joined') {
        $whereClauses[] = "EXISTS (SELECT 1 FROM group_members WHERE group_id = g.id AND user_id = ?)";
        $params[] = $userId;
    }

    $whereClause = implode(' AND ', $whereClauses);

    $sql = "SELECT g.*, 
                   u.full_name as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND user_id = ?) as is_member
            FROM groups g
            INNER JOIN users u ON g.creator_id = u.id
            WHERE {$whereClause}
            ORDER BY g.is_auto_created DESC, g.created_at DESC";

    $groups = $db->query($sql, $params);

    if ($groups) {
        foreach ($groups as &$group) {
            $group['is_member'] = (bool)($group['is_member'] ?? 0);
            $group['is_creator'] = ($group['creator_id'] == $userId);
            // Decode JSON fields
            $group['required_skills'] = $group['required_skills'] ? json_decode($group['required_skills'], true) : [];
        }
    }

    echo json_encode(['success' => true, 'groups' => $groups ?: []]);
}

function getGroupById($db)
{
    $groupId = intval($_GET['group_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    $sql = "SELECT g.*, 
                   u.full_name as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND user_id = ?) as is_member,
                   (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
            FROM groups g
            INNER JOIN users u ON g.creator_id = u.id
            WHERE g.id = ? AND g.is_approved = 1";

    $group = $db->query($sql, [$userId, $userId, $groupId]);

    if ($group && !empty($group)) {
        $group[0]['is_member'] = (bool)($group[0]['is_member'] ?? 0);
        $group[0]['is_creator'] = ($group[0]['creator_id'] == $userId);
        echo json_encode(['success' => true, 'group' => $group[0]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
    }
}

function getUserGroups($db)
{
    $userId = $_SESSION['user_id'];

    $sql = "SELECT g.*, 
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
                   gm.role as user_role
            FROM groups g
            INNER JOIN group_members gm ON g.id = gm.group_id
            WHERE g.is_approved = 1 AND gm.user_id = ?
            ORDER BY g.created_at DESC
            LIMIT 10";

    $groups = $db->query($sql, [$userId]);

    echo json_encode(['success' => true, 'groups' => $groups ?: []]);
}

function getGroupMembers($db)
{
    $groupId = intval($_GET['group_id'] ?? 0);

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    $sql = "SELECT u.id, u.full_name, u.profile_image, u.role, gm.role as member_role, gm.joined_at
            FROM group_members gm
            INNER JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND u.is_approved = 1
            ORDER BY 
                CASE gm.role
                    WHEN 'admin' THEN 1
                    WHEN 'moderator' THEN 2
                    ELSE 3
                END,
                gm.joined_at ASC";

    $members = $db->query($sql, [$groupId]);

    echo json_encode(['success' => true, 'members' => $members ?: []]);
}

function createGroup($db)
{
    $userId = $_SESSION['user_id'];

    // Handle both FormData (multipart/form-data) and JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // If JSON decode failed or empty, try $_POST (for FormData)
    if ($data === null || empty($data)) {
        $data = $_POST;
    }

    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category = trim($data['category'] ?? '');
    $groupType = trim($data['group_type'] ?? 'general');
    $requiredSkills = $data['required_skills'] ?? [];
    $memberIds = isset($data['member_ids']) ? json_decode($data['member_ids'], true) : [];

    if (empty($name) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Name and description are required']);
        return;
    }

    // Convert required_skills to JSON
    $requiredSkillsJson = null;
    if (!empty($requiredSkills) && is_array($requiredSkills)) {
        $requiredSkillsJson = json_encode($requiredSkills);
    }

    // Handle image upload if provided
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/groups/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imageUrl = 'assets/uploads/groups/' . $fileName;
        }
    }

    $sql = "INSERT INTO groups (name, description, category, group_type, required_skills, creator_id, image_url, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$name, $description, $category, $groupType, $requiredSkillsJson, $userId, $imageUrl]);

    if ($result) {
        $groupId = $db->getConnection()->insert_id;

        // Add creator as admin member
        $memberSql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'admin', NOW())";
        $db->query($memberSql, [$groupId, $userId]);

        // Add selected members as regular members
        if (!empty($memberIds) && is_array($memberIds)) {
            $memberInsertSql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())";

            foreach ($memberIds as $memberId) {
                $memberId = intval($memberId);
                if ($memberId > 0 && $memberId !== $userId) { // Don't re-add creator
                    // Check if user exists and is approved
                    $userCheck = $db->query("SELECT id FROM users WHERE id = ? AND is_approved = 1", [$memberId]);
                    if ($userCheck && !empty($userCheck)) {
                        $db->query($memberInsertSql, [$groupId, $memberId]);
                    }
                }
            }
        }

        // Update members count
        $updateCountSql = "UPDATE groups SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) WHERE id = ?";
        $db->query($updateCountSql, [$groupId, $groupId]);

        $addedCount = !empty($memberIds) ? count($memberIds) : 0;

        echo json_encode([
            'success' => true,
            'message' => $addedCount > 0
                ? "Group created successfully with {$addedCount} member(s) added! Waiting for admin approval."
                : 'Group created successfully! Waiting for admin approval.',
            'group_id' => $groupId,
            'members_added' => $addedCount
        ]);
    } else {
        error_log("Database error in createGroup: " . $db->getConnection()->error);
        echo json_encode(['success' => false, 'message' => 'Failed to create group']);
    }
}

function updateGroup($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $groupId = intval($data['group_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category = trim($data['category'] ?? '');

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    // Check if user is creator or admin
    $checkSql = "SELECT creator_id FROM groups WHERE id = ?";
    $group = $db->query($checkSql, [$groupId]);

    if (!$group || empty($group)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        return;
    }

    $isCreator = ($group[0]['creator_id'] == $userId);

    if (!$isCreator) {
        // Check if user is admin/moderator
        $memberSql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
        $member = $db->query($memberSql, [$groupId, $userId]);

        if (!$member || empty($member) || !in_array($member[0]['role'], ['admin', 'moderator'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
    }

    // Handle image upload if provided
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/groups/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Delete old image if exists
        $oldImageSql = "SELECT image_url FROM groups WHERE id = ?";
        $oldImage = $db->query($oldImageSql, [$groupId]);
        if ($oldImage && !empty($oldImage) && $oldImage[0]['image_url']) {
            $oldPath = '../' . $oldImage[0]['image_url'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imageUrl = 'assets/uploads/groups/' . $fileName;
        }
    }

    $updateFields = [];
    $params = [];

    if (!empty($name)) {
        $updateFields[] = "name = ?";
        $params[] = $name;
    }
    if (!empty($description)) {
        $updateFields[] = "description = ?";
        $params[] = $description;
    }
    if (!empty($category)) {
        $updateFields[] = "category = ?";
        $params[] = $category;
    }
    if ($imageUrl) {
        $updateFields[] = "image_url = ?";
        $params[] = $imageUrl;
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }

    $params[] = $groupId;
    $sql = "UPDATE groups SET " . implode(', ', $updateFields) . " WHERE id = ?";

    $result = $db->query($sql, $params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Group updated successfully']);
    } else {
        error_log("Database error in updateGroup: " . $db->getConnection()->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update group']);
    }
}

function deleteGroup($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $groupId = intval($data['group_id'] ?? 0);

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    // Check if user is creator
    $checkSql = "SELECT creator_id, image_url FROM groups WHERE id = ?";
    $group = $db->query($checkSql, [$groupId]);

    if (!$group || empty($group)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        return;
    }

    if ($group[0]['creator_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Only the creator can delete the group']);
        return;
    }

    // Delete image if exists
    if ($group[0]['image_url']) {
        $imagePath = '../' . $group[0]['image_url'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $sql = "DELETE FROM groups WHERE id = ?";
    $result = $db->query($sql, [$groupId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } else {
        error_log("Database error in deleteGroup: " . $db->getConnection()->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete group']);
    }
}

function joinGroup($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $groupId = intval($data['group_id'] ?? 0);

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    // Check if group exists and is approved
    $groupSql = "SELECT id, is_approved FROM groups WHERE id = ?";
    $group = $db->query($groupSql, [$groupId]);

    if (!$group || empty($group)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        return;
    }

    if (!$group[0]['is_approved']) {
        echo json_encode(['success' => false, 'message' => 'Group is not approved yet']);
        return;
    }

    // Check if already a member
    $checkSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
    $existing = $db->query($checkSql, [$groupId, $userId]);

    if ($existing && !empty($existing)) {
        echo json_encode(['success' => false, 'message' => 'Already a member of this group']);
        return;
    }

    $sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())";
    $result = $db->query($sql, [$groupId, $userId]);

    if ($result) {
        // Update members count
        $updateCountSql = "UPDATE groups SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) WHERE id = ?";
        $db->query($updateCountSql, [$groupId, $groupId]);

        echo json_encode(['success' => true, 'message' => 'Joined group successfully']);
    } else {
        error_log("Database error in joinGroup: " . $db->getConnection()->error);
        echo json_encode(['success' => false, 'message' => 'Failed to join group']);
    }
}

function leaveGroup($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $groupId = intval($data['group_id'] ?? 0);

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    // Check if user is creator
    $checkSql = "SELECT creator_id FROM groups WHERE id = ?";
    $group = $db->query($checkSql, [$groupId]);

    if (!$group || empty($group)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        return;
    }

    if ($group[0]['creator_id'] == $userId) {
        echo json_encode(['success' => false, 'message' => 'Creator cannot leave the group. Delete the group instead.']);
        return;
    }

    $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
    $result = $db->query($sql, [$groupId, $userId]);

    if ($result) {
        // Update members count
        $updateCountSql = "UPDATE groups SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) WHERE id = ?";
        $db->query($updateCountSql, [$groupId, $groupId]);

        echo json_encode(['success' => true, 'message' => 'Left group successfully']);
    } else {
        error_log("Database error in leaveGroup: " . $db->getConnection()->error);
        echo json_encode(['success' => false, 'message' => 'Failed to leave group']);
    }
}

function checkMembership($db)
{
    $userId = $_SESSION['user_id'];
    $groupId = intval($_GET['group_id'] ?? 0);

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    $sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
    $member = $db->query($sql, [$groupId, $userId]);

    echo json_encode([
        'success' => true,
        'is_member' => ($member && !empty($member)),
        'role' => $member && !empty($member) ? $member[0]['role'] : null
    ]);
}

function getGroupMessages($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $groupId = intval($_GET['group_id'] ?? 0);

        if (!$groupId) {
            echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
            return;
        }

        // Check if user is a member
        $checkSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
        $isMember = $db->query($checkSql, [$groupId, $userId]);

        if (!$isMember || empty($isMember)) {
            echo json_encode(['success' => false, 'message' => 'You must be a member to view messages']);
            return;
        }

        // Get messages
        $sql = "SELECT gm.id, gm.group_id, gm.user_id, gm.message, gm.image_url, gm.video_url, gm.created_at,
                       u.full_name as sender_name, u.profile_image,
                       CASE WHEN gm.user_id = ? THEN 1 ELSE 0 END as is_sent
                FROM group_messages gm
                INNER JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ?
                ORDER BY gm.created_at ASC";

        $messages = $db->query($sql, [$userId, $groupId]);

        if ($messages === false) {
            throw new Exception("Database query failed: " . $db->getConnection()->error);
        }

        echo json_encode([
            'success' => true,
            'messages' => $messages ?: []
        ]);
    } catch (Exception $e) {
        error_log("Error in getGroupMessages: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load messages: ' . $e->getMessage()
        ]);
    }
}

function sendGroupMessage($db)
{
    try {
        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $groupId = intval($data['group_id'] ?? 0);
        $message = trim($data['message'] ?? '');

        if (!$groupId || !$message) {
            echo json_encode(['success' => false, 'message' => 'Group ID and message are required']);
            return;
        }

        // Check if user is a member
        $checkSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
        $isMember = $db->query($checkSql, [$groupId, $userId]);

        if (!$isMember || empty($isMember)) {
            echo json_encode(['success' => false, 'message' => 'You must be a member to send messages']);
            return;
        }

        // Check if group exists and is approved
        $groupSql = "SELECT id FROM groups WHERE id = ? AND is_approved = 1";
        $group = $db->query($groupSql, [$groupId]);

        if (!$group || empty($group)) {
            echo json_encode(['success' => false, 'message' => 'Group not found or not approved']);
            return;
        }

        $sql = "INSERT INTO group_messages (group_id, user_id, message, created_at) 
                VALUES (?, ?, ?, NOW())";

        $result = $db->query($sql, [$groupId, $userId, $message]);

        if ($result === false) {
            throw new Exception("Database error: " . $db->getConnection()->error);
        }

        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } catch (Exception $e) {
        error_log("Error in sendGroupMessage: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message: ' . $e->getMessage()
        ]);
    }
}

function addMembersToGroup($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $groupId = intval($data['group_id'] ?? 0);
    $memberIds = $data['member_ids'] ?? [];

    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        return;
    }

    if (empty($memberIds) || !is_array($memberIds)) {
        echo json_encode(['success' => false, 'message' => 'No members selected']);
        return;
    }

    // Check if group exists and is approved
    $groupSql = "SELECT id, name FROM groups WHERE id = ? AND is_approved = 1";
    $group = $db->query($groupSql, [$groupId]);

    if (!$group || empty($group)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        return;
    }

    // Check if user is admin or moderator of the group
    $memberCheckSql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
    $memberCheck = $db->query($memberCheckSql, [$groupId, $userId]);

    if (!$memberCheck || empty($memberCheck)) {
        echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
        return;
    }

    $userRole = $memberCheck[0]['role'];
    if ($userRole !== 'admin' && $userRole !== 'moderator') {
        echo json_encode(['success' => false, 'message' => 'Only group admins and moderators can add members']);
        return;
    }

    // Add members
    $addedCount = 0;
    $skippedCount = 0;
    $errors = [];

    foreach ($memberIds as $memberId) {
        $memberId = intval($memberId);

        if ($memberId <= 0) {
            continue;
        }

        // Check if user exists and is approved
        $userCheckSql = "SELECT id, full_name FROM users WHERE id = ? AND is_approved = 1";
        $userCheck = $db->query($userCheckSql, [$memberId]);

        if (!$userCheck || empty($userCheck)) {
            $errors[] = "User ID {$memberId} not found or not approved";
            continue;
        }

        // Check if already a member
        $existingMemberSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
        $existingMember = $db->query($existingMemberSql, [$groupId, $memberId]);

        if ($existingMember && !empty($existingMember)) {
            $skippedCount++;
            continue;
        }

        // Add member
        $insertSql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())";
        $result = $db->query($insertSql, [$groupId, $memberId]);

        if ($result) {
            $addedCount++;
        } else {
            $errors[] = "Failed to add user: " . $userCheck[0]['full_name'];
        }
    }

    // Update member count
    $updateCountSql = "UPDATE groups SET members_count = (SELECT COUNT(*) FROM group_members WHERE group_id = ?) WHERE id = ?";
    $db->query($updateCountSql, [$groupId, $groupId]);

    $message = '';
    if ($addedCount > 0) {
        $message = "{$addedCount} member(s) added successfully";
    }
    if ($skippedCount > 0) {
        $message .= ($message ? '. ' : '') . "{$skippedCount} already member(s)";
    }
    if (!empty($errors)) {
        $message .= ($message ? '. ' : '') . 'Some errors: ' . implode(', ', $errors);
    }

    echo json_encode([
        'success' => $addedCount > 0,
        'message' => $message ?: 'No members were added',
        'added_count' => $addedCount,
        'skipped_count' => $skippedCount
    ]);
}
