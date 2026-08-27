<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Use central database config
require_once __DIR__ . '/../admin/config/database.php';

$allowedOrigin = defined('SITE_URL') && SITE_URL ? SITE_URL : '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? '';

    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Valid date is required']);
        exit;
    }

    $conn = getConnection(true);
    if (!$conn) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get day of week (0 = Sunday, 6 = Saturday)
    $dayOfWeek = date('w', strtotime($date));

    // Check if this day is a working day
    $stmt = $conn->prepare("SELECT is_working, day_name, open_time, close_time FROM work_hours WHERE day_of_week = ?");
    $stmt->bind_param("i", $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'is_working' => (bool)$row['is_working'],
            'day_name' => $row['day_name'],
            'open_time' => $row['open_time'],
            'close_time' => $row['close_time']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'is_working' => true,
            'day_name' => date('l', strtotime($date)),
            'open_time' => '08:00:00',
            'close_time' => '20:00:00'
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
