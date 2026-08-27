<?php
header('Content-Type: application/json');
// CORS: Only allow same-origin requests or configure your domain
// TODO: Replace with your production domain before going live
$allowedOrigins = ['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set timezone to Baghdad
date_default_timezone_set('Asia/Baghdad');

// Include database configuration
require_once __DIR__ . '/db_config.php';

$conn = createBarDatabaseConnection();

if (!$conn) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Simple rate limiting for booking (max 10 bookings per IP per hour)
session_start();
$rateLimitKey = 'booking_rate_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'first_attempt' => time()];
}
$rateData = $_SESSION[$rateLimitKey];
if (time() - $rateData['first_attempt'] > 3600) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'first_attempt' => time()];
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limit
    if ($_SESSION[$rateLimitKey]['count'] >= 10) {
        echo json_encode(['success' => false, 'message' => 'Too many booking attempts. Please try again later.']);
        exit();
    }
    $_SESSION[$rateLimitKey]['count']++;

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $barber_id = intval($_POST['barber'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($name) || empty($phone) || empty($service) || empty($barber_id) || empty($date) || empty($time)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }
    
    // Convert time format from "10:00 AM" to "10:00:00" if needed
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $matches)) {
        $hour = intval($matches[1]);
        $minute = $matches[2];
        $period = strtoupper($matches[3]);
        
        // Convert to 24-hour format
        if ($period === 'PM' && $hour !== 12) {
            $hour += 12;
        } elseif ($period === 'AM' && $hour === 12) {
            $hour = 0;
        }
        
        $time = sprintf('%02d:%02d:00', $hour, $minute);
    } elseif (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        // If format is "10:00", add seconds
        $time .= ':00';
    }

    // Insert booking
    $sql = "INSERT INTO bookings (customer_name, phone, service, barber_id, booking_date, booking_time, notes, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisss", $name, $phone, $service, $barber_id, $date, $time, $notes);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'We will contact you shortly to confirm your appointment!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save booking. Please try again.'
        ]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
