<?php
require_once 'config.php';
requireLogin();

// Add missing columns and grant admin user all permissions
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_products TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_expenses TINYINT(1) DEFAULT 0");
$conn->query("UPDATE admin_users SET can_products=1, can_expenses=1 WHERE id=1");

// Check permission - redirect to first available page if no dashboard access
if (!hasPermission('can_dashboard')) {
    if (hasPermission('can_barbers')) {
        header('Location: barbers.php');
    } elseif (hasPermission('can_schedule')) {
        header('Location: worktime.php');
    } elseif (hasPermission('can_bookings')) {
        header('Location: bookings.php');
    } elseif (hasPermission('can_users')) {
        header('Location: users.php');
    } else {
        // No permissions at all - logout
        header('Location: logout.php');
    }
    exit();
}

// Build user filter for non-admin users
$userFilter = "";
$barberUserFilter = "";
$productUserFilter = "";
$isAdmin = hasPermission('can_users'); // Admins have can_users permission

if (!$isAdmin) {
    $currentUserId = $_SESSION['admin_id'];
    $userFilter = " AND br.user_id = $currentUserId";
    $barberUserFilter = " AND user_id = $currentUserId";
    $productUserFilter = " AND sold_by = $currentUserId";
}

// Get statistics (filtered by user for non-admins)
$totalBarbers = $conn->query("SELECT COUNT(*) as count FROM barbers WHERE 1=1" . $barberUserFilter)->fetch_assoc()['count'];
$activeBarbers = $conn->query("SELECT COUNT(*) as count FROM barbers WHERE status='active'" . $barberUserFilter)->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE 1=1" . $userFilter)->fetch_assoc()['count'];
$todayBookings = $conn->query("SELECT COUNT(*) as count FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE booking_date = CURDATE()" . $userFilter)->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) as count FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE b.status='pending'" . $userFilter)->fetch_assoc()['count'];

// Get revenue statistics (filtered by user) - Daily only
$todayRevenue = 0;
$todayBookingRevenue = 0;
$todayProductRevenue = 0;

// Revenue from bookings (today only, use service price if booking price is 0)
$bookingRevenueQuery = "SELECT COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE COALESCE(s.price, 0) END), 0) as total 
                        FROM bookings b 
                        LEFT JOIN barbers br ON b.barber_id = br.id 
                        LEFT JOIN services s ON b.service = s.name 
                        WHERE b.booking_date = CURDATE() 
                        AND b.status IN ('confirmed', 'completed')" . $userFilter;
$todayBookingRevenue = $conn->query($bookingRevenueQuery)->fetch_assoc()['total'] ?? 0;

// Revenue from products (today only)
$todayProductRevenue = 0;

// Check if product_sales table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'product_sales'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $todayProductRevenue = $conn->query("SELECT SUM(total) as total FROM product_sales WHERE sale_date = CURDATE()" . ($productUserFilter ? str_replace('AND sold_by', 'AND user_id', $productUserFilter) : ''))->fetch_assoc()['total'] ?? 0;
}

// Total revenue (today only)
$todayRevenue = $todayBookingRevenue + $todayProductRevenue;

// Get monthly data - show only months that have data, starting from earliest to current
$monthlyData = [];

// Get all months that have bookings or product sales
$monthsQuery = "
    SELECT DISTINCT month_value, month_date FROM (
        SELECT DATE_FORMAT(booking_date, '%Y-%m') as month_value, DATE_FORMAT(booking_date, '%Y-%m-01') as month_date
        FROM bookings b
        LEFT JOIN barbers br ON b.barber_id = br.id
        WHERE b.status != 'cancelled' 
        AND b.barber_id IS NOT NULL" . $userFilter . "
        UNION
        SELECT DATE_FORMAT(sale_date, '%Y-%m') as month_value, DATE_FORMAT(sale_date, '%Y-%m-01') as month_date
        FROM product_sales
        WHERE 1=1" . ($productUserFilter ? str_replace('AND sold_by', ' AND user_id', $productUserFilter) : '') . "
    ) as months
    ORDER BY month_value ASC
";

$monthsResult = $conn->query($monthsQuery);
$availableMonths = [];
while ($row = $monthsResult->fetch_assoc()) {
    $availableMonths[] = $row['month_value'];
}

// Build data for each month that has activity
foreach ($availableMonths as $month) {
    $monthName = date('M Y', strtotime($month . '-01'));
    
    // Bookings revenue for this month (use service price if booking price is 0)
    $bookingRevQuery = "SELECT COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE COALESCE(s.price, 0) END), 0) as total 
                        FROM bookings b 
                        LEFT JOIN barbers br ON b.barber_id = br.id 
                        LEFT JOIN services s ON b.service = s.name 
                        WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = '$month' 
                        AND b.status != 'cancelled'
                        AND b.barber_id IS NOT NULL" . $userFilter;
    $bookingRev = $conn->query($bookingRevQuery)->fetch_assoc()['total'] ?? 0;
    
    // Products revenue for this month
    $productRev = 0;
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $productRevQuery = "SELECT COALESCE(SUM(total), 0) as total FROM product_sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$month'" . ($productUserFilter ? str_replace('AND sold_by', 'AND user_id', $productUserFilter) : '');
        $productRev = $conn->query($productRevQuery)->fetch_assoc()['total'] ?? 0;
    }
    
    // Bookings count for this month
    $bookingCountQuery = "SELECT COUNT(*) as count FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = '$month' AND b.status != 'cancelled' AND b.barber_id IS NOT NULL" . $userFilter;
    $bookingCount = $conn->query($bookingCountQuery)->fetch_assoc()['count'] ?? 0;
    
    // Expenses for this month
    $expensesRev = 0;
    $expensesTableCheck = $conn->query("SHOW TABLES LIKE 'expenses'");
    if ($expensesTableCheck && $expensesTableCheck->num_rows > 0) {
        $expensesQuery = "SELECT COALESCE(SUM(price), 0) as total FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = '$month'" . ($productUserFilter ? str_replace('AND sold_by', ' AND user_id', $productUserFilter) : '');
        $expensesRev = $conn->query($expensesQuery)->fetch_assoc()['total'] ?? 0;
    }
    
    $monthlyData[] = [
        'month' => $monthName,
        'bookings' => $bookingCount,
        'bookingRevenue' => $bookingRev,
        'productRevenue' => $productRev,
        'expenses' => $expensesRev,
        'revenue' => $bookingRev + $productRev
    ];
}

// Get user performance data (all barbers with their stats)
$userPerformanceData = [];

// Get list of barbers (filtered for non-admin users)
$barbersQuery = "SELECT id, name, user_id FROM barbers WHERE 1=1" . $barberUserFilter . " ORDER BY name ASC";
$barbersResult = $conn->query($barbersQuery);

while ($barber = $barbersResult->fetch_assoc()) {
    $barberId = $barber['id'];
    $barberName = $barber['name'];
    
    // Count bookings for this barber (current month)
    $bookingsQuery = "SELECT COUNT(*) as count FROM bookings WHERE barber_id = $barberId AND status != 'cancelled' AND MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())";
    $bookingsCount = $conn->query($bookingsQuery)->fetch_assoc()['count'] ?? 0;
    
    // Booking revenue for this barber (current month, use service price if booking price is 0)
    $bookingRevenueQuery = "SELECT COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE COALESCE(s.price, 0) END), 0) as total 
                            FROM bookings b 
                            LEFT JOIN services s ON b.service = s.name 
                            WHERE b.barber_id = $barberId 
                            AND b.status != 'cancelled' 
                            AND MONTH(b.booking_date) = MONTH(CURDATE()) 
                            AND YEAR(b.booking_date) = YEAR(CURDATE())";
    $bookingRevenue = $conn->query($bookingRevenueQuery)->fetch_assoc()['total'] ?? 0;
    
    // Product revenue for this barber (current month)
    $productRevenue = 0;
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $userId = $barber['user_id'];
        if ($userId !== null && $userId > 0) {
            $productRevenueQuery = "SELECT COALESCE(SUM(total), 0) as total FROM product_sales WHERE user_id = $userId AND MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
            $productRevenue = $conn->query($productRevenueQuery)->fetch_assoc()['total'] ?? 0;
        }
    }
    
    // Total bookings (all time)
    $totalBookingsQuery = "SELECT COUNT(*) as count FROM bookings WHERE barber_id = $barberId AND status != 'cancelled'";
    $totalBookings = $conn->query($totalBookingsQuery)->fetch_assoc()['count'] ?? 0;
    
    // Total revenue (all time - bookings + products)
    $totalBookingRevenueQuery = "SELECT COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE COALESCE(s.price, 0) END), 0) as total 
                                 FROM bookings b 
                                 LEFT JOIN services s ON b.service = s.name 
                                 WHERE b.barber_id = $barberId 
                                 AND b.status != 'cancelled'
                                 AND b.barber_id IS NOT NULL";
    $totalBookingRevenue = $conn->query($totalBookingRevenueQuery)->fetch_assoc()['total'] ?? 0;
    
    $totalProductRevenue = 0;
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $userId = $barber['user_id'];
        if ($userId !== null && $userId > 0) {
            $totalProductRevenueQuery = "SELECT COALESCE(SUM(total), 0) as total FROM product_sales WHERE user_id = $userId";
            $totalProductRevenue = $conn->query($totalProductRevenueQuery)->fetch_assoc()['total'] ?? 0;
        }
    }
    
    $userPerformanceData[] = [
        'name' => $barberName,
        'monthlyBookings' => $bookingsCount,
        'monthlyBookingRevenue' => $bookingRevenue,
        'monthlyProductRevenue' => $productRevenue,
        'monthlyRevenue' => $bookingRevenue + $productRevenue,
        'totalBookings' => $totalBookings,
        'totalRevenue' => $totalBookingRevenue + $totalProductRevenue
    ];
}

// Get recent bookings (filtered by user for non-admins)
$recentBookings = $conn->query("SELECT b.*, br.name as barber_name FROM bookings b LEFT JOIN barbers br ON b.barber_id = br.id WHERE 1=1" . $userFilter . " ORDER BY b.created_at DESC LIMIT 5");

// Get barbers for quick view (filtered by user for non-admins)
$barbers = $conn->query("SELECT * FROM barbers WHERE 1=1" . $barberUserFilter . " ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('dashboard'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
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
                    <li class="active">
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
                        <a href="/index.php" target="_blank">
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
                    <h1><?php echo t('dashboard'); ?></h1>
                </div>
                <div class="header-right">
                    <div class="admin-user">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars(getAdminName()); ?></span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($todayRevenue, 0); ?></h3>
                            <p><?php echo t('todays_revenue'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card monthly">
                        <div class="stat-icon">
                            <i class="fas fa-cut"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($todayBookingRevenue, 0); ?></h3>
                            <p><?php echo t('todays_booking_revenue'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card bookings">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todayBookings; ?></h3>
                            <p><?php echo t('today_bookings'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingBookings; ?></h3>
                            <p><?php echo t('pending_bookings'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card barbers">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $activeBarbers; ?> / <?php echo $totalBarbers; ?></h3>
                            <p><?php echo t('active_barbers'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card total-revenue">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($todayProductRevenue, 0); ?></h3>
                            <p><?php echo t('todays_product_revenue'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- User Performance Summary Section -->
                <div class="dashboard-card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> <?php echo t('user_performance_summary'); ?></h2>
                        <span style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                            <i class="fas fa-calendar-alt"></i> <?php echo date('F Y'); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><?php echo t('barber_name'); ?></th>
                                        <th><?php echo t('monthly_bookings'); ?></th>
                                        <th><?php echo t('booking_revenue'); ?></th>
                                        <th><?php echo t('product_revenue'); ?></th>
                                        <th><?php echo t('monthly_total'); ?></th>
                                        <th><?php echo t('all_time_bookings'); ?></th>
                                        <th><?php echo t('all_time_revenue'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userPerformanceData as $data): ?>
                                    <tr>
                                        <td data-label="<?php echo t('barber_name'); ?>"><strong><?php echo htmlspecialchars($data['name']); ?></strong></td>
                                        <td data-label="<?php echo t('monthly_bookings'); ?>"><?php echo number_format($data['monthlyBookings']); ?></td>
                                        <td data-label="<?php echo t('booking_revenue'); ?>"><?php echo number_format($data['monthlyBookingRevenue'], 0); ?></td>
                                        <td data-label="<?php echo t('product_revenue'); ?>"><?php echo number_format($data['monthlyProductRevenue'], 0); ?></td>
                                        <td data-label="<?php echo t('monthly_total'); ?>"><?php echo number_format($data['monthlyRevenue'], 0); ?></td>
                                        <td data-label="<?php echo t('all_time_bookings'); ?>"><?php echo number_format($data['totalBookings']); ?></td>
                                        <td data-label="<?php echo t('all_time_revenue'); ?>"><?php echo number_format($data['totalRevenue'], 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td data-label=""><?php echo t('total'); ?></td>
                                        <td data-label="<?php echo t('monthly_bookings'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'monthlyBookings'))); ?></td>
                                        <td data-label="<?php echo t('booking_revenue'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'monthlyBookingRevenue')), 0); ?></td>
                                        <td data-label="<?php echo t('product_revenue'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'monthlyProductRevenue')), 0); ?></td>
                                        <td data-label="<?php echo t('monthly_total'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'monthlyRevenue')), 0); ?></td>
                                        <td data-label="<?php echo t('all_time_bookings'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'totalBookings'))); ?></td>
                                        <td data-label="<?php echo t('all_time_revenue'); ?>"><?php echo number_format(array_sum(array_column($userPerformanceData, 'totalRevenue')), 0); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Monthly Data Section -->
                <div class="dashboard-card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-bar"></i> <?php echo t('monthly_performance'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><?php echo t('month'); ?></th>
                                        <th><?php echo t('bookings'); ?></th>
                                        <th><?php echo t('booking_revenue'); ?></th>
                                        <th><?php echo t('product_revenue'); ?></th>
                                        <th><?php echo t('expenses'); ?></th>
                                        <th><?php echo t('total_revenue'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthlyData as $data): ?>
                                    <tr>
                                        <td data-label="<?php echo t('month'); ?>"><strong><?php echo $data['month']; ?></strong></td>
                                        <td data-label="<?php echo t('bookings'); ?>"><?php echo number_format($data['bookings']); ?></td>
                                        <td data-label="<?php echo t('booking_revenue'); ?>"><?php echo number_format($data['bookingRevenue'], 0); ?></td>
                                        <td data-label="<?php echo t('product_revenue'); ?>"><?php echo number_format($data['productRevenue'], 0); ?></td>
                                        <td data-label="<?php echo t('expenses'); ?>" style="color: #dc3545;"><?php echo number_format($data['expenses'], 0); ?></td>
                                        <td data-label="<?php echo t('total_revenue'); ?>"><?php echo number_format($data['revenue'], 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td data-label=""><?php echo t('total'); ?></td>
                                        <td data-label="<?php echo t('bookings'); ?>"><?php echo number_format(array_sum(array_column($monthlyData, 'bookings'))); ?></td>
                                        <td data-label="<?php echo t('booking_revenue'); ?>"><?php echo number_format(array_sum(array_column($monthlyData, 'bookingRevenue')), 0); ?></td>
                                        <td data-label="<?php echo t('product_revenue'); ?>"><?php echo number_format(array_sum(array_column($monthlyData, 'productRevenue')), 0); ?></td>
                                        <td data-label="<?php echo t('expenses'); ?>" style="color: #dc3545;"><?php echo number_format(array_sum(array_column($monthlyData, 'expenses')), 0); ?></td>
                                        <td data-label="<?php echo t('total_revenue'); ?>"><?php echo number_format(array_sum(array_column($monthlyData, 'revenue')), 0); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
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
    </script>
</body>
</html>
