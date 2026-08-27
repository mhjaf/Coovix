<?php
header('Content-Type: application/json');
// CORS: Only allow same-origin requests or configure your domain
// TODO: Replace with your production domain before going live
$allowedOrigins = ['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set timezone to Baghdad
date_default_timezone_set('Asia/Baghdad');

// Include database configuration
require_once __DIR__ . '/db_config.php';

$conn = createBarDatabaseConnection();

if (!$conn) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_barbers':
        getBarbers($conn);
        break;
    case 'get_available_times':
        getAvailableTimes($conn);
        break;
    case 'get_barber_schedule':
        getBarberSchedule($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getBarbers($conn) {
    $result = $conn->query("SELECT id, name FROM barbers WHERE status = 'active' ORDER BY name ASC");
    $barbers = [];
    while ($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }
    echo json_encode(['barbers' => $barbers]);
}

function getBarberSchedule($conn) {
    $barberId = intval($_GET['barber_id'] ?? 0);

    if (!$barberId) {
        echo json_encode(['error' => 'Barber ID required']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM barber_schedules WHERE barber_id = ? ORDER BY day_of_week ASC");
    $stmt->bind_param("i", $barberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $schedule[$row['day_of_week']] = $row;
    }
    $stmt->close();
    echo json_encode(['schedule' => $schedule]);
}

function getAvailableTimes($conn) {
    $barberId = intval($_GET['barber_id'] ?? 0);
    $date = $_GET['date'] ?? '';

    if (!$barberId || !$date) {
        echo json_encode(['error' => 'Barber ID and date required']);
        return;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format']);
        return;
    }

    // Get day of week (0 = Sunday, 6 = Saturday)
    $dayOfWeek = date('w', strtotime($date));

    // Get barber's schedule for this day
    $stmt = $conn->prepare("SELECT * FROM barber_schedules WHERE barber_id = ? AND day_of_week = ?");
    $stmt->bind_param("ii", $barberId, $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();

    if (!$schedule || $schedule['is_day_off']) {
        echo json_encode(['times' => [], 'message' => 'Barber is not available on this day']);
        return;
    }

    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    
    // Parse break_time (format: "11:00-12:00" or "12:00-13:00")
    $breakStart = null;
    $breakEnd = null;
    if (!empty($schedule['break_time'])) {
        $breakParts = explode('-', $schedule['break_time']);
        if (count($breakParts) == 2) {
            $breakStart = strtotime($breakParts[0]);
            $breakEnd = strtotime($breakParts[1]);
        }
    }

    // Get already booked times for this barber on this date
    $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE barber_id = ? AND booking_date = ? AND status != 'cancelled'");
    $stmt->bind_param("is", $barberId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = date('H:i', strtotime($row['booking_time']));
    }
    $stmt->close();

    // Check if selected date is today
    $isToday = ($date === date('Y-m-d'));
    $currentTime = time();

    // Generate available time slots (30-minute intervals)
    $availableTimes = [];
    $current = $startTime;

    while ($current < $endTime) {
        $timeStr = date('H:i', $current);
        $time12 = date('g:i A', $current);

        // Check if in break time
        $inBreak = false;
        if ($breakStart && $breakEnd) {
            if ($current >= $breakStart && $current < $breakEnd) {
                $inBreak = true;
            }
        }

        // Check if already booked
        $isBooked = in_array($timeStr, $bookedTimes);

        // Check if time has passed (only for today)
        $isPast = false;
        if ($isToday) {
            $slotDateTime = strtotime($date . ' ' . $timeStr);
            if ($slotDateTime <= $currentTime) {
                $isPast = true;
            }
        }

        if (!$inBreak && !$isBooked && !$isPast) {
            $availableTimes[] = [
                'value' => $timeStr,
                'label' => $time12
            ];
        }

        $current += 30 * 60; // Add 30 minutes
    }

    echo json_encode([
        'times' => $availableTimes,
        'schedule' => [
            'start' => date('g:i A', $startTime),
            'end' => date('g:i A', $endTime),
            'break_start' => $breakStart ? date('g:i A', $breakStart) : null,
            'break_end' => $breakEnd ? date('g:i A', $breakEnd) : null
        ]
    ]);
}

$conn->close();
?>
