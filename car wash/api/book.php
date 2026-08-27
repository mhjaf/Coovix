<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Use central database config
require_once __DIR__ . '/../admin/config/database.php';

// Restrict CORS to your domain (update SITE_URL in config/database.php)
$allowedOrigin = defined('SITE_URL') && SITE_URL ? SITE_URL : '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Simple rate limiting using session
function checkRateLimit($action, $maxPerMinute = 10) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'rate_limit_' . $action;
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // Remove entries older than 60 seconds
    $_SESSION[$key] = array_filter($_SESSION[$key], function($t) use ($now) {
        return ($now - $t) < 60;
    });

    if (count($_SESSION[$key]) >= $maxPerMinute) {
        return false;
    }

    $_SESSION[$key][] = $now;
    return true;
}

// Handle GET request - Get services and car types
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getConnection(true);

    if (!$conn) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get action parameter
    $action = $_GET['action'] ?? 'services';

    if ($action === 'car_types') {
        $result = $conn->query("SELECT id, name, name_ku, name_ar FROM car_types WHERE status = 'active' ORDER BY name");

        $carTypes = [];
        while ($row = $result->fetch_assoc()) {
            $carTypes[] = $row;
        }

        echo json_encode(['success' => true, 'car_types' => $carTypes]);
    } elseif ($action === 'wash_types') {
        $result = $conn->query("SELECT id, name, name_ku, name_ar FROM wash_types WHERE status = 'active' ORDER BY name");

        $washTypes = [];
        while ($row = $result->fetch_assoc()) {
            $washTypes[] = $row;
        }

        echo json_encode(['success' => true, 'wash_types' => $washTypes]);
    } elseif ($action === 'service_prices') {
        $carTypeId = (int)($_GET['car_type_id'] ?? 0);
        $washTypeId = isset($_GET['wash_type_id']) ? (int)$_GET['wash_type_id'] : null;

        if ($carTypeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Car type ID required']);
            $conn->close();
            exit;
        }

        if ($washTypeId !== null) {
            $query = "
                SELECT s.id, s.name, s.name_ku, s.name_ar, s.category, s.car_type,
                       s.description, s.description_ku, s.description_ar,
                       wt.name as wash_type, COALESCE(sp.price, s.price) as price
                FROM services s
                CROSS JOIN wash_types wt
                LEFT JOIN service_pricing sp ON s.id = sp.service_id AND sp.car_type_id = ? AND sp.wash_type_id = ?
                WHERE s.status = 'active' AND wt.id = ?
                ORDER BY s.category, s.name
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $carTypeId, $washTypeId, $washTypeId);
        } else {
            $query = "
                SELECT s.id, s.name, s.name_ku, s.name_ar, s.category, s.car_type,
                       s.description, s.description_ku, s.description_ar,
                       COALESCE(sp.price, s.price) as price
                FROM services s
                LEFT JOIN service_pricing sp ON s.id = sp.service_id AND sp.car_type_id = ?
                WHERE s.status = 'active'
                ORDER BY s.category, s.name
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $carTypeId);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }

        echo json_encode(['success' => true, 'services' => $services]);
        $stmt->close();
    } elseif ($action === 'categories') {
        $result = $conn->query("SELECT id, name, name_ku, name_ar FROM categories WHERE status = 'active' ORDER BY name");

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        echo json_encode(['success' => true, 'categories' => $categories]);
    } else {
        $result = $conn->query("SELECT id, name, name_ku, name_ar, price, category, car_type, description, description_ku, description_ar FROM services WHERE status = 'active' ORDER BY category, name");

        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }

        echo json_encode(['success' => true, 'services' => $services]);
    }

    $conn->close();
    exit;
}

// Handle POST request - Create booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limit: max 5 bookings per minute per session
    if (!checkRateLimit('booking', 5)) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait a moment and try again.']);
        exit;
    }

    $conn = getConnection(true);

    if (!$conn) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $name = sanitize($data['name'] ?? '');
    $phone = sanitize($data['phone'] ?? '');
    $category = sanitize($data['category'] ?? '');
    $carType = sanitize($data['car_type'] ?? '');
    $washType = sanitize($data['wash_type'] ?? '');
    $serviceId = (int)($data['service_id'] ?? 0);
    $date = sanitize($data['date'] ?? '');
    $time = sanitize($data['time'] ?? '');
    $note = sanitize($data['note'] ?? '');

    // Validate required fields
    if (empty($name) || empty($category) || empty($carType) || $serviceId <= 0 || empty($date) || empty($time)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        exit;
    }

    // Validate name length
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Name must be between 2 and 100 characters']);
        exit;
    }

    // Check if service exists
    $stmt = $conn->prepare("SELECT id, price FROM services WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $serviceResult = $stmt->get_result();
    if ($serviceResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid service selected']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $serviceRow = $serviceResult->fetch_assoc();
    $basePrice = $serviceRow['price'];
    $stmt->close();

    // Get car type ID from name
    $carTypeId = null;
    $stmt = $conn->prepare("SELECT id FROM car_types WHERE name = ? AND status = 'active'");
    $stmt->bind_param("s", $carType);
    $stmt->execute();
    $ctResult = $stmt->get_result();
    if ($ctRow = $ctResult->fetch_assoc()) {
        $carTypeId = $ctRow['id'];
    }
    $stmt->close();

    // Get wash type ID from name (if provided)
    $washTypeId = null;
    if (!empty($washType)) {
        $stmt = $conn->prepare("SELECT id FROM wash_types WHERE name = ? AND status = 'active'");
        $stmt->bind_param("s", $washType);
        $stmt->execute();
        $wtResult = $stmt->get_result();
        if ($wtRow = $wtResult->fetch_assoc()) {
            $washTypeId = $wtRow['id'];
        }
        $stmt->close();
    }

    // Get actual price
    $pricePaid = $basePrice;
    if ($carTypeId) {
        if ($washTypeId) {
            $stmt = $conn->prepare("SELECT price FROM service_pricing WHERE service_id = ? AND car_type_id = ? AND wash_type_id = ?");
            $stmt->bind_param("iii", $serviceId, $carTypeId, $washTypeId);
            $stmt->execute();
            $priceResult = $stmt->get_result();
            if ($priceRow = $priceResult->fetch_assoc()) {
                $pricePaid = $priceRow['price'];
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT price FROM service_pricing WHERE service_id = ? AND car_type_id = ?");
            $stmt->bind_param("ii", $serviceId, $carTypeId);
            $stmt->execute();
            $priceResult = $stmt->get_result();
            if ($priceRow = $priceResult->fetch_assoc()) {
                $pricePaid = $priceRow['price'];
            }
            $stmt->close();
        }
    }

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (customer_name, phone, category, car_type, service_id, booking_date, booking_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssisss", $name, $phone, $category, $carType, $serviceId, $date, $time, $note);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully! We will contact you shortly.',
            'booking_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating booking. Please try again.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
