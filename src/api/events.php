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
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id AND status = 'going') as attendees_count
            FROM events e
            WHERE e.is_approved = 1 AND e.event_date >= NOW()
            ORDER BY e.event_date ASC";

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
    $eventDate = $data['event_date'] ?? '';
    $location = trim($data['location'] ?? '');

    if (!$title || !$description || !$eventDate || !$location) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        return;
    }

    $sql = "INSERT INTO events (user_id, title, description, event_date, location, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())";

    $result = $db->query($sql, [$userId, $title, $description, $eventDate, $location]);

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

    if ($existing) {
        $sql = "UPDATE event_attendees SET status = ? WHERE event_id = ? AND user_id = ?";
        $result = $db->query($sql, [$status, $eventId, $userId]);
    } else {
        $sql = "INSERT INTO event_attendees (event_id, user_id, status, created_at) VALUES (?, ?, ?, NOW())";
        $result = $db->query($sql, [$eventId, $userId, $status]);
    }

    echo json_encode([
        'success' => $result ? true : false,
        'message' => $result ? 'RSVP updated' : 'Failed to RSVP'
    ]);
}
