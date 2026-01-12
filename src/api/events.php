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
        getAllEvents($db);
        break;
    case 'get_upcoming':
        getUpcomingEvents($db);
        break;
    case 'create':
        createEvent($db);
        break;
    case 'rsvp':
        rsvpEvent($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getAllEvents($db)
{
    // Get all approved events (matches getUpcomingEvents but without limit)
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendees_count
            FROM events e
            WHERE e.is_approved = 1
            ORDER BY e.event_date ASC, e.event_time ASC";

    $events = $db->query($sql);

    if ($events === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Database query failed'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'events' => $events ?: []
    ]);
}

function getUpcomingEvents($db)
{
    // Get upcoming events (limit to 5 for sidebar)
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendees_count
            FROM events e
            WHERE e.is_approved = 1 AND (e.event_date > CURDATE() OR (e.event_date = CURDATE() AND e.event_time >= TIME(NOW())))
            ORDER BY e.event_date ASC, e.event_time ASC
            LIMIT 5";

    $events = $db->query($sql);

    echo json_encode([
        'success' => true,
        'events' => $events ?: []
    ]);
}

function createEvent($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $eventDateTime = $data['event_date'] ?? '';
    $location = trim($data['location'] ?? '');

    if (!$title || !$description || !$eventDateTime || !$location) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        return;
    }

    // Parse datetime-local format (YYYY-MM-DDTHH:mm) into separate date and time
    $dateTimeParts = explode('T', $eventDateTime);
    $eventDate = $dateTimeParts[0] ?? '';
    $eventTime = isset($dateTimeParts[1]) ? $dateTimeParts[1] . ':00' : '00:00:00';

    if (!$eventDate) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }

    $sql = "INSERT INTO events (user_id, title, description, event_date, event_time, location, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$userId, $title, $description, $eventDate, $eventTime, $location]);

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'Event created, waiting for approval' : 'Failed to create event'
    ]);
}

function rsvpEvent($db)
{
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $eventId = intval($data['event_id'] ?? 0);
    $status = $data['status'] ?? 'going';

    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Invalid event']);
        return;
    }

    // Check if already registered
    $checkSql = "SELECT id FROM event_attendees WHERE event_id = ? AND user_id = ?";
    $existing = $db->query($checkSql, [$eventId, $userId]);

    if ($existing && !empty($existing)) {
        // User already registered - remove them (toggle off)
        $sql = "DELETE FROM event_attendees WHERE event_id = ? AND user_id = ?";
        $result = $db->query($sql, [$eventId, $userId]);
        $message = 'RSVP removed';
        $isGoing = false;
    } else {
        // User not registered - add them
        $sql = "INSERT INTO event_attendees (event_id, user_id, registered_at) VALUES (?, ?, NOW())";
        $result = $db->query($sql, [$eventId, $userId]);
        $message = $status === 'going' ? 'You\'re going to this event!' : 'Marked as interested!';
        $isGoing = true;
    }

    if ($result !== false) {
        // Get updated attendee count
        $countSql = "SELECT COUNT(*) as count FROM event_attendees WHERE event_id = ?";
        $countResult = $db->query($countSql, [$eventId]);
        $attendeesCount = $countResult && !empty($countResult) ? $countResult[0]['count'] : 0;

        echo json_encode([
            'success' => true,
            'message' => $message,
            'is_going' => $isGoing,
            'attendees_count' => $attendeesCount
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update RSVP'
        ]);
    }
}
