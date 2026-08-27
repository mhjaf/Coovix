<?php
require_once 'config.php';
requireLogin();

// Check permission
if (!hasPermission('can_bookings')) {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Get services for dropdown
$servicesResult = $conn->query("SELECT id, name, price, duration FROM services WHERE status = 'active' ORDER BY name ASC");
$servicesList = [];
while ($row = $servicesResult->fetch_assoc()) {
    $servicesList[] = $row;
}

// Get barbers for dropdown
$barbersResult = $conn->query("SELECT id, name FROM barbers WHERE status = 'active' ORDER BY name ASC");
$barbersList = [];
while ($row = $barbersResult->fetch_assoc()) {
    $barbersList[] = $row;
}

// Handle Status Update (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && empty($message)) {
    $id = intval($_POST['booking_id']);
    $status = $_POST['new_status'];
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

    if (in_array($status, $validStatuses)) {
        $sql = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $message = t('status_updated_to') . " " . t($status);
            $messageType = "success";
        }
        $stmt->close();
    }
}

// Handle Add Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reservation']) && empty($message)) {
    $customer_name = sanitize($conn, $_POST['customer_name']);
    $service = sanitize($conn, $_POST['service']);
    $price = floatval($_POST['price'] ?? 0);
    $booking_date = sanitize($conn, $_POST['booking_date']);
    $booking_time = sanitize($conn, $_POST['booking_time']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');

    if (!empty($customer_name) && !empty($service) && !empty($booking_date) && !empty($booking_time)) {
        $sql = "INSERT INTO bookings (customer_name, phone, service, price, barber_id, booking_date, booking_time, notes, status) VALUES (?, '', ?, ?, NULL, ?, ?, ?, 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsss", $customer_name, $service, $price, $booking_date, $booking_time, $notes);

        if ($stmt->execute()) {
            $message = t('reservation_added');
            $messageType = "success";
        } else {
            $message = t('error');
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = t('fill_required_fields');
        $messageType = "error";
    }
}

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';

// Handle Edit Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reservation']) && empty($message)) {
    $id = intval($_POST['id']);
    $customer_name = sanitize($conn, $_POST['customer_name']);
    $service = sanitize($conn, $_POST['service']);
    $price = floatval($_POST['price'] ?? 0);
    $booking_date = sanitize($conn, $_POST['booking_date']);
    $booking_time = sanitize($conn, $_POST['booking_time']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');

    if (!empty($customer_name) && !empty($service) && !empty($booking_date) && !empty($booking_time)) {
        $sql = "UPDATE bookings SET customer_name=?, service=?, price=?, booking_date=?, booking_time=?, notes=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsssi", $customer_name, $service, $price, $booking_date, $booking_time, $notes, $id);

        if ($stmt->execute()) {
            $message = t('reservation_updated');
            $messageType = "success";
        } else {
            $message = t('error');
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = t('fill_required_fields');
        $messageType = "error";
    }
}

// Get booking for edit
$editBooking = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT b.*, s.price as service_price FROM bookings b LEFT JOIN services s ON b.service = s.name WHERE b.id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editBooking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}


// Filter by status, date, and user
$statusFilter = isset($_GET['filter']) && in_array($_GET['filter'], ['pending', 'confirmed', 'completed', 'cancelled']) ? $_GET['filter'] : '';
$dateFilter = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';
$monthFilter = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : '';
$filterUserId = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;

// Check if any filter parameters exist in the URL
$hasFilterParams = isset($_GET['filter_user']) || isset($_GET['month']) || isset($_GET['date']) || isset($_GET['filter']);

// Default to today if no filters are set
if (!$hasFilterParams) {
    $dateFilter = date('Y-m-d');
}

// Build parameterized filter conditions
$bookingFilterConditions = [];
$bookingFilterTypes = "";
$bookingFilterValues = [];
$currentUserId = $_SESSION['admin_id'];

// Build user filter - if not admin (can_users), only show bookings for their linked barbers
if (!hasPermission('can_users')) {
    $bookingFilterConditions[] = "br.user_id = ?";
    $bookingFilterTypes .= "i";
    $bookingFilterValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $bookingFilterConditions[] = "br.user_id = ?";
    $bookingFilterTypes .= "i";
    $bookingFilterValues[] = $filterUserId;
}

// Get all users for filter dropdown (admins only)
$allUsers = [];
if (hasPermission('can_users')) {
    $usersResult = $conn->query("SELECT id, username FROM admin_users ORDER BY username ASC");
    while ($row = $usersResult->fetch_assoc()) {
        $allUsers[] = $row;
    }
}

// Add status filter
if ($statusFilter) {
    $bookingFilterConditions[] = "b.status = ?";
    $bookingFilterTypes .= "s";
    $bookingFilterValues[] = $statusFilter;
}

// Month filter takes priority over date filter
if ($monthFilter) {
    $bookingFilterConditions[] = "DATE_FORMAT(b.booking_date, '%Y-%m') = ?";
    $bookingFilterTypes .= "s";
    $bookingFilterValues[] = $monthFilter;
} elseif ($dateFilter) {
    $bookingFilterConditions[] = "b.booking_date = ?";
    $bookingFilterTypes .= "s";
    $bookingFilterValues[] = $dateFilter;
}

// Build WHERE clause
$whereClause = !empty($bookingFilterConditions) ? "WHERE " . implode(" AND ", $bookingFilterConditions) : "";

// Get bookings with service price using prepared statement
$bookingsQuery = "SELECT b.*, br.name as barber_name, s.price as service_price FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id LEFT JOIN services s ON b.service = s.name " . ($whereClause ? $whereClause : "WHERE 1=1") . " ORDER BY b.booking_date ASC, b.booking_time ASC";

if (!empty($bookingFilterValues)) {
    $stmt = $conn->prepare($bookingsQuery);
    $stmt->bind_param($bookingFilterTypes, ...$bookingFilterValues);
    $stmt->execute();
    $bookings = $stmt->get_result();
} else {
    $bookings = $conn->query($bookingsQuery);
}

// Build count conditions (without status filter for getting all counts)
$countConditions = [];
$countTypes = "";
$countValues = [];

if (!hasPermission('can_users')) {
    $countConditions[] = "br.user_id = ?";
    $countTypes .= "i";
    $countValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $countConditions[] = "br.user_id = ?";
    $countTypes .= "i";
    $countValues[] = $filterUserId;
}

if ($monthFilter) {
    $countConditions[] = "DATE_FORMAT(b.booking_date, '%Y-%m') = ?";
    $countTypes .= "s";
    $countValues[] = $monthFilter;
} elseif ($dateFilter) {
    $countConditions[] = "b.booking_date = ?";
    $countTypes .= "s";
    $countValues[] = $dateFilter;
}

$countBaseWhere = !empty($countConditions) ? "WHERE " . implode(" AND ", $countConditions) : "";

// Get counts using prepared statements
if (!empty($countValues)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id " . ($countBaseWhere ? $countBaseWhere : "WHERE 1=1"));
    $stmt->bind_param($countTypes, ...$countValues);
    $stmt->execute();
    $allCount = $stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id " . ($countBaseWhere ? $countBaseWhere . " AND" : "WHERE") . " b.status='pending'");
    $stmt->bind_param($countTypes, ...$countValues);
    $stmt->execute();
    $pendingCount = $stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id " . ($countBaseWhere ? $countBaseWhere . " AND" : "WHERE") . " b.status='confirmed'");
    $stmt->bind_param($countTypes, ...$countValues);
    $stmt->execute();
    $confirmedCount = $stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id " . ($countBaseWhere ? $countBaseWhere . " AND" : "WHERE") . " b.status='completed'");
    $stmt->bind_param($countTypes, ...$countValues);
    $stmt->execute();
    $completedCount = $stmt->get_result()->fetch_assoc()['c'];
} else {
    $allCount = $conn->query("SELECT COUNT(*) as c FROM bookings b")->fetch_assoc()['c'];
    $pendingCount = $conn->query("SELECT COUNT(*) as c FROM bookings b WHERE b.status='pending'")->fetch_assoc()['c'];
    $confirmedCount = $conn->query("SELECT COUNT(*) as c FROM bookings b WHERE b.status='confirmed'")->fetch_assoc()['c'];
    $completedCount = $conn->query("SELECT COUNT(*) as c FROM bookings b WHERE b.status='completed'")->fetch_assoc()['c'];
}

// Calculate total revenue
$revenueQuery = "SELECT SUM(CASE WHEN b.price > 0 THEN b.price ELSE COALESCE(s.price, 0) END) as total FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id LEFT JOIN services s ON b.service = s.name " . ($whereClause ? $whereClause : "");

if (!empty($bookingFilterValues)) {
    $stmt = $conn->prepare($revenueQuery);
    $stmt->bind_param($bookingFilterTypes, ...$bookingFilterValues);
    $stmt->execute();
    $totalRevenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    $totalRevenue = $conn->query($revenueQuery)->fetch_assoc()['total'] ?? 0;
}

// Get completed customers count based on current filter (date, month, or today)
$customerConditions = ["b.status = 'completed'"];
$customerTypes = "";
$customerValues = [];

// Add user filter for customers query
if (!hasPermission('can_users')) {
    $customerConditions[] = "br.user_id = ?";
    $customerTypes .= "i";
    $customerValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $customerConditions[] = "br.user_id = ?";
    $customerTypes .= "i";
    $customerValues[] = $filterUserId;
}

if ($dateFilter) {
    $customerConditions[] = "DATE(b.booking_date) = ?";
    $customerTypes .= "s";
    $customerValues[] = $dateFilter;
} elseif ($monthFilter) {
    $customerConditions[] = "DATE_FORMAT(b.booking_date, '%Y-%m') = ?";
    $customerTypes .= "s";
    $customerValues[] = $monthFilter;
} else {
    $customerConditions[] = "DATE(b.booking_date) = CURDATE()";
}

$todayCustomersQuery = "SELECT COUNT(*) as count FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE " . implode(" AND ", $customerConditions);
if (!empty($customerValues)) {
    $stmt = $conn->prepare($todayCustomersQuery);
    $stmt->bind_param($customerTypes, ...$customerValues);
    $stmt->execute();
    $todayCustomers = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} else {
    $todayCustomers = $conn->query($todayCustomersQuery)->fetch_assoc()['count'] ?? 0;
}

// Get available months that have booking data
$monthConditions = [];
$monthTypes = "";
$monthValues = [];

if (!hasPermission('can_users')) {
    $monthConditions[] = "br.user_id = ?";
    $monthTypes .= "i";
    $monthValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $monthConditions[] = "br.user_id = ?";
    $monthTypes .= "i";
    $monthValues[] = $filterUserId;
}

$availableMonthsQuery = "SELECT DISTINCT DATE_FORMAT(b.booking_date, '%Y-%m') as month_value, DATE_FORMAT(b.booking_date, '%Y-%m-01') as month_date FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id" . (!empty($monthConditions) ? " WHERE " . implode(" AND ", $monthConditions) : "") . " ORDER BY month_value DESC";

if (!empty($monthValues)) {
    $stmt = $conn->prepare($availableMonthsQuery);
    $stmt->bind_param($monthTypes, ...$monthValues);
    $stmt->execute();
    $availableMonthsResult = $stmt->get_result();
    $stmt->close();
} else {
    $availableMonthsResult = $conn->query($availableMonthsQuery);
}

$availableMonths = [];
while ($row = $availableMonthsResult->fetch_assoc()) {
    $availableMonths[] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('bookings'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .time-picker-wrapper {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .time-picker-wrapper input[type="time"] {
            font-size: 1.2rem;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-weight: 600;
            color: #FF6B35;
            background: #fff;
            transition: all 0.3s;
        }
        .time-picker-wrapper input[type="time"]:focus {
            border-color: #FF6B35;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
        }
        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .time-slot {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            background: #f8f9fa;
            color: #555;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .time-slot:hover {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
            transform: translateY(-2px);
        }
        .time-slot.active {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
        }
        
        /* Filter Badge */
        .filter-badge {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        .filter-badge i {
            margin-right: 5px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideDown 0.3s;
        }
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .modal-header h3 i {
            margin-right: 10px;
        }
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.2s;
        }
        .close:hover {
            transform: scale(1.2);
        }
        .modal-body {
            padding: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-group label i {
            color: #FF6B35;
            margin-right: 8px;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        .modal-footer {
            padding: 20px 25px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-cut"></i>
                    <span>Coovix Barber</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php if (hasPermission('can_dashboard')): ?>
                    <li>
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span><?php echo t('dashboard'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_bookings')): ?>
                    <li class="active">
                        <a href="bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            <span><?php echo t('bookings'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_products')): ?>
                    <li>
                        <a href="products.php">
                            <i class="fas fa-box"></i>
                            <span><?php echo t('products'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_expenses')): ?>
                    <li>
                        <a href="expenses.php">
                            <i class="fas fa-receipt"></i>
                            <span><?php echo t('expenses'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_schedule')): ?>
                    <li>
                        <a href="worktime.php">
                            <i class="fas fa-clock"></i>
                            <span><?php echo t('work_time'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_staff_status')): ?>
                    <li>
                        <a href="staff-status.php">
                            <i class="fas fa-user-clock"></i>
                            <span><?php echo t('staff_status'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_barbers')): ?>
                    <li>
                        <a href="barbers.php">
                            <i class="fas fa-user-tie"></i>
                            <span><?php echo t('barbers'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_users')): ?>
                    <li>
                        <a href="users.php">
                            <i class="fas fa-users-cog"></i>
                            <span><?php echo t('users'); ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="user-reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span><?php echo t('user_reports'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('can_settings')): ?>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span><?php echo t('settings'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="divider"></li>
                    <li>
                        <a href="../../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span><?php echo t('view_website'); ?></span>
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li class="lang-dropdown">
                        <button class="lang-dropdown-toggle" onclick="toggleLangDropdown(this)">
                            <span class="lang-toggle-left">
                                <i class="fas fa-globe"></i>
                                <span><?php echo t('language'); ?></span>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="lang-dropdown-menu">
                            <a href="?lang=en" class="<?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku" class="<?php echo getCurrentLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
                            <a href="?lang=ar" class="<?php echo getCurrentLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                        </div>
                    </li>
                    <li class="divider"></li>
                    <li class="logout-item">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span><?php echo t('logout'); ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo t('bookings'); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="openFilterModal()" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> <?php echo t('filter'); ?>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- New Reservation Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <a href="?action=add" class="btn btn-primary" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-plus"></i> <?php echo t('new_reservation'); ?>
                    </a>
                </div>
                <!-- Active Filters -->
                <?php if ($filterUserId || $monthFilter || $dateFilter): ?>
                <div class="filter-container" style="margin-bottom: 15px;">
                    <?php if ($filterUserId): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-user"></i> <?php 
                            $selectedUser = array_filter($allUsers, function($u) use ($filterUserId) { return $u['id'] == $filterUserId; });
                            echo !empty($selectedUser) ? reset($selectedUser)['username'] : 'User #' . $filterUserId;
                        ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($dateFilter): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-calendar-day"></i> <?php echo date('M d, Y', strtotime($dateFilter)); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($monthFilter): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('F Y', strtotime($monthFilter . '-01')); ?>
                    </span>
                    <?php endif; ?>
                    <a href="bookings.php<?php echo $statusFilter ? '?filter=' . $statusFilter : ''; ?>" class="btn btn-sm" style="font-size: 0.85rem; padding: 4px 10px; background: #dc3545; color: white; text-decoration: none; border-radius: 20px;">
                        <i class="fas fa-times"></i> <?php echo t('clear_all_filters'); ?>
                    </a>
                </div>
                <?php endif; ?>
                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
                <?php endif; ?>

                <!-- Add Reservation Form -->
                <?php if ($showAddForm): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-plus"></i> <?php echo t('new_reservation'); ?></h2>
                        <a href="bookings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <?php echo csrfField(); ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="customer_name">
                                        <i class="fas fa-user"></i> <?php echo t('customer_name'); ?> *
                                    </label>
                                    <input type="text" id="customer_name" name="customer_name" required placeholder="">
                                </div>
                                <div class="form-group">
                                    <label for="service">
                                        <i class="fas fa-cut"></i> <?php echo t('service'); ?> *
                                    </label>
                                    <select id="service" name="service" required onchange="updatePriceAndTimeSlots()">
                                        <option value="">-- <?php echo t('select_service'); ?> --</option>
                                        <?php foreach ($servicesList as $svc): ?>
                                        <option value="<?php echo htmlspecialchars($svc['name']); ?>" 
                                                data-price="<?php echo $svc['price']; ?>"
                                                data-duration="<?php echo $svc['duration']; ?>">
                                            <?php echo htmlspecialchars($svc['name']); ?> (<?php echo $svc['duration']; ?> min)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="price">
                                        <?php echo t('price'); ?>
                                    </label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" style="color: #FF6B35; font-weight: 700;" value="0.00" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label for="booking_date">
                                        <i class="fas fa-calendar"></i> <?php echo t('date'); ?> *
                                    </label>
                                    <input type="date" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="booking_time">
                                        <i class="fas fa-clock"></i> <?php echo t('time'); ?> *
                                    </label>
                                    <div class="time-picker-wrapper">
                                        <input type="time" id="booking_time" name="booking_time" required value="<?php echo date('H:i'); ?>" style="display: none;">
                                        <div class="time-slots" id="timeSlots" style="display: none;">
                                        </div>
                                        <p id="selectServiceMessage" style="color: #999; font-size: 0.9rem; padding: 10px 0; margin: 0;">
                                            <i class="fas fa-info-circle"></i> Please select a service first to see available time slots
                                        </p>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes">
                                        <i class="fas fa-sticky-note"></i> <?php echo t('notes'); ?>
                                    </label>
                                    <textarea id="notes" name="notes" rows="2" placeholder=""></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_reservation" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('create_reservation'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Reservation Form -->
                <?php if ($editBooking): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-edit"></i> <?php echo t('edit_reservation'); ?> #<?php echo $editBooking['id']; ?></h2>
                        <a href="bookings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo $editBooking['id']; ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="customer_name">
                                        <i class="fas fa-user"></i> <?php echo t('customer_name'); ?> *
                                    </label>
                                    <input type="text" id="customer_name" name="customer_name" required value="<?php echo htmlspecialchars($editBooking['customer_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="service">
                                        <i class="fas fa-cut"></i> <?php echo t('service'); ?> *
                                    </label>
                                    <select id="service" name="service" required onchange="updatePrice()">
                                        <option value="">-- <?php echo t('select_service'); ?> --</option>
                                        <?php foreach ($servicesList as $svc): ?>
                                        <option value="<?php echo htmlspecialchars($svc['name']); ?>" data-price="<?php echo $svc['price']; ?>" <?php echo $editBooking['service'] === $svc['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($svc['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="price">
                                        <?php echo t('price'); ?>
                                    </label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" style="color: #FF6B35; font-weight: 700;" value="<?php echo number_format(($editBooking['price'] > 0 ? $editBooking['price'] : ($editBooking['service_price'] ?? 0)), 2, '.', ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="booking_date">
                                        <i class="fas fa-calendar"></i> <?php echo t('date'); ?> *
                                    </label>
                                    <input type="date" id="booking_date" name="booking_date" required value="<?php echo $editBooking['booking_date']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="booking_time">
                                        <i class="fas fa-clock"></i> <?php echo t('time'); ?> *
                                    </label>
                                    <div class="time-picker-wrapper">
                                        <input type="time" id="booking_time" name="booking_time" required value="<?php echo date('H:i', strtotime($editBooking['booking_time'])); ?>">
                                        <div class="time-slots">
                                            <button type="button" class="time-slot" onclick="setTime('09:00')">9:00 AM</button>
                                            <button type="button" class="time-slot" onclick="setTime('10:00')">10:00 AM</button>
                                            <button type="button" class="time-slot" onclick="setTime('11:00')">11:00 AM</button>
                                            <button type="button" class="time-slot" onclick="setTime('12:00')">12:00 PM</button>
                                            <button type="button" class="time-slot" onclick="setTime('13:00')">1:00 PM</button>
                                            <button type="button" class="time-slot" onclick="setTime('14:00')">2:00 PM</button>
                                            <button type="button" class="time-slot" onclick="setTime('15:00')">3:00 PM</button>
                                            <button type="button" class="time-slot" onclick="setTime('16:00')">4:00 PM</button>
                                            <button type="button" class="time-slot" onclick="setTime('17:00')">5:00 PM</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes">
                                        <i class="fas fa-sticky-note"></i> <?php echo t('notes'); ?>
                                    </label>
                                    <textarea id="notes" name="notes" rows="2"><?php echo htmlspecialchars($editBooking['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="edit_reservation" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('update_reservation'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Revenue Summary -->
                <?php if ($statusFilter === 'completed'): ?>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px;">
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);">
                        <div class="card-body" style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="font-size: 1.8rem; opacity: 0.25; flex-shrink: 0;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.65rem; opacity: 0.9; margin-bottom: 3px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">
                                        <i class="fas fa-chart-line"></i> <?php echo t('total_revenue_completed'); ?>
                                    </div>
                                    <div style="font-size: 1.2rem; font-weight: 700; letter-spacing: -0.5px; line-height: 1.2;">
                                        <?php echo number_format($totalRevenue, 0); ?> <span style="font-size: 0.7em; opacity: 0.8;">IQD</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #28a745 0%, #48c768 100%); color: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);">
                        <div class="card-body" style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="font-size: 1.8rem; opacity: 0.25; flex-shrink: 0;">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.65rem; opacity: 0.9; margin-bottom: 3px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">
                                        <i class="fas fa-users"></i> <?php echo t('total_customers_completed'); ?>
                                    </div>
                                    <div style="font-size: 1.2rem; font-weight: 700; letter-spacing: -0.5px; line-height: 1.2;">
                                        <?php echo $todayCustomers; ?> <span style="font-size: 0.7em; opacity: 0.8;">Customers</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="bookings.php<?php echo $dateFilter ? '?date=' . $dateFilter : ($monthFilter ? '?month=' . $monthFilter : ''); echo ($filterUserId > 0) ? (strpos($_SERVER['QUERY_STRING'], '?') !== false ? '&' : '?') . 'filter_user=' . $filterUserId : ''; ?>" class="filter-tab <?php echo !$statusFilter ? 'active' : ''; ?>">
                        <?php echo t('all'); ?> <span class="badge"><?php echo $allCount; ?></span>
                    </a>
                    <a href="?filter=pending<?php echo $dateFilter ? '&date=' . $dateFilter : ($monthFilter ? '&month=' . $monthFilter : ''); echo ($filterUserId > 0) ? '&filter_user=' . $filterUserId : ''; ?>" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        <?php echo t('pending'); ?> <span class="badge warning"><?php echo $pendingCount; ?></span>
                    </a>
                    <a href="?filter=confirmed<?php echo $dateFilter ? '&date=' . $dateFilter : ($monthFilter ? '&month=' . $monthFilter : ''); echo ($filterUserId > 0) ? '&filter_user=' . $filterUserId : ''; ?>" class="filter-tab <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">
                        <?php echo t('confirmed'); ?> <span class="badge success"><?php echo $confirmedCount; ?></span>
                    </a>
                    <a href="?filter=completed<?php echo $dateFilter ? '&date=' . $dateFilter : ($monthFilter ? '&month=' . $monthFilter : ''); echo ($filterUserId > 0) ? '&filter_user=' . $filterUserId : ''; ?>" class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                        <?php echo t('completed'); ?> <span class="badge"><?php echo $completedCount; ?></span>
                    </a>
                </div>

                <!-- Bookings Table -->
                <div class="dashboard-card">
                    <div class="card-body">
                        <?php if ($bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?php echo t('customer'); ?></th>
                                        <th><?php echo t('phone'); ?></th>
                                        <th><?php echo t('service'); ?></th>
                                        <th><?php echo t('price'); ?></th>
                                        <th><?php echo t('barber'); ?></th>
                                        <th><?php echo t('date_time'); ?></th>
                                        <th><?php echo t('status'); ?></th>
                                        <th><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="ID">#<?php echo $booking['id']; ?></td>
                                        <td data-label="<?php echo t('customer'); ?>"><strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong></td>
                                        <td data-label="<?php echo t('phone'); ?>"><?php echo htmlspecialchars($booking['phone']); ?></td>
                                        <td data-label="<?php echo t('service'); ?>"><?php echo htmlspecialchars($booking['service']); ?></td>
                                        <td data-label="<?php echo t('price'); ?>"><strong style="color: #FF6B35;"><?php echo number_format(($booking['price'] > 0 ? $booking['price'] : ($booking['service_price'] ?? 0)), 0); ?></strong></td>
                                        <td data-label="<?php echo t('barber'); ?>"><?php echo htmlspecialchars($booking['barber_name'] ?? t('any')); ?></td>
                                        <td data-label="<?php echo t('date_time'); ?>">
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> - <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                        </td>
                                        <td data-label="<?php echo t('status'); ?>">
                                            <span class="status-badge <?php echo $booking['status']; ?>">
                                                <?php echo t($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $booking['phone']); ?>?text=Sir,%20your%20request%20has%20been%20accepted" target="_blank" class="btn btn-sm" style="background: #25D366; color: white;" title="WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                                <a href="?action=edit&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-secondary" title="<?php echo t('edit'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="new_status" value="confirmed">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success" title="<?php echo t('confirm'); ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'confirmed'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="new_status" value="completed">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary" title="<?php echo t('complete'); ?>">
                                                        <i class="fas fa-flag-checkered"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="new_status" value="cancelled">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-warning" title="<?php echo t('cancel'); ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3><?php echo t('no_bookings'); ?></h3>
                            <p><?php echo $statusFilter ? t('no_bookings') : t('no_bookings'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-filter"></i> <?php echo t('filter_bookings'); ?></h3>
                <span class="close" onclick="closeFilterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (hasPermission('can_users')): ?>
                <div class="form-group">
                    <label for="modalUserFilter"><i class="fas fa-user"></i> <?php echo t('filter_by_user'); ?></label>
                    <select id="modalUserFilter" class="form-control">
                        <option value="0"><?php echo t('all_users'); ?></option>
                        <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filterUserId == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="modalDateFilter"><i class="fas fa-calendar"></i> <?php echo t('select_date'); ?></label>
                    <input type="date" id="modalDateFilter" class="form-control" value="<?php echo $dateFilter; ?>">
                </div>
                <div class="form-group">
                    <label for="modalMonthFilter"><i class="fas fa-calendar-alt"></i> <?php echo t('select_month'); ?></label>
                    <select id="modalMonthFilter" class="form-control">
                        <option value=""><?php echo t('all_months'); ?></option>
                        <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo $month['month_value']; ?>" <?php echo ($monthFilter === $month['month_value']) ? 'selected' : ''; ?>>
                            <?php echo date('F Y', strtotime($month['month_date'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle"></i> <?php echo t('only_months_with_bookings'); ?>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="resetFilters()" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> <?php echo t('reset'); ?>
                </button>
                <button onclick="applyFilters()" class="btn btn-primary">
                    <i class="fas fa-check"></i> <?php echo t('apply_filter'); ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking on nav links (mobile)
        document.querySelectorAll('.sidebar-nav > ul > li > a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    document.querySelector('.sidebar').classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991) {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar.classList.contains('active') && !e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // Toggle language dropdown
        function toggleLangDropdown(btn) {
            btn.closest('.lang-dropdown').classList.toggle('open');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.lang-dropdown')) {
                document.querySelectorAll('.lang-dropdown.open').forEach(function(dropdown) {
                    dropdown.classList.remove('open');
                });
            }
        });

        // Modal functions
        function openFilterModal() {
            document.getElementById('filterModal').classList.add('show');
        }

        function closeFilterModal() {
            document.getElementById('filterModal').classList.remove('show');
        }

        function applyFilters() {
            const date = document.getElementById('modalDateFilter').value;
            const month = document.getElementById('modalMonthFilter').value;
            const userFilterElement = document.getElementById('modalUserFilter');
            const userId = userFilterElement ? userFilterElement.value : 0;
            const url = new URL(window.location.href);
            
            // Handle user filter
            if (userId && userId != '0') {
                url.searchParams.set('filter_user', userId);
            } else {
                url.searchParams.delete('filter_user');
            }
            
            // If month is selected, use it and ignore date
            if (month) {
                url.searchParams.set('month', month);
                url.searchParams.delete('date');
            } else if (date) {
                url.searchParams.set('date', date);
                url.searchParams.delete('month');
            } else {
                url.searchParams.delete('date');
                url.searchParams.delete('month');
            }
            
            // Preserve status filter if it exists
            // (filter parameter is already in URL, no need to set it again)
            
            window.location.href = url.toString();
        }

        function resetFilters() {
            // Clear form fields without closing modal
            document.getElementById('modalDateFilter').value = '';
            document.getElementById('modalMonthFilter').value = '';
            const userFilterElement = document.getElementById('modalUserFilter');
            if (userFilterElement) {
                userFilterElement.value = '0';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('filterModal');
            if (event.target === modal) {
                closeFilterModal();
            }
        }

        // Update price when service is selected
        function updatePrice() {
            const serviceSelect = document.getElementById('service');
            const priceInput = document.getElementById('price');

            if (serviceSelect && priceInput) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price') || 0;
                priceInput.value = parseFloat(price).toFixed(2);
            }
        }

        // Update price and generate time slots based on service duration
        function updatePriceAndTimeSlots() {
            const serviceSelect = document.getElementById('service');
            const priceInput = document.getElementById('price');
            const timeSlotsContainer = document.getElementById('timeSlots');
            const timeInput = document.getElementById('booking_time');
            const selectServiceMessage = document.getElementById('selectServiceMessage');

            if (serviceSelect && priceInput) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price') || 0;
                const duration = parseInt(selectedOption.getAttribute('data-duration')) || 30;
                
                priceInput.value = parseFloat(price).toFixed(2);

                // Generate time slots based on duration
                if (selectedOption.value) {
                    // Hide message and show time slots
                    if (selectServiceMessage) selectServiceMessage.style.display = 'none';
                    if (timeInput) timeInput.style.display = 'block';
                    if (timeSlotsContainer) timeSlotsContainer.style.display = 'flex';
                    
                    generateTimeSlots(duration, timeSlotsContainer);
                } else {
                    // Show message and hide time slots
                    if (selectServiceMessage) selectServiceMessage.style.display = 'block';
                    if (timeInput) timeInput.style.display = 'none';
                    if (timeSlotsContainer) timeSlotsContainer.style.display = 'none';
                    timeSlotsContainer.innerHTML = '';
                }
            }
        }

        // Generate time slots based on service duration
        function generateTimeSlots(duration, container) {
            const startHour = 9; // 9:00 AM
            const endHour = 18; // 6:00 PM
            const slots = [];

            // Generate slots
            for (let hour = startHour; hour < endHour; hour++) {
                let minutes = 0;
                while (minutes < 60) {
                    const totalMinutes = hour * 60 + minutes;
                    const endTotalMinutes = totalMinutes + duration;
                    const endHour = Math.floor(endTotalMinutes / 60);
                    
                    // Don't create slot if it extends beyond working hours
                    if (endHour >= endHour && endTotalMinutes % 60 > 0) break;
                    if (endHour > endHour) break;
                    
                    const timeStr = `${String(hour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
                    const period = hour >= 12 ? 'PM' : 'AM';
                    const displayTime = `${displayHour}:${String(minutes).padStart(2, '0')} ${period}`;
                    
                    slots.push({ time: timeStr, display: displayTime });
                    minutes += duration;
                }
            }

            // Render slots
            container.innerHTML = '';
            slots.forEach(slot => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'time-slot';
                button.textContent = slot.display;
                button.onclick = function() { setTime(slot.time); };
                container.appendChild(button);
            });
        }

        // Set time from quick select buttons
        function setTime(time) {
            const timeInput = document.getElementById('booking_time');
            if (timeInput) {
                timeInput.value = time;
                // Update active state on buttons
                document.querySelectorAll('.time-slot').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(time)) {
                        btn.classList.add('active');
                    }
                });
            }
        }

        // Highlight current time slot on load
        document.addEventListener('DOMContentLoaded', function() {
            const timeInput = document.getElementById('booking_time');
            if (timeInput) {
                const currentTime = timeInput.value;
                document.querySelectorAll('.time-slot').forEach(btn => {
                    if (btn.getAttribute('onclick').includes(currentTime)) {
                        btn.classList.add('active');
                    }
                });
            }
        });

        // Auto-refresh at midnight to show new day's data
        function checkMidnight() {
            const now = new Date();
            const midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            const timeUntilMidnight = midnight - now;
            
            setTimeout(() => {
                window.location.reload();
            }, timeUntilMidnight);
        }
        checkMidnight();
    </script>
</body>
</html>
