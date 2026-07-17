<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('reports');

$conn = getConnection();

// Get date range filters
$startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Total Revenue (completed bookings only)
$revenueQuery = "
    SELECT SUM(s.price) as total_revenue
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.status = 'completed'
    AND b.booking_date BETWEEN ? AND ?
";
$stmt = $conn->prepare($revenueQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$totalRevenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$stmt->close();

// Total Bookings Count by Status
$statusQuery = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM bookings
    WHERE booking_date BETWEEN ? AND ?
";
$stmt = $conn->prepare($statusQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$statusStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Revenue by Category
$categoryQuery = "
    SELECT
        b.category,
        COUNT(*) as booking_count,
        SUM(s.price) as revenue
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.status = 'completed'
    AND b.booking_date BETWEEN ? AND ?
    GROUP BY b.category
    ORDER BY revenue DESC
";
$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$categoryStats = $stmt->get_result();
$stmt->close();

// Top Services
$servicesQuery = "
    SELECT
        s.name as service_name,
        s.name_ku as service_name_ku,
        s.name_ar as service_name_ar,
        COUNT(*) as booking_count,
        SUM(s.price) as revenue
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.status = 'completed'
    AND b.booking_date BETWEEN ? AND ?
    GROUP BY b.service_id, s.name, s.name_ku, s.name_ar
    ORDER BY booking_count DESC
    LIMIT 10
";
$stmt = $conn->prepare($servicesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topServices = $stmt->get_result();
$stmt->close();

// Daily Revenue for the period
$dailyQuery = "
    SELECT
        b.booking_date,
        COUNT(*) as booking_count,
        SUM(s.price) as daily_revenue
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.status = 'completed'
    AND b.booking_date BETWEEN ? AND ?
    GROUP BY b.booking_date
    ORDER BY b.booking_date DESC
    LIMIT 30
";
$stmt = $conn->prepare($dailyQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$dailyStats = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('reports'); ?> - Coovix Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <?php if (isRTL()): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, sans-serif; }
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Mobile Hamburger -->
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
            <span></span><span></span><span></span>
        </button>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Coovix</h2>
                <span><?php echo __('admin_panel'); ?></span>
            </div>
            <nav class="sidebar-nav">
                <?php if (hasPermission('dashboard')): ?><a href="dashboard.php"><?php echo __('dashboard'); ?></a><?php endif; ?>
                <?php if (hasPermission('bookings')): ?><a href="bookings.php"><?php echo __('bookings'); ?></a><?php endif; ?>
                <?php if (hasPermission('reports')): ?><a href="reports.php" class="active"><?php echo __('reports'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="settings_new.php"><?php echo __('settings'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="work_time.php"><?php echo __('work_time'); ?></a><?php endif; ?>
                <?php if (hasPermission('users')): ?><a href="users.php"><?php echo __('users'); ?></a><?php endif; ?>

                <!-- Language Dropdown -->
                <div class="lang-dropdown" id="langDropdown">
                    <button type="button" class="lang-btn" onclick="toggleLangDropdown()">
                        <span class="lang-icon">🌐</span>
                        <span class="lang-text"><?php echo getLangName(getCurrentLang()); ?></span>
                        <span class="lang-arrow">▼</span>
                    </button>
                    <div class="lang-menu">
                        <a href="?lang=en&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="lang-option <?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇬🇧</span> English
                        </a>
                        <a href="?lang=ku&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="lang-option <?php echo getCurrentLang() === 'ku' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇮🇶</span> کوردی
                        </a>
                        <a href="?lang=ar&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="lang-option <?php echo getCurrentLang() === 'ar' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇸🇦</span> العربية
                        </a>
                    </div>
                </div>

                <a href="logout.php"><?php echo __('logout'); ?></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><?php echo __('reports'); ?></h1>
                <span><?php echo date('F d, Y', strtotime($startDate)); ?> - <?php echo date('F d, Y', strtotime($endDate)); ?></span>
            </header>

            <!-- Date Range Filter -->
            <div class="card">
                <div class="report-filters">
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date"><?php echo __('from_date'); ?></label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="form-group">
                                <label for="end_date"><?php echo __('to_date'); ?></label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="submit" class="btn btn-primary"><?php echo __('apply_filter'); ?></button>
                            </div>
                        </div>
                    </form>
                    <div class="report-quick-filters">
                        <a href="?start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-small">Today</a>
                        <a href="?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-small">Last 7 Days</a>
                        <a href="?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-small">This Month</a>
                        <a href="?start_date=<?php echo date('Y-01-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-small">This Year</a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card completed">
                    <div class="stat-icon">$</div>
                    <div class="stat-info">
                        <h3><?php echo __('total_revenue'); ?></h3>
                        <span class="stat-number"><?php echo number_format($totalRevenue, 0); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">#</div>
                    <div class="stat-info">
                        <h3><?php echo __('total_bookings'); ?></h3>
                        <span class="stat-number"><?php echo number_format($statusStats['total'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-icon">+</div>
                    <div class="stat-info">
                        <h3><?php echo __('completed'); ?></h3>
                        <span class="stat-number"><?php echo number_format($statusStats['completed'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon">!</div>
                    <div class="stat-info">
                        <h3><?php echo __('pending'); ?></h3>
                        <span class="stat-number"><?php echo number_format($statusStats['pending'] ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <!-- Revenue by Category -->
            <div class="card">
                <div class="card-header">
                    <h2><?php echo __('bookings_by_category'); ?></h2>
                </div>
                <div class="category-stats">
                    <?php if ($categoryStats->num_rows > 0): ?>
                        <?php while ($cat = $categoryStats->fetch_assoc()): ?>
                            <div class="category-stat-item">
                                <span class="category-label"><?php echo htmlspecialchars($cat['category'] ?? 'Uncategorized'); ?></span>
                                <div class="category-stat-values">
                                    <span style="color: #6b7280;"><?php echo $cat['booking_count']; ?> <?php echo __('bookings'); ?></span>
                                    <span class="category-value"><?php echo number_format($cat['revenue'], 0); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #6b7280; padding: 20px;"><?php echo __('no_bookings_found'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Services & Daily Revenue -->
            <div class="reports-two-col-grid">
                <!-- Top Services -->
                <div class="card">
                    <div class="card-header">
                        <h2>Top <?php echo __('services'); ?></h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('service'); ?></th>
                                <th><?php echo __('bookings'); ?></th>
                                <th><?php echo __('price'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topServices->num_rows > 0): ?>
                                <?php while ($service = $topServices->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(getLocalizedName(['name' => $service['service_name'], 'name_ku' => $service['service_name_ku'], 'name_ar' => $service['service_name_ar']])); ?></td>
                                        <td><?php echo $service['booking_count']; ?></td>
                                        <td><?php echo number_format($service['revenue'], 0); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center"><?php echo __('no_bookings_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Daily Revenue -->
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo __('total_revenue'); ?></h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('bookings'); ?></th>
                                <th><?php echo __('price'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dailyStats->num_rows > 0): ?>
                                <?php while ($day = $dailyStats->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($day['booking_date'])); ?></td>
                                        <td><?php echo $day['booking_count']; ?></td>
                                        <td><?php echo number_format($day['daily_revenue'], 0); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center"><?php echo __('no_bookings_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Booking Status Summary -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2><?php echo __('bookings_by_status'); ?></h2>
                </div>
                <div class="status-summary-grid">
                    <div style="text-align: center; padding: 20px; background: #dcfce7; border-radius: 12px;">
                        <div style="font-size: 2rem; font-weight: 700; color: #166534;"><?php echo $statusStats['completed'] ?? 0; ?></div>
                        <div style="color: #166534; font-weight: 500;"><?php echo __('completed'); ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #fef3c7; border-radius: 12px;">
                        <div style="font-size: 2rem; font-weight: 700; color: #b45309;"><?php echo $statusStats['pending'] ?? 0; ?></div>
                        <div style="color: #b45309; font-weight: 500;"><?php echo __('pending'); ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #e0e7ff; border-radius: 12px;">
                        <div style="font-size: 2rem; font-weight: 700; color: #4338ca;"><?php echo $statusStats['confirmed'] ?? 0; ?></div>
                        <div style="color: #4338ca; font-weight: 500;"><?php echo __('confirmed'); ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #fef2f2; border-radius: 12px;">
                        <div style="font-size: 2rem; font-weight: 700; color: #991b1b;"><?php echo $statusStats['cancelled'] ?? 0; ?></div>
                        <div style="color: #991b1b; font-weight: 500;"><?php echo __('cancelled'); ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
            document.getElementById('hamburgerBtn').classList.toggle('open');
        }

        function toggleLangDropdown() {
            document.getElementById('langDropdown').classList.toggle('open');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('langDropdown');
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    </script>
</body>
</html>
