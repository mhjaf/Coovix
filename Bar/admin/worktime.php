<?php
require_once 'config.php';
requireLogin();

// Check permission
if (!hasPermission('can_schedule')) {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';
$currentUserId = $_SESSION['admin_id'];

// Get barbers based on user permissions
if (!hasPermission('can_users')) {
    // Non-admin user: only show their linked barber
    $stmt = $conn->prepare("SELECT id, name FROM barbers WHERE status = 'active' AND user_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $allBarbersResult = $stmt->get_result();
} else {
    $allBarbersResult = $conn->query("SELECT id, name FROM barbers WHERE status = 'active' ORDER BY name ASC");
}
$allBarbers = [];
while ($row = $allBarbersResult->fetch_assoc()) {
    $allBarbers[] = $row;
}

// Check if barber is selected via dropdown or linked to user
$selectedBarberId = isset($_POST['barber_id']) ? intval($_POST['barber_id']) : (isset($_GET['barber_id']) ? intval($_GET['barber_id']) : 0);

// Try to get barber linked to current user first
$stmtLinked = $conn->prepare("SELECT id, name FROM barbers WHERE user_id = ? AND status = 'active'");
$stmtLinked->bind_param("i", $currentUserId);
$stmtLinked->execute();
$barberResult = $stmtLinked->get_result();
$linkedBarber = $barberResult->fetch_assoc();

// Use selected barber or linked barber or first available
if ($selectedBarberId > 0) {
    foreach ($allBarbers as $b) {
        if ($b['id'] == $selectedBarberId) {
            $currentBarber = $b;
            break;
        }
    }
} elseif ($linkedBarber) {
    $currentBarber = $linkedBarber;
} elseif (count($allBarbers) > 0) {
    $currentBarber = $allBarbers[0];
} else {
    $currentBarber = null;
}

$barberId = $currentBarber['id'] ?? 0;

// Generate time options (6:00 AM to 11:00 PM in 30-minute intervals)
function generateTimeOptions() {
    $options = [];
    for ($hour = 6; $hour <= 23; $hour++) {
        foreach (['00', '30'] as $min) {
            $time24 = sprintf('%02d:%s', $hour, $min);
            $time12 = date('g:i A', strtotime($time24));
            $options[$time24] = $time12;
        }
    }
    return $options;
}
$timeOptions = generateTimeOptions();

// Days of week (Saturday to Friday)
$daysOfWeek = [
    6 => t('saturday'),
    0 => t('sunday'),
    1 => t('monday'),
    2 => t('tuesday'),
    3 => t('wednesday'),
    4 => t('thursday'),
    5 => t('friday')
];

// Handle Save Schedule
if (isset($_POST['save_schedule'])) {
    $saveBarberId = intval($_POST['barber_id'] ?? $barberId);
    if ($saveBarberId > 0) {
        $successCount = 0;

    foreach ($daysOfWeek as $dayNum => $dayName) {
        $isDayOff = isset($_POST['day_off_' . $dayNum]) ? 1 : 0;
        $startTime = !empty($_POST['start_time_' . $dayNum]) ? $_POST['start_time_' . $dayNum] . ':00' : null;
        $endTime = !empty($_POST['end_time_' . $dayNum]) ? $_POST['end_time_' . $dayNum] . ':00' : null;
        $breakTime = !empty($_POST['break_time_' . $dayNum]) ? $_POST['break_time_' . $dayNum] : null;

        if ($isDayOff) {
            $sql = "INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, break_time, is_day_off)
                    VALUES (?, ?, '00:00:00', '00:00:00', NULL, 1)
                    ON DUPLICATE KEY UPDATE start_time='00:00:00', end_time='00:00:00', break_time=NULL, is_day_off=1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $saveBarberId, $dayNum);
        } else if ($startTime && $endTime) {
            $sql = "INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, break_time, is_day_off)
                    VALUES (?, ?, ?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE start_time=?, end_time=?, break_time=?, is_day_off=0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissssss", $saveBarberId, $dayNum, $startTime, $endTime, $breakTime, $startTime, $endTime, $breakTime);
        } else {
            continue;
        }

        if ($stmt->execute()) {
            $successCount++;
        }
        $stmt->close();
    }

    if ($successCount > 0) {
        $message = "Schedule saved successfully!";
        $messageType = "success";
        // Update barberId to reload correct schedule
        $barberId = $saveBarberId;
    }
    }
}

// Get current schedule
$scheduleData = [];
if ($barberId) {
    $stmtSched = $conn->prepare("SELECT * FROM barber_schedules WHERE barber_id = ?");
    $stmtSched->bind_param("i", $barberId);
    $stmtSched->execute();
    $result = $stmtSched->get_result();
    while ($row = $result->fetch_assoc()) {
        $scheduleData[$row['day_of_week']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('my_schedule'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .schedule-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .schedule-header-card {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .schedule-header-card h2 {
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .schedule-header-card h2 i {
            color: #FF6B35;
        }
        .schedule-header-card p {
            margin: 10px 0 0;
            opacity: 0.7;
        }
        .barber-name {
            color: #FF6B35;
            font-weight: 600;
        }
        .barber-selector {
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .barber-selector label {
            font-size: 0.95rem;
            opacity: 0.8;
        }
        .barber-dropdown {
            padding: 10px 20px;
            border: 2px solid rgba(255,107,53,0.5);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            min-width: 200px;
        }
        .barber-dropdown option {
            background: #1a1a1a;
            color: white;
        }
        .days-container {
            background: white;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .day-row {
            display: grid;
            grid-template-columns: 140px 1fr 1fr 1fr 80px;
            gap: 15px;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }
        .day-row:last-child {
            border-bottom: none;
        }
        .day-row:hover {
            background: #fafafa;
        }
        .day-row.day-off {
            background: #f8f9fa;
        }
        .day-row.day-off .day-name {
            opacity: 0.5;
        }
        .day-row.day-off .time-group {
            opacity: 0.3;
        }
        .day-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .day-name i {
            color: #FF6B35;
            font-size: 1rem;
        }
        .time-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .time-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            font-weight: 500;
        }
        .time-select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23FF6B35' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }
        .time-select:hover {
            border-color: #FF6B35;
        }
        .time-select:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 4px rgba(255,107,53,0.15);
        }
        .time-select:disabled {
            background-color: #f1f1f1;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .day-off-toggle {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .day-off-toggle input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #FF6B35;
        }
        .day-off-toggle label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            cursor: pointer;
        }
        .save-btn-container {
            padding: 25px 30px;
            background: #f8f9fa;
        }
        .save-btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #FF6B35, #e55a2b);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,107,53,0.4);
        }
        .no-barber-card {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
        }
        .no-barber-card i {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 20px;
        }
        .no-barber-card h3 {
            margin: 0 0 10px;
            font-size: 1.5rem;
        }
        .no-barber-card p {
            color: #666;
            margin: 0;
        }
        @media (max-width: 768px) {
            .day-row {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 20px;
            }
            .day-name {
                padding-bottom: 10px;
                border-bottom: 2px solid #FF6B35;
                justify-content: center;
            }
            .time-group {
                text-align: center;
            }
            .day-off-toggle {
                flex-direction: row;
                justify-content: center;
                gap: 10px;
                padding-top: 10px;
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
                    <li class="active">
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
                    <h1><?php echo t('my_schedule'); ?></h1>
                </div>
                <div class="header-right">
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
                <?php endif; ?>

                <?php if ($currentBarber): ?>
                <div class="schedule-container">
                    <form method="POST">
                        <input type="hidden" name="barber_id" value="<?php echo $barberId; ?>">
                        <div class="schedule-header-card">
                            <h2><i class="fas fa-calendar-week"></i> <?php echo t('weekly_schedule'); ?></h2>
                            <?php if (count($allBarbers) > 1): ?>
                            <div class="barber-selector">
                                <label><?php echo t('select_barber'); ?>:</label>
                                <select name="barber_id" onchange="this.form.submit()" class="barber-dropdown">
                                    <?php foreach ($allBarbers as $barber): ?>
                                    <option value="<?php echo $barber['id']; ?>" <?php echo $barber['id'] == $barberId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($barber['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <p><?php echo t('set_working_hours'); ?> <span class="barber-name"><?php echo htmlspecialchars($currentBarber['name']); ?></span></p>
                            <?php endif; ?>
                        </div>

                        <div class="days-container">
                            <?php foreach ($daysOfWeek as $dayNum => $dayName):
                                $dayData = $scheduleData[$dayNum] ?? null;
                                $isDayOff = $dayData && $dayData['is_day_off'];
                                $startTime = $dayData && !$isDayOff ? date('H:i', strtotime($dayData['start_time'])) : '';
                                $endTime = $dayData && !$isDayOff ? date('H:i', strtotime($dayData['end_time'])) : '';
                            ?>
                            <div class="day-row <?php echo $isDayOff ? 'day-off' : ''; ?>" id="day-<?php echo $dayNum; ?>">
                                <div class="day-name">
                                    <i class="fas fa-calendar-day"></i>
                                    <?php echo $dayName; ?>
                                </div>

                                <div class="time-group">
                                    <span class="time-label"><?php echo t('start_time'); ?></span>
                                    <select name="start_time_<?php echo $dayNum; ?>" class="time-select start-select" <?php echo $isDayOff ? 'disabled' : ''; ?>>
                                        <option value="">-- <?php echo t('select_date'); ?> --</option>
                                        <?php foreach ($timeOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $startTime === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="time-group">
                                    <span class="time-label"><?php echo t('end_time'); ?></span>
                                    <select name="end_time_<?php echo $dayNum; ?>" class="time-select end-select" <?php echo $isDayOff ? 'disabled' : ''; ?>>
                                        <option value="">-- <?php echo t('select_date'); ?> --</option>
                                        <?php foreach ($timeOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $endTime === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="time-group">
                                    <span class="time-label"><?php echo t('break_start'); ?></span>
                                    <select name="break_time_<?php echo $dayNum; ?>" class="time-select break-select" <?php echo $isDayOff ? 'disabled' : ''; ?>>
                                        <option value=""><?php echo t('no_break'); ?></option>
                                        <?php 
                                        $breakOptions = [
                                            '11:00-12:00' => '11:00 - 12:00',
                                            '12:00-13:00' => '12:00 - 13:00',
                                            '13:00-14:00' => '13:00 - 14:00',
                                            '14:00-15:00' => '14:00 - 15:00',
                                            '15:00-16:00' => '15:00 - 16:00'
                                        ];
                                        $breakTime = $dayData && !$isDayOff ? $dayData['break_time'] : '';
                                        foreach ($breakOptions as $value => $label): 
                                        ?>
                                        <option value="<?php echo $value; ?>" <?php echo $breakTime === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="day-off-toggle">
                                    <input type="checkbox"
                                           id="day_off_<?php echo $dayNum; ?>"
                                           name="day_off_<?php echo $dayNum; ?>"
                                           <?php echo $isDayOff ? 'checked' : ''; ?>
                                           onchange="toggleDayOff(<?php echo $dayNum; ?>)">
                                    <label for="day_off_<?php echo $dayNum; ?>"><?php echo t('day_off'); ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="save-btn-container">
                                <button type="submit" name="save_schedule" class="save-btn">
                                    <i class="fas fa-save"></i> <?php echo t('save_schedule'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="no-barber-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3><?php echo t('no_barbers_found'); ?></h3>
                    <p><?php echo t('no_barbers_add_first'); ?> <a href="barbers.php" style="color: #FF6B35;"><?php echo t('barbers_page'); ?></a> <?php echo t('page'); ?>.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
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

        function toggleDayOff(dayNum) {
            const row = document.getElementById('day-' + dayNum);
            const checkbox = document.getElementById('day_off_' + dayNum);
            const selects = row.querySelectorAll('.time-select');

            if (checkbox.checked) {
                row.classList.add('day-off');
                selects.forEach(select => {
                    select.disabled = true;
                    select.value = '';
                });
            } else {
                row.classList.remove('day-off');
                selects.forEach(select => select.disabled = false);
            }
        }
    </script>
</body>
</html>
