<?php
require_once 'config.php';
requireLogin();

// Add can_expenses column if it doesn't exist
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_expenses TINYINT(1) DEFAULT 0");

// Update admin user (ID 1) to have expenses permission
$conn->query("UPDATE admin_users SET can_expenses=1 WHERE id=1");

// Check permission
if (!hasPermission('can_expenses')) {
    header('Location: index.php');
    exit();
}

// Create expenses table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createTableSQL);

$message = '';
$messageType = '';

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

$currentUserId = $_SESSION['admin_id'];

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense']) && empty($message)) {
    $name = sanitize($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $date = sanitize($conn, $_POST['date']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');
    
    // Handle file upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (in_array($ext, $allowed) && in_array($mimeType, $allowedMimes)) {
            $newname = 'expense_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/expenses/';

            // Create directory if not exists
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path . $newname)) {
                $photo = $newname;
            }
        }
    }

    $sql = "INSERT INTO expenses (name, price, date, photo, notes, user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsssi", $name, $price, $date, $photo, $notes, $currentUserId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Expense added successfully!";
        $_SESSION['messageType'] = "success";
        header('Location: expenses.php');
        exit();
    } else {
        $message = "Error adding expense. Please try again.";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense']) && empty($message)) {
    $id = intval($_POST['id']);
    $name = sanitize($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $date = sanitize($conn, $_POST['date']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');
    $existing_photo = sanitize($conn, $_POST['existing_photo'] ?? '');
    
    $photo = $existing_photo;
    
    // Handle new file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (in_array($ext, $allowed) && in_array($mimeType, $allowedMimes)) {
            $newname = 'expense_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/expenses/';

            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path . $newname)) {
                // Delete old photo
                if ($existing_photo && file_exists($upload_path . $existing_photo)) {
                    unlink($upload_path . $existing_photo);
                }
                $photo = $newname;
            }
        }
    }
    
    $sql = "UPDATE expenses SET name=?, price=?, date=?, photo=?, notes=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsssii", $name, $price, $date, $photo, $notes, $id, $currentUserId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Expense updated successfully!";
        $_SESSION['messageType'] = "success";
        header('Location: expenses.php');
        exit();
    } else {
        $message = "Error updating expense. Please try again.";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Delete Expense (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense']) && empty($message)) {
    $id = intval($_POST['delete_id']);
    
    // Get photo to delete
    $stmt = $conn->prepare("SELECT photo FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $photo = $row['photo'];
        if ($photo && file_exists('../uploads/expenses/' . $photo)) {
            unlink('../uploads/expenses/' . $photo);
        }
    }
    
    $sql = "DELETE FROM expenses WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $currentUserId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Expense deleted successfully!";
        $_SESSION['messageType'] = "success";
        header('Location: expenses.php');
        exit();
    } else {
        $message = "Error deleting expense. Please try again.";
        $messageType = "error";
    }
    $stmt->close();
}

// Get month filter (default to current month)
$monthFilter = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');

// Get all expenses with filter using prepared statements
if ($monthFilter) {
    $stmt = $conn->prepare("SELECT e.*, au.username FROM expenses e LEFT JOIN admin_users au ON e.user_id = au.id WHERE DATE_FORMAT(e.date, '%Y-%m') = ? ORDER BY e.date DESC, e.created_at DESC");
    $stmt->bind_param("s", $monthFilter);
    $stmt->execute();
    $expenses = $stmt->get_result();

    $stmt2 = $conn->prepare("SELECT SUM(price) as total FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?");
    $stmt2->bind_param("s", $monthFilter);
    $stmt2->execute();
    $totalExpenses = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    $expenses = $conn->query("SELECT e.*, au.username FROM expenses e LEFT JOIN admin_users au ON e.user_id = au.id ORDER BY e.date DESC, e.created_at DESC");
    $totalExpenses = $conn->query("SELECT SUM(price) as total FROM expenses")->fetch_assoc()['total'] ?? 0;
}

// Get available months that have expense data
$availableMonthsQuery = "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month_value, DATE_FORMAT(date, '%Y-%m-01') as month_date FROM expenses ORDER BY month_value DESC";
$availableMonthsResult = $conn->query($availableMonthsQuery);
$availableMonths = [];
while ($row = $availableMonthsResult->fetch_assoc()) {
    $availableMonths[] = $row;
}

// Get expense for edit
$editExpense = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $editId, $currentUserId);
    $stmt->execute();
    $editExpense = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('expenses'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .excel-table thead {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            color: white;
        }
        .excel-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #444;
            font-size: 0.95rem;
        }
        .excel-table td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            background: white;
        }
        .excel-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .excel-table tbody tr:hover {
            background: #fff3e0;
            transition: all 0.3s;
        }
        .expense-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            cursor: pointer;
        }
        .expense-photo:hover {
            border-color: #FF6B35;
            transform: scale(1.05);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            animation: fadeIn 0.3s;
        }
        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.2s;
        }
        .close:hover {
            transform: scale(1.2) rotate(90deg);
        }
        .modal-body {
            padding: 30px;
        }
        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-upload-input {
            display: none;
        }
        .file-upload-label {
            display: block;
            padding: 40px 20px;
            border: 2px dashed #ddd;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .file-upload-label:hover {
            border-color: #FF6B35;
            background: #fff3e0;
        }
        .file-upload-label i {
            font-size: 3rem;
            color: #FF6B35;
            margin-bottom: 10px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                    <li class="active">
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

        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo t('expenses'); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="openFilterModal()" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> <?php echo t('filter_by_month'); ?>
                    </button>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- New Expense Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <button onclick="openExpenseModal()" class="btn btn-primary" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-plus"></i> <?php echo t('new_expense'); ?>
                    </button>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
                <?php endif; ?>

                <?php if ($monthFilter): ?>
                <div class="filter-container" style="margin-bottom: 15px;">
                    <span class="filter-badge" style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; padding: 8px 15px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('F Y', strtotime($monthFilter . '-01')); ?>
                        <a href="expenses.php" style="color: white; margin-left: 5px; text-decoration: none; font-weight: bold;">×</a>
                    </span>
                    <a href="expenses.php" class="btn btn-sm btn-secondary" style="margin-left: 10px;">
                        <i class="fas fa-times"></i> <?php echo t('clear_filter'); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="stats-row" style="margin-bottom: 25px;">
                    <div class="stat-card">
                        <div class="stat-icon danger" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totalExpenses, 0); ?> IQD</h3>
                            <p><?php echo t('total_expenses'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-body" style="padding: 0;">
                        <?php if ($expenses->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="excel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo t('expense_name'); ?></th>
                                        <th><?php echo t('amount'); ?></th>
                                        <th><?php echo t('date'); ?></th>
                                        <th><?php echo t('photo'); ?></th>
                                        <th><?php echo t('notes'); ?></th>
                                        <th><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; while ($expense = $expenses->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="#" style="font-weight: 600; color: #666;"><?php echo $counter++; ?></td>
                                        <td data-label="<?php echo t('expense_name'); ?>" style="font-weight: 600;"><?php echo htmlspecialchars($expense['name']); ?></td>
                                        <td data-label="<?php echo t('amount'); ?>" style="color: #dc3545; font-weight: 700; font-size: 1.1rem;"><?php echo number_format($expense['price'], 0); ?> IQD</td>
                                        <td data-label="<?php echo t('date'); ?>"><?php echo date('M d, Y', strtotime($expense['date'])); ?></td>
                                        <td data-label="<?php echo t('photo'); ?>">
                                            <?php if ($expense['photo']): ?>
                                            <img src="../uploads/expenses/<?php echo $expense['photo']; ?>" class="expense-photo" onclick="viewImage('../uploads/expenses/<?php echo $expense['photo']; ?>')" alt="Expense Photo">
                                            <?php else: ?>
                                            <span style="color: #999;"><?php echo t('no_photo'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="<?php echo t('notes'); ?>"><?php echo htmlspecialchars($expense['notes'] ?? '-'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editExpense(<?php echo $expense['id']; ?>)" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this expense?');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="delete_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" name="delete_expense" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3><?php echo t('no_expenses_yet'); ?></h3>
                            <p><?php echo t('start_adding_expense'); ?></p>
                            <button onclick="openExpenseModal()" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('new_expense'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Expense Modal -->
    <div id="expenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> <span id="modalTitle"><?php echo t('new_expense'); ?></span></h3>
                <span class="close" onclick="closeExpenseModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="expenseForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" id="expense_id">
                    <input type="hidden" name="existing_photo" id="existing_photo">

                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> <?php echo t('expense_name'); ?> *</label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="e.g., Office Supplies">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price"><i class="fas fa-money-bill"></i> <?php echo t('amount_iqd'); ?> *</label>
                            <input type="number" id="price" name="price" class="form-control" step="1" min="0" required placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar"></i> <?php echo t('date'); ?> *</label>
                            <input type="date" id="date" name="date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="photo"><i class="fas fa-camera"></i> <?php echo t('upload_photo_receipt'); ?></label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="photo" name="photo" class="file-upload-input" accept="image/*,application/pdf" onchange="previewFile(this)">
                            <label for="photo" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div><?php echo t('click_upload_drag'); ?></div>
                                <small style="color: #999;"><?php echo t('jpg_png_gif_pdf'); ?></small>
                            </label>
                            <div id="preview"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes"><i class="fas fa-sticky-note"></i> <?php echo t('notes'); ?></label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder=""></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_expense" id="submitBtn" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> <?php echo t('save_expense'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageModal" class="modal" onclick="this.classList.remove('show')">
        <div style="position: relative; max-width: 90%; max-height: 90vh;">
            <img id="modalImage" src="" style="max-width: 100%; max-height: 90vh; border-radius: 8px;">
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-filter"></i> <?php echo t('filter_expenses'); ?></h3>
                <span class="close" onclick="closeFilterModal()">&times;</span>
            </div>
            <div class="modal-body">
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
                <div class="form-actions">
                    <button onclick="applyFilter()" class="btn btn-primary btn-lg">
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

        function openExpenseModal() {
            document.getElementById('expenseModal').classList.add('show');
            document.getElementById('expenseForm').reset();
            document.getElementById('expense_id').value = '';
            document.getElementById('existing_photo').value = '';
            document.getElementById('modalTitle').textContent = '<?php echo t('new_expense'); ?>';
            document.getElementById('submitBtn').name = 'add_expense';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check"></i> <?php echo t('save_expense'); ?>';
            document.getElementById('preview').innerHTML = '';
        }

        function closeExpenseModal() {
            document.getElementById('expenseModal').classList.remove('show');
        }

        function editExpense(id) {
            window.location.href = '?edit=' + id;
        }

        <?php if ($editExpense): ?>
        window.onload = function() {
            document.getElementById('expenseModal').classList.add('show');
            document.getElementById('expense_id').value = <?php echo $editExpense['id']; ?>;
            document.getElementById('name').value = <?php echo json_encode($editExpense['name']); ?>;
            document.getElementById('price').value = <?php echo $editExpense['price']; ?>;
            document.getElementById('date').value = <?php echo json_encode($editExpense['date']); ?>;
            document.getElementById('notes').value = <?php echo json_encode($editExpense['notes'] ?? ''); ?>;
            document.getElementById('existing_photo').value = <?php echo json_encode($editExpense['photo'] ?? ''); ?>;
            document.getElementById('modalTitle').textContent = '<?php echo t('edit_expense'); ?>';
            document.getElementById('submitBtn').name = 'edit_expense';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> <?php echo t('update_expense'); ?>';
            
            <?php if ($editExpense['photo']): ?>
            document.getElementById('preview').innerHTML = '<img src="../uploads/expenses/<?php echo $editExpense['photo']; ?>" class="preview-image">';
            <?php endif; ?>
        };
        <?php endif; ?>

        function previewFile(input) {
            const preview = document.getElementById('preview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = '<img src="' + e.target.result + '" class="preview-image">';
                    } else {
                        preview.innerHTML = '<p style="color: #28a745; margin-top: 10px;"><i class="fas fa-file-pdf"></i> ' + file.name + '</p>';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function viewImage(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('expenseModal');
            if (event.target === modal) {
                closeExpenseModal();
            }
            const filterModal = document.getElementById('filterModal');
            if (event.target === filterModal) {
                closeFilterModal();
            }
        };

        function openFilterModal() {
            document.getElementById('filterModal').classList.add('show');
        }

        function closeFilterModal() {
            document.getElementById('filterModal').classList.remove('show');
        }

        function applyFilter() {
            const month = document.getElementById('monthFilter').value;
            if (month) {
                window.location.href = 'expenses.php?month=' + month;
            } else {
                window.location.href = 'expenses.php';
            }
        }

        // Auto-refresh at midnight to show new month's data
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
