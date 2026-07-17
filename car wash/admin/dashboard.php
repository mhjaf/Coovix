<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('dashboard');

$conn = getConnection();

// Get today's date
$today = date('Y-m-d');

// Get statistics for today using prepared statements
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE booking_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending' AND booking_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$pendingBookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed' AND booking_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$completedBookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$totalServices = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch_assoc()['count'];

// Get recent bookings for today
$stmt = $conn->prepare("
    SELECT b.*, s.name as service_name, s.name_ku as service_name_ku, s.name_ar as service_name_ar
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.booking_date = ?
    ORDER BY b.booking_time ASC
    LIMIT 5
");
$stmt->bind_param("s", $today);
$stmt->execute();
$recentBookings = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard'); ?> - Coovix Admin</title>
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
                <?php if (hasPermission('dashboard')): ?><a href="dashboard.php" class="active"><?php echo __('dashboard'); ?></a><?php endif; ?>
                <?php if (hasPermission('bookings')): ?><a href="bookings.php"><?php echo __('bookings'); ?></a><?php endif; ?>
                <?php if (hasPermission('reports')): ?><a href="reports.php"><?php echo __('reports'); ?></a><?php endif; ?>
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
                        <a href="?lang=en" class="lang-option <?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇬🇧</span> English
                        </a>
                        <a href="?lang=ku" class="lang-option <?php echo getCurrentLang() === 'ku' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇮🇶</span> کوردی
                        </a>
                        <a href="?lang=ar" class="lang-option <?php echo getCurrentLang() === 'ar' ? 'active' : ''; ?>">
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
                <h1><?php echo __('dashboard'); ?> - <?php echo date('F d, Y'); ?></h1>
                <span><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $totalBookings; ?></h3>
                    <p><?php echo __('todays_bookings'); ?></p>
                </div>
                <div class="stat-card pending">
                    <h3><?php echo $pendingBookings; ?></h3>
                    <p><?php echo __('pending'); ?></p>
                </div>
                <div class="stat-card completed">
                    <h3><?php echo $completedBookings; ?></h3>
                    <p><?php echo __('completed'); ?></p>
                </div>
                <div class="stat-card services">
                    <h3><?php echo $totalServices; ?></h3>
                    <p><?php echo __('active_services'); ?></p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h2><?php echo __('todays_bookings'); ?></h2>
                    <a href="bookings.php" class="btn btn-small"><?php echo __('view_all'); ?></a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo __('customer'); ?></th>
                            <th><?php echo __('service'); ?></th>
                            <th><?php echo __('date'); ?></th>
                            <th><?php echo __('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentBookings->num_rows > 0): ?>
                            <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars(getLocalizedName(['name' => $booking['service_name'], 'name_ku' => $booking['service_name_ku'], 'name_ar' => $booking['service_name_ar']])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo __($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center"><?php echo __('no_bookings_yet'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
