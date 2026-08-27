<?php
require_once 'config.php';
requireLogin();

// Check if user has permission to access users page
if (!hasPermission('can_users')) {
    header('Location: index.php');
    exit();
}
// Add can_products and can_expenses columns if they don't exist
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_products TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_expenses TINYINT(1) DEFAULT 0");

// Update admin user (ID 1) to have all permissions
$conn->query("UPDATE admin_users SET can_products=1, can_expenses=1 WHERE id=1");

$message = '';
$messageType = '';

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Handle Delete User (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && empty($message)) {
    $id = intval($_POST['delete_id']);
    if ($id == $_SESSION['admin_id']) {
        $message = "You cannot delete your own account!";
        $messageType = "error";
    } else {
        $sql = "DELETE FROM admin_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "User deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting user.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user']) && empty($message)) {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    $can_dashboard = isset($_POST['can_dashboard']) ? 1 : 0;
    $can_barbers = isset($_POST['can_barbers']) ? 1 : 0;
    $can_schedule = isset($_POST['can_schedule']) ? 1 : 0;
    $can_bookings = isset($_POST['can_bookings']) ? 1 : 0;
    $can_users = isset($_POST['can_users']) ? 1 : 0;
    $can_staff_status = isset($_POST['can_staff_status']) ? 1 : 0;
    $can_products = isset($_POST['can_products']) ? 1 : 0;
    $can_settings = isset($_POST['can_settings']) ? 1 : 0;
    $status = sanitize($conn, $_POST['status']);

    if (!empty($username) && !empty($password)) {
        // Check if username exists
        $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "Username already exists!";
            $messageType = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO admin_users (username, password, can_dashboard, can_barbers, can_schedule, can_bookings, can_users, can_staff_status, can_products, can_expenses, can_settings, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiiiiiiiis", $username, $hashed_password, $can_dashboard, $can_barbers, $can_schedule, $can_bookings, $can_users, $can_staff_status, $can_products, $can_expenses, $can_settings, $status);

            if ($stmt->execute()) {
                $message = "User added successfully!";
                $messageType = "success";
            } else {
                $message = "Error adding user. Please try again.";
                $messageType = "error";
            }
        }
    } else {
        $message = "Username and password are required!";
        $messageType = "error";
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user']) && empty($message)) {
    $id = intval($_POST['id']);
    $username = sanitize($conn, $_POST['username']);
    $can_dashboard = isset($_POST['can_dashboard']) ? 1 : 0;
    $can_barbers = isset($_POST['can_barbers']) ? 1 : 0;
    $can_schedule = isset($_POST['can_schedule']) ? 1 : 0;
    $can_bookings = isset($_POST['can_bookings']) ? 1 : 0;
    $can_users = isset($_POST['can_users']) ? 1 : 0;
    $can_staff_status = isset($_POST['can_staff_status']) ? 1 : 0;
    $can_products = isset($_POST['can_products']) ? 1 : 0;
    $can_expenses = isset($_POST['can_expenses']) ? 1 : 0;
    $can_settings = isset($_POST['can_settings']) ? 1 : 0;
    $status = sanitize($conn, $_POST['status']);

    // Check if username exists for other users
    $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
    $check->bind_param("si", $username, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = "Username already exists!";
        $messageType = "error";
    } else {
        // Update with or without password
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE admin_users SET username=?, password=?, can_dashboard=?, can_barbers=?, can_schedule=?, can_bookings=?, can_users=?, can_staff_status=?, can_products=?, can_expenses=?, can_settings=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiiiiiiiisi", $username, $hashed_password, $can_dashboard, $can_barbers, $can_schedule, $can_bookings, $can_users, $can_staff_status, $can_products, $can_expenses, $can_settings, $status, $id);
        } else {
            $sql = "UPDATE admin_users SET username=?, can_dashboard=?, can_barbers=?, can_schedule=?, can_bookings=?, can_users=?, can_staff_status=?, can_products=?, can_expenses=?, can_settings=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiiiiiiiisi", $username, $can_dashboard, $can_barbers, $can_schedule, $can_bookings, $can_users, $can_staff_status, $can_products, $can_expenses, $can_settings, $status, $id);
        }

        if ($stmt->execute()) {
            $message = "User updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating user. Please try again.";
            $messageType = "error";
        }
    }
}


// Get user for editing
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editUser = $result->fetch_assoc();
    $stmt->close();
}

// Get all users
$users = $conn->query("SELECT * FROM admin_users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('manage_users'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .permission-item:hover {
            background: #e9ecef;
        }
        .permission-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }
        .permission-item label {
            cursor: pointer;
            font-weight: 500;
            color: #333;
        }
        .permission-item .permission-desc {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        .user-id {
            background: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
            border-bottom: 0.3rem solid rgba(0, 0, 0, 0.1);
        }
        .access-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .access-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        .access-badge.granted {
            background: #d4edda;
            color: #155724;
        }
        .access-badge.denied {
            background: #f8d7da;
            color: #721c24;
        }
        .data-table tbody tr td:nth-child(2) {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .permission-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .permission-item {
                padding: 10px 12px;
            }
            
            .card-body {
                padding: 15px !important;
            }
            
            .form-grid {
                grid-template-columns: 1fr !important;
                gap: 15px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .dashboard-card {
                margin-bottom: 20px;
            }
            
            /* User table mobile - keep ID, Username, Status on one line */
            .data-table tbody tr td:nth-child(1),
            .data-table tbody tr td:nth-child(2) {
                display: inline-flex !important;
                width: auto !important;
                border-bottom: none !important;
                padding: 8px 5px !important;
                justify-content: flex-start !important;
                align-items: center !important;
            }
            
            /* Hide the separate status column on mobile */
            .data-table tbody tr td:nth-child(4) {
                display: none !important;
            }
            
            .data-table tbody tr td:nth-child(1)::before,
            .data-table tbody tr td:nth-child(2)::before {
                display: none !important;
            }
            
            .data-table tbody tr td:nth-child(1) {
                padding-left: 15px !important;
            }
            
            .data-table tbody tr td:nth-child(2) {
                flex: 1;
                gap: 8px;
                display: flex !important;
                align-items: center !important;
            }
            
            /* First row container for ID, Username */
            .data-table tbody tr {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
            }
            
            .data-table tbody tr td:nth-child(1),
            .data-table tbody tr td:nth-child(2) {
                flex-basis: auto;
            }
            
            /* Full width for permissions and actions */
            .data-table tbody tr td:nth-child(3),
            .data-table tbody tr td:nth-child(5) {
                flex-basis: 100%;
                width: 100%;
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
                    <li class="active">
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
                    <h1><?php echo t('manage_users'); ?></h1>
                </div>
                <div class="header-right">
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Add User Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <a href="?action=add" class="btn btn-primary" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-user-plus"></i> <?php echo t('add_user'); ?>
                    </a>
                </div>
                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                <!-- Add User Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus"></i> <?php echo t('add_new_user'); ?></h2>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php echo csrfField(); ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username"><?php echo t('username'); ?> *</label>
                                    <input type="text" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="password"><?php echo t('password'); ?> *</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status"><?php echo t('status'); ?></label>
                                    <select id="status" name="status">
                                        <option value="active"><?php echo t('active'); ?></option>
                                        <option value="inactive"><?php echo t('inactive'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <h3 style="margin: 20px 0 10px; color: #333;"><i class="fas fa-key"></i> <?php echo t('access_permissions'); ?></h3>
                            <div class="permission-grid">
                                <div class="permission-item">
                                    <input type="checkbox" id="can_dashboard" name="can_dashboard" checked>
                                    <div>
                                        <label for="can_dashboard"><?php echo t('dashboard'); ?></label>
                                        <div class="permission-desc"><?php echo t('view_dashboard_stats'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_bookings" name="can_bookings">
                                    <div>
                                        <label for="can_bookings"><?php echo t('bookings'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_customer_bookings'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_schedule" name="can_schedule">
                                    <div>
                                        <label for="can_schedule"><?php echo t('work_time'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_work_schedule'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_staff_status" name="can_staff_status">
                                    <div>
                                        <label for="can_staff_status"><?php echo t('staff_status'); ?></label>
                                        <div class="permission-desc"><?php echo t('view_staff_work_status'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_barbers" name="can_barbers">
                                    <div>
                                        <label for="can_barbers"><?php echo t('barbers'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_barber_profiles'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_users" name="can_users">
                                    <div>
                                        <label for="can_users"><?php echo t('users'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_admin_users'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_products" name="can_products">
                                    <div>
                                        <label for="can_products"><?php echo t('products'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_products_sales'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_expenses" name="can_expenses">
                                    <div>
                                        <label for="can_expenses"><?php echo t('expenses'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_business_expenses'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_settings" name="can_settings">
                                    <div>
                                        <label for="can_settings"><?php echo t('settings'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_services_settings'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo t('create_user'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <?php elseif ($editUser): ?>
                <!-- Edit User Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-edit"></i> <?php echo t('edit_user'); ?></h2>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username"><?php echo t('username'); ?> *</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="password"><?php echo t('password_leave_blank'); ?></label>
                                    <input type="password" id="password" name="password" placeholder="<?php echo t('enter_new_password'); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status"><?php echo t('status'); ?></label>
                                    <select id="status" name="status">
                                        <option value="active" <?php echo $editUser['status'] === 'active' ? 'selected' : ''; ?>><?php echo t('active'); ?></option>
                                        <option value="inactive" <?php echo $editUser['status'] === 'inactive' ? 'selected' : ''; ?>><?php echo t('inactive'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <h3 style="margin: 20px 0 10px; color: #333;"><i class="fas fa-key"></i> <?php echo t('access_permissions'); ?></h3>
                            <div class="permission-grid">
                                <div class="permission-item">
                                    <input type="checkbox" id="can_dashboard" name="can_dashboard" <?php echo $editUser['can_dashboard'] ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_dashboard"><?php echo t('dashboard'); ?></label>
                                        <div class="permission-desc"><?php echo t('view_dashboard_stats'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_bookings" name="can_bookings" <?php echo $editUser['can_bookings'] ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_bookings"><?php echo t('bookings'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_customer_bookings'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_schedule" name="can_schedule" <?php echo ($editUser['can_schedule'] ?? 0) ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_schedule"><?php echo t('work_time'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_work_schedule'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_staff_status" name="can_staff_status" <?php echo ($editUser['can_staff_status'] ?? 0) ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_staff_status"><?php echo t('staff_status'); ?></label>
                                        <div class="permission-desc"><?php echo t('view_staff_work_status'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_barbers" name="can_barbers" <?php echo $editUser['can_barbers'] ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_barbers"><?php echo t('barbers'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_barber_profiles'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_users" name="can_users" <?php echo $editUser['can_users'] ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_users"><?php echo t('users'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_admin_users'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_products" name="can_products" <?php echo ($editUser['can_products'] ?? 0) ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_products"><?php echo t('products'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_products_sales'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_expenses" name="can_expenses" <?php echo ($editUser['can_expenses'] ?? 0) ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_expenses"><?php echo t('expenses'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_business_expenses'); ?></div>
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="can_settings" name="can_settings" <?php echo ($editUser['can_settings'] ?? 0) ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="can_settings"><?php echo t('settings'); ?></label>
                                        <div class="permission-desc"><?php echo t('manage_services_settings'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="edit_user" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo t('update_user'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <?php else: ?>
                <!-- Users List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> <?php echo t('all_users_title'); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if ($users->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo t('username'); ?></th>
                                    <th><?php echo t('access_permissions'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="user-id">#<?php echo $user['id']; ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <span class="status-badge <?php echo $user['status']; ?>">
                                            <?php echo t($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="access-badges">
                                            <span class="access-badge <?php echo $user['can_dashboard'] ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo $user['can_dashboard'] ? 'check' : 'times'; ?>"></i> <?php echo t('dashboard'); ?>
                                            </span>
                                            <span class="access-badge <?php echo $user['can_bookings'] ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo $user['can_bookings'] ? 'check' : 'times'; ?>"></i> <?php echo t('bookings'); ?>
                                            </span>
                                            <span class="access-badge <?php echo ($user['can_products'] ?? 0) ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo ($user['can_products'] ?? 0) ? 'check' : 'times'; ?>"></i> <?php echo t('products'); ?>
                                            </span>
                                            <span class="access-badge <?php echo ($user['can_schedule'] ?? 0) ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo ($user['can_schedule'] ?? 0) ? 'check' : 'times'; ?>"></i> <?php echo t('work_time'); ?>
                                            </span>
                                            <span class="access-badge <?php echo ($user['can_staff_status'] ?? 0) ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo ($user['can_staff_status'] ?? 0) ? 'check' : 'times'; ?>"></i> <?php echo t('staff_status'); ?>
                                            </span>
                                            <span class="access-badge <?php echo $user['can_barbers'] ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo $user['can_barbers'] ? 'check' : 'times'; ?>"></i> <?php echo t('barbers'); ?>
                                            </span>
                                            <span class="access-badge <?php echo $user['can_users'] ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo $user['can_users'] ? 'check' : 'times'; ?>"></i> <?php echo t('users'); ?>
                                            </span>
                                            <span class="access-badge <?php echo $user['can_users'] ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo $user['can_users'] ? 'check' : 'times'; ?>"></i> <?php echo t('user_reports'); ?>
                                            </span>
                                            <span class="access-badge <?php echo ($user['can_expenses'] ?? 0) ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo ($user['can_expenses'] ?? 0) ? 'check' : 'times'; ?>"></i> <?php echo t('expenses'); ?>
                                            </span>
                                            <span class="access-badge <?php echo ($user['can_settings'] ?? 0) ? 'granted' : 'denied'; ?>">
                                                <i class="fas fa-<?php echo ($user['can_settings'] ?? 0) ? 'check' : 'times'; ?>"></i> <?php echo t('settings'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="display: none;">
                                        <span class="status-badge <?php echo $user['status']; ?>">
                                            <?php echo t($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                                <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo t('confirm_delete_user'); ?>')">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                                    <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p><?php echo t('no_users_found'); ?></p>
                            <a href="?action=add" class="btn btn-primary"><?php echo t('add_user'); ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
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
    </script>
</body>
</html>
