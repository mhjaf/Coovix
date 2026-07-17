<?php
require_once 'config.php';
requireLogin();

// Only users with staff status permission can see this page
if (!hasPermission('can_staff_status')) {
    header('Location: index.php');
    exit();
}

// Get filter parameters with validation
$filterDate = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';
$filterUser = isset($_GET['user']) ? (($_GET['user'] === 'all') ? 'all' : intval($_GET['user'])) : 'all';
$monthFilter = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : '';
$fromDate = isset($_GET['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to_date']) ? $_GET['to_date'] : '';
$rangeType = isset($_GET['range']) && in_array($_GET['range'], ['this_week','this_month','last_month','last_3_months','last_6_months','last_year','custom']) ? $_GET['range'] : '';

// Check if filter parameters exist in URL
$hasFilterParams = isset($_GET['user']) || isset($_GET['month']) || isset($_GET['date']) || isset($_GET['from_date']);

// If no filters at all, show today's data
if (!$hasFilterParams) {
    $filterDate = date('Y-m-d');
}

// If date is set but empty string and no month, use today
if (isset($_GET['date']) && $filterDate === '' && !$monthFilter && !$fromDate) {
    $filterDate = date('Y-m-d');
}

// If only user is selected (no month/date/range), default to current month view
if ($filterUser !== 'all' && !$monthFilter && !isset($_GET['date']) && !$fromDate) {
    $monthFilter = date('Y-m');
}

// Get available months from work_hours for filter (filtered by selected staff if applicable)
$monthsUserFilter = "";
if (!hasPermission('can_users')) {
    $currentUserId = $_SESSION['admin_id'];
    $monthsUserFilter = " AND wh.barber_id IN (SELECT id FROM barbers WHERE user_id = $currentUserId)";
}
// If a specific staff is selected, only show months that have data for that staff
if ($filterUser !== 'all') {
    $monthsUserFilter .= " AND wh.barber_id = " . intval($filterUser);
}
$availableMonthsQuery = "SELECT DISTINCT DATE_FORMAT(wh.work_date, '%Y-%m') as month_value, DATE_FORMAT(wh.work_date, '%Y-%m-01') as month_date FROM work_hours wh WHERE wh.work_date IS NOT NULL" . $monthsUserFilter . " ORDER BY month_value DESC";
$availableMonthsResult = $conn->query($availableMonthsQuery);
$availableMonths = [];
if ($availableMonthsResult) {
    while ($row = $availableMonthsResult->fetch_assoc()) {
        $availableMonths[] = $row;
    }
}

// Get min and max dates from work_hours for date range picker
$dateRangeQuery = "SELECT MIN(wh.work_date) as min_date, MAX(wh.work_date) as max_date FROM work_hours wh WHERE wh.work_date IS NOT NULL" . $monthsUserFilter;
$dateRangeResult = $conn->query($dateRangeQuery);
$minDate = '';
$maxDate = '';
if ($dateRangeResult && $row = $dateRangeResult->fetch_assoc()) {
    $minDate = $row['min_date'] ?: '';
    $maxDate = $row['max_date'] ?: '';
}

// Apply date range filter if set (from quick buttons)
$isRangeView = false;
$rangeLabelText = '';
if ($fromDate && $toDate) {
    $firstDayOfMonth = $fromDate;
    $lastDayOfMonth = $toDate;
    $filterDate = $fromDate;
    $isMonthView = true;
    $isRangeView = true;
    // Set range label based on range type
    if ($rangeType === 'this_week') {
        $rangeLabelText = 'This Week';
    } elseif ($rangeType === 'this_month') {
        $rangeLabelText = 'This Month';
    } elseif ($rangeType === 'last_month') {
        $rangeLabelText = 'Last Month';
    } elseif ($rangeType === 'last_3_months') {
        $rangeLabelText = 'Last 3 Months';
    } elseif ($rangeType === 'last_6_months') {
        $rangeLabelText = 'Last 6 Months';
    } elseif ($rangeType === 'last_year') {
        $rangeLabelText = 'Last Year';
    } elseif ($rangeType === 'custom') {
        $rangeLabelText = date('M d, Y', strtotime($fromDate)) . ' - ' . date('M d, Y', strtotime($toDate));
    } else {
        $rangeLabelText = date('M d, Y', strtotime($fromDate)) . ' - ' . date('M d, Y', strtotime($toDate));
    }
} elseif ($monthFilter) {
    // Apply month filter if set
    $firstDayOfMonth = date('Y-m-01', strtotime($monthFilter . '-01'));
    $lastDayOfMonth = date('Y-m-t', strtotime($monthFilter . '-01'));
    $filterDate = $firstDayOfMonth;
    $isMonthView = true;
} else {
    $isMonthView = false;
    // If no month filter, ensure we have a valid date
    if (empty($filterDate)) {
        $filterDate = date('Y-m-d');
    }
}

// Get today's date (use filterDate which is now guaranteed to be set)
$today = $filterDate;
$dayOfWeek = date('w', strtotime($filterDate)); // 0=Sunday, 6=Saturday

// Get all barbers for the filter dropdown
$allBarbersSql = "SELECT id, name FROM barbers WHERE status = 'active' ORDER BY name ASC";
$allBarbersResult = $conn->query($allBarbersSql);
$allBarbers = [];
while ($row = $allBarbersResult->fetch_assoc()) {
    $allBarbers[] = $row;
}

// If month view or range view, get all work hours for the period
if ($isMonthView) {
    $sql = "SELECT
        b.id,
        b.name,
        b.status,
        wh.work_date,
        wh.start_time as work_start,
        wh.end_time as work_end,
        wh.total_hours
    FROM barbers b
    LEFT JOIN work_hours wh ON b.id = wh.barber_id";

    // Add date range filter if month or range is selected
    if ($monthFilter || $isRangeView) {
        $sql .= " AND wh.work_date >= ? AND wh.work_date <= ?";
    }

    $sql .= " WHERE b.status = 'active'";

    // Add user filter if specified
    if ($filterUser !== 'all') {
        $sql .= " AND b.id = ?";
    }

    $sql .= " ORDER BY wh.work_date DESC, b.name ASC";

    $stmt = $conn->prepare($sql);

    // Bind parameters based on filters
    if (($monthFilter || $isRangeView) && $filterUser !== 'all') {
        $stmt->bind_param("ssi", $firstDayOfMonth, $lastDayOfMonth, $filterUser);
    } elseif ($monthFilter || $isRangeView) {
        $stmt->bind_param("ss", $firstDayOfMonth, $lastDayOfMonth);
    } elseif ($filterUser !== 'all') {
        // No month filter, but user filter exists - don't bind any date params
        // This case won't happen in month view, but keeping for safety
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $barbers = [];
    while ($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }
    $stmt->close();

    // Get summary of total hours per staff member for the period
    $summarySql = "SELECT
        b.id,
        b.name,
        COUNT(wh.id) as days_worked,
        COALESCE(SUM(wh.total_hours), 0) as total_hours
    FROM barbers b
    LEFT JOIN work_hours wh ON b.id = wh.barber_id";

    if ($monthFilter || $isRangeView) {
        $summarySql .= " AND wh.work_date >= ? AND wh.work_date <= ?";
    }

    $summarySql .= " WHERE b.status = 'active'";

    if ($filterUser !== 'all') {
        $summarySql .= " AND b.id = ?";
    }

    $summarySql .= " GROUP BY b.id, b.name ORDER BY total_hours DESC";

    $summaryStmt = $conn->prepare($summarySql);

    if (($monthFilter || $isRangeView) && $filterUser !== 'all') {
        $summaryStmt->bind_param("ssi", $firstDayOfMonth, $lastDayOfMonth, $filterUser);
    } elseif ($monthFilter || $isRangeView) {
        $summaryStmt->bind_param("ss", $firstDayOfMonth, $lastDayOfMonth);
    }

    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();

    $staffSummary = [];
    while ($row = $summaryResult->fetch_assoc()) {
        $staffSummary[] = $row;
    }
    $summaryStmt->close();
} else {
    // Single day view (date filter or no filter)
    $sql = "SELECT
        b.id,
        b.name,
        b.status,
        bs.start_time as schedule_start,
        bs.end_time as schedule_end,
        bs.break_time,
        bs.is_day_off,
        wh.start_time as work_start,
        wh.end_time as work_end,
        wh.total_hours
    FROM barbers b
    LEFT JOIN barber_schedules bs ON b.id = bs.barber_id AND bs.day_of_week = ?
    LEFT JOIN work_hours wh ON b.id = wh.barber_id AND wh.work_date = ?
    WHERE b.status = 'active'";

    // Add user filter if specified
    if ($filterUser !== 'all') {
        $sql .= " AND b.id = ?";
    }

    $sql .= " ORDER BY b.name ASC";

    $stmt = $conn->prepare($sql);
    if ($filterUser !== 'all') {
        $stmt->bind_param("isi", $dayOfWeek, $today, $filterUser);
    } else {
        $stmt->bind_param("is", $dayOfWeek, $today);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $barbers = [];
    while ($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }
    $stmt->close();
}

// Function to check if barber is currently working
function isCurrentlyWorking($scheduleStart, $scheduleEnd, $isDayOff) {
    if ($isDayOff) return false;
    if (!$scheduleStart || !$scheduleEnd) return false;

    // If barber has a schedule for today, show as working
    return true;
}

// Get day name
$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$todayName = $dayNames[$dayOfWeek];
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('staff_status'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .excel-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .excel-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }
        .excel-header .date-info {
            color: #666;
            font-size: 0.95rem;
        }
        .excel-header .current-time {
            font-size: 1.8rem;
            font-weight: 700;
            color: #FF6B35;
            font-variant-numeric: tabular-nums;
        }
        .excel-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .excel-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .excel-table thead {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
        }
        .excel-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .excel-table th:last-child {
            border-right: none;
        }
        .excel-table th.center {
            text-align: center;
        }
        .excel-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        .excel-table tbody tr:hover {
            background: #f8f9fa;
        }
        .excel-table tbody tr:last-child td {
            border-bottom: none;
        }
        .staff-name {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B35, #e55a2b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.working {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-badge.off-duty {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-badge.day-off {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-badge.no-schedule {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        .status-badge.working .pulse {
            background: #28a745;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }
        .time-cell {
            font-weight: 600;
            color: #333;
            font-variant-numeric: tabular-nums;
        }
        .time-cell.center {
            text-align: center;
        }
        .duration-cell {
            text-align: center;
            font-weight: 600;
            color: #FF6B35;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #666;
            margin: 0 0 10px 0;
        }
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .print-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        @media print {
            .sidebar, .main-header, .print-btn {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        /* Filter Badge */
        .filter-badge {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 8px;
        }
        .filter-badge i {
            margin-right: 5px;
        }
        
        /* Hours Badge */
        .hours-badge {
            display: inline-block;
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
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
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        /* Quick Filter Buttons */
        .quick-filter-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .quick-btn {
            padding: 10px 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .quick-btn:hover {
            border-color: #FF6B35;
            color: #FF6B35;
            background: #fff5f0;
        }
        .quick-btn.active {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            border-color: #FF6B35;
        }
        .quick-btn i {
            font-size: 0.75rem;
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
                    <span><?php echo t('classic_barber'); ?></span>
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
                    <li>
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
                    <li class="active">
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
                        <a href="/index.html" target="_blank">
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
                    <h1><?php echo t('staff_status'); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="window.print()" class="btn btn-success print-btn">
                        <i class="fas fa-print"></i> <?php echo t('print_report'); ?>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Excel-style Header -->
                <div class="excel-header">
                    <div>
                        <h2><i class="fas fa-users"></i> Staff Schedule - <?php echo $todayName; ?></h2>
                        <div class="date-info"><?php echo date('F j, Y', strtotime($filterDate)); ?> - Day <?php echo date('j', strtotime($filterDate)); ?></div>
                        <?php if ($filterUser !== 'all' || $filterDate !== date('Y-m-d') || $monthFilter || $isRangeView): ?>
                        <div class="filter-container" style="margin-top: 8px;">
                            <?php if ($filterUser !== 'all'):
                                $selectedBarber = array_filter($allBarbers, function($b) use ($filterUser) {
                                    return $b['id'] == $filterUser;
                                });
                                $selectedBarber = reset($selectedBarber);
                            ?>
                            <span class="filter-badge"><i class="fas fa-user"></i> <?php echo htmlspecialchars($selectedBarber['name']); ?></span>
                            <?php endif; ?>
                            <?php if (!$isRangeView && !$monthFilter && $filterDate !== date('Y-m-d')): ?>
                            <span class="filter-badge"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($filterDate)); ?></span>
                            <?php endif; ?>
                            <?php if ($monthFilter && !$isRangeView): ?>
                            <span class="filter-badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffc107;"><i class="fas fa-calendar-alt"></i> <?php echo date('F Y', strtotime($monthFilter . '-01')); ?></span>
                            <?php endif; ?>
                            <?php if ($isRangeView): ?>
                            <span class="filter-badge" style="background: #d4edda; color: #155724; border: 1px solid #28a745;"><i class="fas fa-calendar-week"></i> <?php echo $rangeLabelText; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <button onclick="openFilterModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-filter"></i> <?php echo t('filter'); ?>
                        </button>
                        <div style="text-align: right;">
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;"><?php echo t('current_time'); ?></div>
                            <div class="current-time" id="currentTime"><?php echo date('H:i:s'); ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($isMonthView && isset($staffSummary)): ?>
                <!-- Staff Summary Cards -->
                <div class="summary-section" style="margin-bottom: 20px;">
                    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 1.1rem;">
                        <i class="fas fa-chart-bar" style="color: #FF6B35; margin-right: 8px;"></i>
                        <?php echo t('staff_hours_summary'); ?>
                        <?php if ($isRangeView): ?>
                        <span style="font-weight: normal; font-size: 0.9rem; color: #666;"> - <?php echo $rangeLabelText; ?></span>
                        <?php endif; ?>
                    </h3>
                    <div class="summary-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                        <?php
                        $grandTotal = 0;
                        foreach ($staffSummary as $staff):
                            $grandTotal += $staff['total_hours'];
                            $initials = '';
                            $nameParts = explode(' ', $staff['name']);
                            foreach ($nameParts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                        ?>
                        <div class="summary-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 15px;">
                            <div class="staff-avatar" style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #FF6B35, #e55a2b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem;">
                                <?php echo $initials; ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 4px;"><?php echo htmlspecialchars($staff['name']); ?></div>
                                <div style="font-size: 0.85rem; color: #666;"><?php echo $staff['days_worked']; ?> <?php echo $staff['days_worked'] != 1 ? t('days_worked') : t('day_worked'); ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: <?php echo $staff['total_hours'] > 0 ? '#FF6B35' : '#999'; ?>;">
                                    <?php echo number_format($staff['total_hours'], 1); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #666; text-transform: uppercase;"><?php echo t('hours'); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($staffSummary) > 1): ?>
                    <div style="margin-top: 15px; padding: 15px 20px; background: linear-gradient(135deg, #1a1a1a, #2d2d2d); border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: white; font-weight: 600;"><i class="fas fa-calculator" style="margin-right: 8px;"></i> <?php echo t('total_all_staff'); ?></span>
                        <span style="color: #FF6B35; font-size: 1.3rem; font-weight: 700;"><?php echo number_format($grandTotal, 1); ?> <?php echo t('hours'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (count($barbers) > 0): ?>
                <?php if ($isMonthView): ?>
                <!-- Month View - Group by Date -->
                <div class="excel-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('staff_member'); ?></th>
                                <th class="center"><?php echo t('start_time'); ?></th>
                                <th class="center"><?php echo t('end_time'); ?></th>
                                <th class="center"><?php echo t('total_hours'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            $totalHours = 0;
                            $hasData = false;
                            foreach ($barbers as $barber):
                                if (!$barber['work_date']) continue; // Skip if no work date
                                
                                $hasData = true;
                                $totalHours += $barber['total_hours'] ?? 0;
                            ?>
                            <tr>
                                <td data-label="#"><?php echo $counter++; ?></td>
                                <td data-label="<?php echo t('date'); ?>"><strong><?php echo date('M d, Y (D)', strtotime($barber['work_date'])); ?></strong></td>
                                <td data-label="<?php echo t('staff_member'); ?>">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="avatar"><?php
                                            $nameParts = explode(' ', $barber['name']);
                                            $initials = '';
                                            foreach ($nameParts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            echo substr($initials, 0, 2);
                                        ?></div>
                                        <span><?php echo htmlspecialchars($barber['name']); ?></span>
                                    </div>
                                </td>
                                <td data-label="<?php echo t('start_time'); ?>" class="center"><?php echo $barber['work_start'] ? date('g:i A', strtotime($barber['work_start'])) : '-'; ?></td>
                                <td data-label="<?php echo t('end_time'); ?>" class="center"><?php echo $barber['work_end'] ? date('g:i A', strtotime($barber['work_end'])) : '-'; ?></td>
                                <td data-label="<?php echo t('total_hours'); ?>" class="center">
                                    <span class="hours-badge"><?php echo number_format($barber['total_hours'] ?? 0, 1); ?>h</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ($hasData && $counter > 1): ?>
                            <tr style="background: #f8f9fa; font-weight: 600;">
                                <td colspan="5" style="text-align: right; padding-right: 15px;"><?php echo t('total_hours'); ?>:</td>
                                <td class="center">
                                    <span class="hours-badge" style="background: #28a745; color: white; font-size: 1.1rem;"><?php echo number_format($totalHours, 1); ?>h</span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!$hasData): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                    <?php echo t('no_work_hours_recorded'); ?>
                                    <?php if ($isRangeView): ?>
                                    <br><small style="color: #aaa;"><?php echo date('M d, Y', strtotime($firstDayOfMonth)); ?> - <?php echo date('M d, Y', strtotime($lastDayOfMonth)); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Excel-style Table -->
                <div class="excel-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('staff_member'); ?></th>
                                <th class="center"><?php echo t('status'); ?></th>
                                <th class="center"><?php echo t('start_time'); ?></th>
                                <th class="center"><?php echo t('end_time'); ?></th>
                                <th class="center">Break Start</th>
                                <th class="center"><?php echo t('duration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            foreach ($barbers as $barber):
                                $isWorking = isCurrentlyWorking($barber['schedule_start'], $barber['schedule_end'], $barber['is_day_off']);
                                $isDayOff = $barber['is_day_off'];
                                $hasSchedule = $barber['schedule_start'] && $barber['schedule_end'];

                                // Determine status
                                if ($isDayOff) {
                                    $statusClass = 'day-off';
                                    $statusText = t('day_off');
                                } elseif (!$hasSchedule) {
                                    $statusClass = 'no-schedule';
                                    $statusText = t('no_schedule');
                                } elseif ($isWorking) {
                                    $statusClass = 'working';
                                    $statusText = t('working');
                                } else {
                                    $statusClass = 'off-duty';
                                    $statusText = t('off_duty');
                                }

                                // Get initials
                                $nameParts = explode(' ', $barber['name']);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                $initials = substr($initials, 0, 2);

                                // Calculate duration
                                $duration = '-';
                                if ($hasSchedule && !$isDayOff) {
                                    $start = strtotime($barber['schedule_start']);
                                    $end = strtotime($barber['schedule_end']);
                                    $hours = ($end - $start) / 3600;
                                    $duration = number_format($hours, 1) . ' ' . t('hrs');
                                }
                            ?>
                            <tr>
                                <td data-label="#" style="text-align: center; color: #666; font-weight: 600;"><?php echo $counter++; ?></td>
                                <td data-label="<?php echo t('staff_member'); ?>">
                                    <div class="staff-name">
                                        <div class="staff-avatar"><?php echo $initials; ?></div>
                                        <span><?php echo htmlspecialchars($barber['name']); ?></span>
                                    </div>
                                </td>
                                <td data-label="<?php echo t('status'); ?>" style="text-align: center;">
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php if ($statusClass === 'working'): ?>
                                        <span class="pulse"></span>
                                        <?php endif; ?>
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td data-label="<?php echo t('start_time'); ?>" class="time-cell center">
                                    <?php echo $hasSchedule && !$isDayOff ? date('g:i A', strtotime($barber['schedule_start'])) : '-'; ?>
                                </td>
                                <td data-label="<?php echo t('end_time'); ?>" class="time-cell center">
                                    <?php echo $hasSchedule && !$isDayOff ? date('g:i A', strtotime($barber['schedule_end'])) : '-'; ?>
                                </td>
                                <td data-label="Break Start" class="time-cell center">
                                    <?php echo $hasSchedule && !$isDayOff && $barber['break_time'] ? $barber['break_time'] : '-'; ?>
                                </td>
                                <td data-label="<?php echo t('duration'); ?>" class="duration-cell">
                                    <?php echo $duration; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="excel-table">
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h3><?php echo t('no_active_barbers'); ?></h3>
                        <p><?php echo t('add_barbers_to_see_status'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-filter"></i> <?php echo t('filter_staff_schedule'); ?></h3>
                <span class="close" onclick="closeFilterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="modalUserFilter"><i class="fas fa-user"></i> <?php echo t('select_staff_member'); ?></label>
                    <select id="modalUserFilter" class="form-control">
                        <option value="all" <?php echo $filterUser === 'all' ? 'selected' : ''; ?>><?php echo t('all_staff'); ?></option>
                        <?php foreach ($allBarbers as $barber): ?>
                        <option value="<?php echo $barber['id']; ?>" <?php echo $filterUser == $barber['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($barber['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> <?php echo t('custom_date_range'); ?></label>
                    <?php if ($minDate && $maxDate): ?>
                    <small style="color: #888; display: block; margin-bottom: 8px;">
                        <?php echo t('data_available'); ?>: <?php echo date('M d, Y', strtotime($minDate)); ?> - <?php echo date('M d, Y', strtotime($maxDate)); ?>
                    </small>
                    <?php endif; ?>
                    <div class="date-range-row" style="display: flex; gap: 10px; align-items: center;">
                        <div style="flex: 1;">
                            <small style="color: #666; display: block; margin-bottom: 4px;"><?php echo t('start_date'); ?></small>
                            <input type="date" id="modalStartDate" class="form-control" value="<?php echo $fromDate ?: ''; ?>" <?php echo $minDate ? 'min="'.$minDate.'"' : ''; ?> <?php echo $maxDate ? 'max="'.$maxDate.'"' : ''; ?>>
                        </div>
                        <span style="color: #999; padding-top: 18px;">to</span>
                        <div style="flex: 1;">
                            <small style="color: #666; display: block; margin-bottom: 4px;"><?php echo t('end_date'); ?></small>
                            <input type="date" id="modalEndDate" class="form-control" value="<?php echo $toDate ?: ''; ?>" <?php echo $minDate ? 'min="'.$minDate.'"' : ''; ?> <?php echo $maxDate ? 'max="'.$maxDate.'"' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> <?php echo t('quick_date_range'); ?></label>
                    <div class="quick-filter-buttons">
                        <button type="button" onclick="applyQuickFilter('this_week')" class="quick-btn <?php echo $rangeType === 'this_week' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i> <?php echo t('this_week'); ?>
                        </button>
                        <button type="button" onclick="applyQuickFilter('this_month')" class="quick-btn <?php echo $rangeType === 'this_month' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i> <?php echo t('this_month'); ?>
                        </button>
                        <button type="button" onclick="applyQuickFilter('last_month')" class="quick-btn <?php echo $rangeType === 'last_month' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-minus"></i> <?php echo t('last_month'); ?>
                        </button>
                        <button type="button" onclick="applyQuickFilter('last_3_months')" class="quick-btn <?php echo $rangeType === 'last_3_months' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> <?php echo t('last_3_months'); ?>
                        </button>
                        <button type="button" onclick="applyQuickFilter('last_6_months')" class="quick-btn <?php echo $rangeType === 'last_6_months' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-week"></i> <?php echo t('last_6_months'); ?>
                        </button>
                        <button type="button" onclick="applyQuickFilter('last_year')" class="quick-btn <?php echo $rangeType === 'last_year' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> <?php echo t('last_year'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="resetFilters()" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> <?php echo t('reset'); ?>
                </button>
                <button onclick="applyFilters()" class="btn btn-primary">
                    <i class="fas fa-check"></i> <?php echo t('apply_filters'); ?>
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
            const userId = document.getElementById('modalUserFilter').value;
            const startDate = document.getElementById('modalStartDate').value;
            const endDate = document.getElementById('modalEndDate').value;

            const url = new URL(window.location.href);
            url.search = ''; // Clear all existing params

            // Add user filter
            if (userId !== 'all') {
                url.searchParams.set('user', userId);
            }

            // Add custom date range if both dates selected
            if (startDate && endDate) {
                url.searchParams.set('from_date', startDate);
                url.searchParams.set('to_date', endDate);
                url.searchParams.set('range', 'custom');
            }

            window.location.href = url.toString();
        }

        function resetFilters() {
            const url = new URL(window.location.href);
            url.searchParams.delete('user');
            url.searchParams.delete('date');
            url.searchParams.delete('month');
            url.searchParams.delete('from_date');
            url.searchParams.delete('to_date');
            url.searchParams.delete('range');
            window.location.href = url.toString();
        }

        // Quick filter function for date ranges
        function applyQuickFilter(rangeType) {
            const userId = document.getElementById('modalUserFilter').value;
            const today = new Date();
            let fromDate, toDate;

            // Calculate date range based on type
            if (rangeType === 'this_week') {
                // This week: from Monday to Sunday of current week
                const dayOfWeek = today.getDay();
                const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Sunday = 0
                const monday = new Date(today);
                monday.setDate(today.getDate() + mondayOffset);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                fromDate = formatDate(monday);
                toDate = formatDate(sunday);
            } else if (rangeType === 'this_month') {
                // This month: from 1st to last day of current month
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                fromDate = formatDate(firstDay);
                toDate = formatDate(lastDay);
            } else if (rangeType === 'last_month') {
                // Last month: from 1st to last day of previous month
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                fromDate = formatDate(lastMonth);
                toDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
            } else if (rangeType === 'last_3_months') {
                // Last 3 months: from 3 months ago to today
                const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                fromDate = formatDate(threeMonthsAgo);
                toDate = formatDate(today);
            } else if (rangeType === 'last_6_months') {
                // Last 6 months: from 6 months ago to today
                const sixMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 6, 1);
                fromDate = formatDate(sixMonthsAgo);
                toDate = formatDate(today);
            } else if (rangeType === 'last_year') {
                // Last year: from 12 months ago to today
                const oneYearAgo = new Date(today.getFullYear() - 1, today.getMonth(), 1);
                fromDate = formatDate(oneYearAgo);
                toDate = formatDate(today);
            }

            // Build URL with parameters
            const url = new URL(window.location.href);
            url.search = ''; // Clear all existing params

            if (userId !== 'all') {
                url.searchParams.set('user', userId);
            }

            url.searchParams.set('from_date', fromDate);
            url.searchParams.set('to_date', toDate);
            url.searchParams.set('range', rangeType);

            window.location.href = url.toString();
        }

        // Helper function to format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('filterModal');
            if (event.target === modal) {
                closeFilterModal();
            }
        }

        // Update clock every second
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('currentTime').textContent = hours + ':' + minutes + ':' + seconds;
        }
        setInterval(updateClock, 1000);

        // Auto-refresh page every 5 minutes to update status
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
