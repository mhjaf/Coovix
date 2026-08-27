<?php
require_once 'config.php';
requireLogin();

// Check permission
if (!hasPermission('can_users')) {
    header('Location: index.php');
    exit();
}

// Get month filter with validation
$monthFilter = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : '';

// Build month filter condition
$bookingMonthFilter = "";
$productMonthFilter = "";
$productMonthFilterNoAlias = "";
if ($monthFilter) {
    $bookingMonthFilter = " AND DATE_FORMAT(b.booking_date, '%Y-%m') = '$monthFilter'";
    $productMonthFilter = " AND DATE_FORMAT(ps.sale_date, '%Y-%m') = '$monthFilter'";
    $productMonthFilterNoAlias = " AND DATE_FORMAT(sale_date, '%Y-%m') = '$monthFilter'";
}

// Get all users with their statistics
$usersQuery = "
    SELECT 
        u.id,
        u.username,
        u.status,
        COUNT(DISTINCT b.id) as completed_bookings,
        COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE s.price END), 0) as booking_revenue,
        COALESCE(product_stats.product_revenue, 0) as product_revenue,
        COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE s.price END), 0) + COALESCE(product_stats.product_revenue, 0) as total_revenue
    FROM admin_users u
    LEFT JOIN barbers br ON u.id = br.user_id
    LEFT JOIN bookings b ON br.id = b.barber_id AND b.status = 'completed' $bookingMonthFilter
    LEFT JOIN services s ON b.service = s.name
    LEFT JOIN (
        SELECT ps.user_id, SUM(ps.total) as product_revenue
        FROM product_sales ps
        WHERE 1=1 $productMonthFilter
        GROUP BY ps.user_id
    ) as product_stats ON u.id = product_stats.user_id
    GROUP BY u.id, u.username, u.status, product_stats.product_revenue
    ORDER BY total_revenue DESC
";

$users = $conn->query($usersQuery);

// Get totals
$totalsQuery = "
    SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        COALESCE(SUM(CASE WHEN b.price > 0 THEN b.price ELSE s.price END), 0) as total_booking_revenue,
        COALESCE((SELECT SUM(total) FROM product_sales WHERE 1=1 $productMonthFilterNoAlias), 0) as total_product_revenue
    FROM bookings b
    LEFT JOIN services s ON b.service = s.name
    WHERE b.status = 'completed' $bookingMonthFilter
";
$totals = $conn->query($totalsQuery)->fetch_assoc();
$grandTotal = $totals['total_booking_revenue'] + $totals['total_product_revenue'];

// Get available months from bookings and product sales
$availableMonthsQuery = "
    SELECT DISTINCT month_value, month_date FROM (
        SELECT DATE_FORMAT(booking_date, '%Y-%m') as month_value, DATE_FORMAT(booking_date, '%Y-%m-01') as month_date
        FROM bookings WHERE status = 'completed'
        UNION
        SELECT DATE_FORMAT(sale_date, '%Y-%m') as month_value, DATE_FORMAT(sale_date, '%Y-%m-01') as month_date
        FROM product_sales
    ) as months
    ORDER BY month_value DESC
";
$availableMonthsResult = $conn->query($availableMonthsQuery);
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
    <title><?php echo t('user_reports'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .report-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .report-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table thead {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
        }
        .report-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
        }
        .report-table tbody tr:hover {
            background: #f8f9fa;
        }
        .report-table tbody tr:last-child td {
            border-bottom: none;
        }
        .report-table tfoot {
            background: #f8f9fa;
            font-weight: 700;
            border-top: 3px solid #FF6B35;
        }
        .report-table tfoot td {
            padding: 18px 15px;
            font-size: 1rem;
            color: #333;
        }
        .revenue-cell {
            color: #FF6B35;
            font-weight: 700;
            font-size: 1.05rem;
        }
        .total-cell {
            color: #28a745;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .user-name {
            font-weight: 600;
            color: #333;
        }
        .status-active {
            color: #28a745;
            font-weight: 500;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: 500;
        }
        .number-cell {
            text-align: center;
            font-weight: 600;
            color: #666;
        }
        .excel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .excel-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        @media print {
            .sidebar, .main-header, .export-btn {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .excel-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .excel-header h2 {
                font-size: 1.2rem;
            }
            
            .report-table {
                border-radius: 8px;
            }
            
            .report-table table {
                display: block;
            }
            
            .report-table thead {
                display: none;
            }
            
            .report-table tbody {
                display: block;
            }
            
            .report-table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 2px solid #f0f0f0;
                border-radius: 12px;
                overflow: hidden;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .report-table tbody tr:hover {
                background: white;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .report-table tbody td {
                display: block;
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
                text-align: right !important;
                position: relative;
                padding-left: 50%;
                min-height: 45px;
            }
            
            .report-table tbody td:last-child {
                border-bottom: none;
            }
            
            .report-table tbody td::before {
                content: attr(data-label) !important;
                font-weight: 600 !important;
                color: #666 !important;
                text-transform: uppercase !important;
                font-size: 0.75rem !important;
                text-align: left !important;
                position: absolute !important;
                left: 15px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                width: 45% !important;
                display: block !important;
            }
            
            .report-table tbody td:first-child {
                background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
                color: white;
                font-weight: 700;
                font-size: 1.1rem;
            }
            
            .report-table tbody td:first-child::before {
                color: rgba(255,255,255,0.95) !important;
                font-weight: 700 !important;
                display: block !important;
            }
            
            .report-table tfoot {
                display: block;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .report-table tfoot tr {
                display: block;
            }
            
            .report-table tfoot td {
                display: block;
                padding: 12px 15px;
                border-bottom: 1px solid #e0e0e0;
                text-align: right !important;
                position: relative;
                padding-left: 50%;
                min-height: 45px;
            }
            
            .report-table tfoot td:last-child {
                border-bottom: none;
            }
            
            .report-table tfoot td::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                color: #333 !important;
                text-transform: uppercase !important;
                font-size: 0.75rem !important;
                position: absolute !important;
                left: 15px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                width: 45% !important;
                display: block !important;
            }
            
            .report-table tfoot td[colspan="3"] {
                display: none;
            }
            
            .number-cell,
            .revenue-cell,
            .total-cell {
                text-align: right !important;
            }
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
                    <li class="active">
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
                    <h1><?php echo t('user_performance_reports'); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="openFilterModal()" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> <?php echo t('filter_by_month'); ?>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Print Report Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <button onclick="window.print()" class="btn btn-success" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-print"></i> <?php echo t('print_report'); ?>
                    </button>
                </div>
                <?php if ($monthFilter): ?>
                <div class="filter-container" style="margin-bottom: 15px;">
                    <span class="filter-badge" style="background: linear-gradient(135deg, #17a2b8 0%, #3fc3d8 100%); color: white; padding: 8px 15px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('F Y', strtotime($monthFilter . '-01')); ?>
                        <a href="user-reports.php" style="color: white; margin-left: 5px; text-decoration: none; font-weight: bold;">×</a>
                    </span>
                    <a href="user-reports.php" class="btn btn-sm btn-secondary" style="margin-left: 10px;">
                        <i class="fas fa-times"></i> <?php echo t('clear_filter'); ?>
                    </a>
                </div>
                <?php endif; ?>
                <!-- Excel-style Header -->
                <div class="excel-header">
                    <h2><i class="fas fa-file-excel"></i> <?php echo t('user_performance_summary'); ?></h2>
                    <div>
                        <span style="color: #666;"><?php echo t('generated'); ?>: <?php echo date('F j, Y'); ?></span>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo t('user_id'); ?></th>
                                <th><?php echo t('username'); ?></th>
                                <th><?php echo t('status'); ?></th>
                                <th style="text-align: center;"><?php echo t('completed_customers'); ?></th>
                                <th style="text-align: right;"><?php echo t('booking_revenue'); ?> (IQD)</th>
                                <th style="text-align: right;"><?php echo t('product_revenue'); ?> (IQD)</th>
                                <th style="text-align: right;"><?php echo t('total_revenue'); ?> (IQD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td class="number-cell" data-label="<?php echo t('user_id'); ?>">#<?php echo $user['id']; ?></td>
                                    <td class="user-name" data-label="<?php echo t('username'); ?>"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td data-label="<?php echo t('status'); ?>">
                                        <span class="status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="number-cell" data-label="<?php echo t('completed_customers'); ?>"><?php echo $user['completed_bookings']; ?></td>
                                    <td style="text-align: right;" class="revenue-cell" data-label="<?php echo t('booking_revenue'); ?>">
                                        <?php echo number_format($user['booking_revenue'], 0); ?> IQD
                                    </td>
                                    <td style="text-align: right;" class="revenue-cell" data-label="<?php echo t('product_revenue'); ?>">
                                        <?php echo number_format($user['product_revenue'], 0); ?> IQD
                                    </td>
                                    <td style="text-align: right;" class="total-cell" data-label="<?php echo t('total_revenue'); ?>">
                                        <?php echo number_format($user['total_revenue'], 0); ?> IQD
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                        <?php echo t('no_user_data'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; padding-right: 30px;">
                                    <strong>TOTAL:</strong>
                                </td>
                                <td class="number-cell" style="font-size: 1.1rem;" data-label="<?php echo t('completed_customers'); ?>">
                                    <?php echo $totals['total_bookings']; ?>
                                </td>
                                <td style="text-align: right; color: #FF6B35; font-size: 1.1rem;" data-label="<?php echo t('booking_revenue'); ?>">
                                    <?php echo number_format($totals['total_booking_revenue'], 0); ?> IQD
                                </td>
                                <td style="text-align: right; color: #FF6B35; font-size: 1.1rem;" data-label="<?php echo t('product_revenue'); ?>">
                                    <?php echo number_format($totals['total_product_revenue'], 0); ?> IQD
                                </td>
                                <td style="text-align: right; color: #28a745; font-size: 1.2rem;" data-label="<?php echo t('total_revenue'); ?>">
                                    <?php echo number_format($grandTotal, 0); ?> IQD
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7);">
        <div class="modal-content" style="background: white; margin: 10% auto; padding: 0; border-radius: 16px; width: 90%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 25px 30px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; border-radius: 16px 16px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 600;"><i class="fas fa-filter"></i> <?php echo t('filter_reports'); ?></h3>
                <span class="close" onclick="closeFilterModal()" style="color: white; font-size: 32px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div class="form-group">
                    <label for="monthFilter"><i class="fas fa-calendar"></i> <?php echo t('select_month'); ?></label>
                    <select id="monthFilter" class="form-control">
                        <option value=""><?php echo t('all_months'); ?></option>
                        <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo $month['month_value']; ?>" <?php echo $monthFilter === $month['month_value'] ? 'selected' : ''; ?>>
                            <?php echo date('F Y', strtotime($month['month_date'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button onclick="applyFilter()" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-check"></i> <?php echo t('apply_filter'); ?>
                    </button>
                </div>
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

        function openFilterModal() {
            document.getElementById('filterModal').style.display = 'flex';
        }

        function closeFilterModal() {
            document.getElementById('filterModal').style.display = 'none';
        }

        function applyFilter() {
            const month = document.getElementById('monthFilter').value;
            if (month) {
                window.location.href = 'user-reports.php?month=' + month;
            } else {
                window.location.href = 'user-reports.php';
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('filterModal');
            if (event.target === modal) {
                closeFilterModal();
            }
        };
    </script>
</body>
</html>
