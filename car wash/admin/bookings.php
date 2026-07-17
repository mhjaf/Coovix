<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('bookings');

$conn = getConnection();
$message = '';
$messageType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCSRF();
    if ($_POST['action'] === 'update_status') {
        $bookingId = (int)$_POST['booking_id'];
        $status = sanitize($_POST['status']);

        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $bookingId);

        if ($stmt->execute()) {
            $message = 'Booking status updated successfully';
            $messageType = 'success';
        } else {
            $message = 'Error updating booking status';
            $messageType = 'error';
        }
        $stmt->close();
    }

    if ($_POST['action'] === 'update_booking') {
        $bookingId = (int)$_POST['booking_id'];
        $customerName = sanitize($_POST['customer_name']);
        $phone = sanitize($_POST['phone']);
        $bookingDate = sanitize($_POST['booking_date']);
        $bookingTime = sanitize($_POST['booking_time']);
        $notes = sanitize($_POST['notes']);

        $stmt = $conn->prepare("UPDATE bookings SET customer_name = ?, phone = ?, booking_date = ?, booking_time = ?, notes = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $customerName, $phone, $bookingDate, $bookingTime, $notes, $bookingId);

        if ($stmt->execute()) {
            $message = 'Booking updated successfully';
            $messageType = 'success';
        } else {
            $message = 'Error updating booking';
            $messageType = 'error';
        }
        $stmt->close();
    }

    if ($_POST['action'] === 'delete') {
        $bookingId = (int)$_POST['booking_id'];

        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $bookingId);

        if ($stmt->execute()) {
            $message = 'Booking deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Error deleting booking';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFilter = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');

// Calculate total completed price
$totalCompletedQuery = "
    SELECT SUM(s.price) as total_completed
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.booking_date = '" . $conn->real_escape_string($dateFilter) . "'
    AND b.status = 'completed'
";
$totalResult = $conn->query($totalCompletedQuery);
$totalCompleted = 0;
if ($totalResult && $totalResult->num_rows > 0) {
    $row = $totalResult->fetch_assoc();
    $totalCompleted = $row['total_completed'] ? $row['total_completed'] : 0;
}

// Build query
$query = "
    SELECT b.*, s.name as service_name, s.name_ku as service_name_ku, s.name_ar as service_name_ar, s.price as display_price
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.booking_date = '" . $conn->real_escape_string($dateFilter) . "'
";

if ($statusFilter) {
    $query .= " AND b.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$query .= " ORDER BY b.booking_time ASC";

$bookings = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('bookings'); ?> - Coovix Admin</title>
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
                <?php if (hasPermission('bookings')): ?><a href="bookings.php" class="active"><?php echo __('bookings'); ?></a><?php endif; ?>
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
                        <a href="?lang=en&date=<?php echo $dateFilter; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="lang-option <?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇬🇧</span> English
                        </a>
                        <a href="?lang=ku&date=<?php echo $dateFilter; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="lang-option <?php echo getCurrentLang() === 'ku' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇮🇶</span> کوردی
                        </a>
                        <a href="?lang=ar&date=<?php echo $dateFilter; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="lang-option <?php echo getCurrentLang() === 'ar' ? 'active' : ''; ?>">
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
                <h1><?php echo __('bookings'); ?> - <?php echo date('F d, Y', strtotime($dateFilter)); ?></h1>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Date Filter -->
            <div class="card">
                <form method="GET" class="date-filter-form">
                    <div class="date-filter-row">
                        <div class="date-filter-inputs">
                            <label for="date"><?php echo __('select_date'); ?>:</label>
                            <input type="date" id="date" name="date" value="<?php echo $dateFilter; ?>" onchange="this.form.submit()">
                            <?php if ($statusFilter): ?>
                                <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                            <?php endif; ?>
                        </div>
                        <div class="revenue-badge">
                            💰 <?php echo __('completed_total'); ?>: <?php echo number_format($totalCompleted, 0); ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="filters">
                <a href="bookings.php?date=<?php echo $dateFilter; ?>" class="btn btn-small <?php echo !$statusFilter ? 'active' : ''; ?>">
                    <span class="filter-icon">📋</span> <?php echo __('all'); ?>
                </a>
                <a href="bookings.php?date=<?php echo $dateFilter; ?>&status=pending" class="btn btn-small <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                    <span class="filter-icon">⏳</span> <?php echo __('pending'); ?>
                </a>
                <a href="bookings.php?date=<?php echo $dateFilter; ?>&status=confirmed" class="btn btn-small <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">
                    <span class="filter-icon">✅</span> <?php echo __('confirmed'); ?>
                </a>
                <a href="bookings.php?date=<?php echo $dateFilter; ?>&status=completed" class="btn btn-small <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                    <span class="filter-icon">🎉</span> <?php echo __('completed'); ?>
                </a>
                <a href="bookings.php?date=<?php echo $dateFilter; ?>&status=cancelled" class="btn btn-small <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                    <span class="filter-icon">❌</span> <?php echo __('cancelled'); ?>
                </a>
            </div>

            <!-- Bookings Table -->
            <div class="card table-scroll-wrapper">
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="data-table bookings-table">
                        <thead>
                            <tr>
                                <th><?php echo __('id'); ?></th>
                                <th><?php echo __('customer'); ?></th>
                                <th><?php echo __('category'); ?></th>
                                <th><?php echo __('car_type'); ?></th>
                                <th><?php echo __('service'); ?></th>
                                <th><?php echo __('price'); ?></th>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('time'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-size: 0.85rem;">#<?php echo $booking['id']; ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($booking['category']); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($booking['car_type'] ?? 'N/A'); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars(getLocalizedName(['name' => $booking['service_name'], 'name_ku' => $booking['service_name_ku'], 'name_ar' => $booking['service_name_ar']])); ?></td>
                                        <td style="font-size: 0.85rem; white-space: nowrap;"><?php echo number_format($booking['display_price'], 0); ?></td>
                                        <td style="font-size: 0.85rem; white-space: nowrap;"><?php echo date('M d', strtotime($booking['booking_date'])); ?></td>
                                        <td style="font-size: 0.85rem; white-space: nowrap;"><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                    <td>
                                        <form method="POST" class="inline-form">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="status-select status-<?php echo $booking['status']; ?>" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #d1d5db; background: #ffffff; color: #1f2937; cursor: pointer;">
                                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>⏳ <?php echo __('pending'); ?></option>
                                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>✅ <?php echo __('confirmed'); ?></option>
                                                <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>🎉 <?php echo __('completed'); ?></option>
                                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ <?php echo __('cancelled'); ?></option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="booking-actions">
                                            <button onclick='editBooking(<?php echo json_encode($booking); ?>)' class="btn btn-primary btn-small" style="font-size: 0.8rem; padding: 6px 12px;"><?php echo __('edit'); ?></button>
                                            <?php
    $waMessage = "Dear Sir/Madam,\nYour application has been accepted. Kindly ensure that you arrive at least 10 minutes prior to your scheduled appointment.\n\nDate: " . date('M d, Y', strtotime($booking['booking_date'])) . "\nTime: " . date('h:i A', strtotime($booking['booking_time']));
    $waPhone = preg_replace('/[^0-9]/', '', $booking['phone']);
?>
                                            <a href="https://wa.me/<?php echo $waPhone; ?>?text=<?php echo rawurlencode($waMessage); ?>" target="_blank" class="btn btn-small" style="background: #25D366; color: white; text-decoration: none; display: inline-flex; align-items: center; font-size: 0.8rem; padding: auto;">
                                                WhatsApp
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center"><?php echo __('no_bookings_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Booking Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 20px; width: 100%; max-width: 500px; margin: 16px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);">
            <div style="padding: 24px 24px 0; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.25rem; display: flex; align-items: center; gap: 10px; color: #111827;">✏️ <?php echo __('edit_booking'); ?></h2>
                <button onclick="closeEditModal()" style="width: 36px; height: 36px; border-radius: 50%; background: #f3f4f6; border: 1px solid #e5e7eb; color: #6b7280; cursor: pointer; font-size: 1.2rem;">&times;</button>
            </div>
            <div style="padding: 24px;">
                <form method="POST" id="editForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_booking">
                    <input type="hidden" name="booking_id" id="edit_booking_id">

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;"><?php echo __('customer_name'); ?> *</label>
                        <input type="text" name="customer_name" id="edit_customer_name" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; color: #1f2937;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;"><?php echo __('phone'); ?></label>
                        <input type="tel" name="phone" id="edit_phone" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; color: #1f2937;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;"><?php echo __('booking_date'); ?> *</label>
                        <input type="date" name="booking_date" id="edit_booking_date" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; color: #1f2937;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;"><?php echo __('booking_time'); ?> *</label>
                        <input type="time" name="booking_time" id="edit_booking_time" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; color: #1f2937;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;"><?php echo __('notes'); ?></label>
                        <textarea name="notes" id="edit_notes" rows="3" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; color: #1f2937;"></textarea>
                    </div>

                    <button type="submit" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer;"><?php echo __('save_changes'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editBooking(booking) {
            document.getElementById('edit_booking_id').value = booking.id;
            document.getElementById('edit_customer_name').value = booking.customer_name;
            document.getElementById('edit_phone').value = booking.phone || '';
            document.getElementById('edit_booking_date').value = booking.booking_date;
            document.getElementById('edit_booking_time').value = booking.booking_time;
            document.getElementById('edit_notes').value = booking.notes || '';

            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('editModal')) {
                closeEditModal();
            }
        });

        // Language dropdown
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
<?php $conn->close(); ?>
