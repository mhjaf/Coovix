<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('settings');

$conn = getConnection();
$message = '';

// Create work_hours table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS work_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week INT NOT NULL,
        day_name VARCHAR(20) NOT NULL,
        is_working TINYINT(1) DEFAULT 1,
        open_time TIME DEFAULT '08:00:00',
        close_time TIME DEFAULT '20:00:00',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_day (day_of_week)
    )
");

// Initialize default working hours if table is empty
$checkData = $conn->query("SELECT COUNT(*) as count FROM work_hours")->fetch_assoc();
if ($checkData['count'] == 0) {
    $defaultHours = [
        [0, 'Sunday', 1, '08:00:00', '20:00:00'],
        [1, 'Monday', 1, '08:00:00', '20:00:00'],
        [2, 'Tuesday', 1, '08:00:00', '20:00:00'],
        [3, 'Wednesday', 1, '08:00:00', '20:00:00'],
        [4, 'Thursday', 1, '08:00:00', '20:00:00'],
        [5, 'Friday', 0, '00:00:00', '00:00:00'],
        [6, 'Saturday', 1, '08:00:00', '20:00:00']
    ];
    
    foreach ($defaultHours as $day) {
        $stmt = $conn->prepare("INSERT INTO work_hours (day_of_week, day_name, is_working, open_time, close_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $day[0], $day[1], $day[2], $day[3], $day[4]);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $dayOfWeek = (int)$_POST['day_of_week'];
    $isWorking = isset($_POST['is_working']) ? 1 : 0;
    $openTime = $_POST['open_time'];
    $closeTime = $_POST['close_time'];
    
    $stmt = $conn->prepare("UPDATE work_hours SET is_working = ?, open_time = ?, close_time = ? WHERE day_of_week = ?");
    $stmt->bind_param("issi", $isWorking, $openTime, $closeTime, $dayOfWeek);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Working hours updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-error">Error updating working hours.</div>';
    }
    $stmt->close();
}

// Get all working hours
$workHours = $conn->query("SELECT * FROM work_hours ORDER BY day_of_week");

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('work_time'); ?> - Coovix Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <?php if (isRTL()): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, sans-serif; }
    </style>
    <?php endif; ?>
    <style>
        .work-time-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        .day-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        .day-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a1a;
        }
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.working {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.off {
            background: #fee2e2;
            color: #991b1b;
        }
        .time-info {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 20px;
        }
        .time-item {
            flex: 1;
        }
        .time-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .time-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        .form-group input[type="time"] {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-group input[type="time"]:focus {
            outline: none;
            border-color: #2563eb;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-save {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .edit-form {
            display: none;
            padding-top: 16px;
            border-top: 2px solid #f0f0f0;
            margin-top: 16px;
        }
        .edit-form.active {
            display: block;
        }
        .btn-edit {
            padding: 8px 16px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-edit:hover {
            background: #e5e7eb;
        }
    </style>
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
                <?php if (hasPermission('reports')): ?><a href="reports.php"><?php echo __('reports'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="settings_new.php"><?php echo __('settings'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="work_time.php" class="active"><?php echo __('work_time'); ?></a><?php endif; ?>
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
                <h1><?php echo __('work_time'); ?> Management</h1>
                <span>Manage working days and hours</span>
            </header>

            <?php echo $message; ?>

            <div class="work-time-grid">
                <?php while ($day = $workHours->fetch_assoc()): ?>
                <div class="day-card">
                    <div class="day-header">
                        <h3 class="day-name"><?php echo $day['day_name']; ?></h3>
                        <div class="day-header-actions">
                            <span class="status-badge <?php echo $day['is_working'] ? 'working' : 'off'; ?>">
                                <?php echo $day['is_working'] ? '✓ Working' : '✕ Day Off'; ?>
                            </span>
                            <button class="btn-edit" onclick="toggleEditForm(<?php echo $day['day_of_week']; ?>)">Edit</button>
                        </div>
                    </div>
                    
                    <?php if ($day['is_working']): ?>
                    <div class="time-info">
                        <div class="time-item">
                            <div class="time-label">Open Time</div>
                            <div class="time-value"><?php echo date('h:i A', strtotime($day['open_time'])); ?></div>
                        </div>
                        <div style="font-size: 2rem; color: #d1d5db;">→</div>
                        <div class="time-item">
                            <div class="time-label">Close Time</div>
                            <div class="time-value"><?php echo date('h:i A', strtotime($day['close_time'])); ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #999;">
                        <p style="font-size: 1.1rem;">Not accepting bookings on this day</p>
                    </div>
                    <?php endif; ?>

                    <!-- Edit Form -->
                    <div class="edit-form" id="editForm<?php echo $day['day_of_week']; ?>">
                        <form method="POST" action="">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="day_of_week" value="<?php echo $day['day_of_week']; ?>">
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="working<?php echo $day['day_of_week']; ?>" name="is_working" <?php echo $day['is_working'] ? 'checked' : ''; ?>>
                                <label for="working<?php echo $day['day_of_week']; ?>">This is a working day</label>
                            </div>

                            <div class="form-group">
                                <label>Open Time</label>
                                <input type="time" name="open_time" value="<?php echo $day['open_time']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Close Time</label>
                                <input type="time" name="close_time" value="<?php echo $day['close_time']; ?>" required>
                            </div>

                            <button type="submit" class="btn-save">💾 Save Changes</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
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

        function toggleEditForm(dayId) {
            const form = document.getElementById('editForm' + dayId);
            form.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('langDropdown');
            if (!event.target.closest('.lang-dropdown')) {
                dropdown.classList.remove('open');
            }
        });
    </script>
</body>
</html>
