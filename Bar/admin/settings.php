<?php
require_once 'config.php';
requireLogin();

// Only users with settings permission can see this page
if (!hasPermission('can_settings')) {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Handle Update Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service']) && empty($message)) {
    $id = intval($_POST['service_id']);
    $name = sanitize($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = sanitize($conn, $_POST['status']);

    $sql = "UPDATE services SET name=?, price=?, duration=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdisi", $name, $price, $duration, $status, $id);

    if ($stmt->execute()) {
        $message = t('service_updated');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Add Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service']) && empty($message)) {
    $name = sanitize($conn, $_POST['name']);
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = sanitize($conn, $_POST['status']);

    // Check if slug exists
    $check = $conn->prepare("SELECT id FROM services WHERE slug = ?");
    $check->bind_param("s", $slug);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }

    $sql = "INSERT INTO services (name, slug, price, duration, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdis", $name, $slug, $price, $duration, $status);

    if ($stmt->execute()) {
        $message = t('service_added');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Delete Service (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service']) && empty($message)) {
    $id = intval($_POST['delete_id']);
    $sql = "DELETE FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = t('service_deleted');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Get service for editing
$editService = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editService = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle Update Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product']) && empty($message)) {
    $id = intval($_POST['product_id']);
    $name = sanitize($conn, $_POST['name']);
    $description = sanitize($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $status = sanitize($conn, $_POST['status']);

    $sql = "UPDATE products SET name=?, description=?, price=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsi", $name, $description, $price, $status, $id);

    if ($stmt->execute()) {
        $message = t('product_updated');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product']) && empty($message)) {
    $name = sanitize($conn, $_POST['name']);
    $description = sanitize($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $status = sanitize($conn, $_POST['status']);

    $sql = "INSERT INTO products (name, description, price, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssds", $name, $description, $price, $status);

    if ($stmt->execute()) {
        $message = t('product_added');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Delete Product (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && empty($message)) {
    $id = intval($_POST['delete_id']);
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = t('product_deleted');
        $messageType = "success";
    } else {
        $message = t('error');
        $messageType = "error";
    }
    $stmt->close();
}

// Get product for editing
$editProduct = null;
if (isset($_GET['edit_product']) && is_numeric($_GET['edit_product'])) {
    $editId = intval($_GET['edit_product']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editProduct = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY name ASC");

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY name ASC");

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
$showAddProductForm = isset($_GET['action']) && $_GET['action'] === 'add_product';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('settings'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .settings-tab {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-weight: 600;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        .settings-tab:hover {
            border-color: #FF6B35;
            color: #FF6B35;
        }
        .settings-tab.active {
            background: #FF6B35;
            border-color: #FF6B35;
            color: white;
        }
        .price-input {
            position: relative;
        }
        .price-input::before {
            content: '$';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 600;
        }
        .price-input input {
            padding-left: 30px !important;
        }
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .service-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .service-info {
            flex: 1;
        }
        .service-info h3 {
            margin: 0 0 5px;
            font-size: 1.1rem;
            color: #1a1a1a;
        }
        .service-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9rem;
        }
        .service-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .service-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF6B35;
            margin-right: 20px;
        }
        .duration-input {
            position: relative;
        }
        .duration-input::after {
            content: 'min';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 0.9rem;
        }
        .duration-input input {
            padding-right: 45px !important;
        }
        .settings-section {
            margin-bottom: 20px;
        }
        .settings-section-header {
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .settings-section-header:hover {
            border-color: #FF6B35;
        }
        .settings-section-header.active {
            border-color: #FF6B35;
            border-radius: 15px 15px 0 0;
        }
        .settings-section-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .settings-section-header h3 i {
            color: #FF6B35;
            font-size: 1.2rem;
        }
        .settings-section-header .toggle-icon {
            font-size: 1rem;
            color: #888;
            transition: transform 0.3s;
        }
        .settings-section-header.active .toggle-icon {
            transform: rotate(180deg);
            color: #FF6B35;
        }
        .settings-section-body {
            display: none;
            background: white;
            border-radius: 0 0 15px 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px solid #FF6B35;
            border-top: none;
        }
        .settings-section-body.show {
            display: block;
        }

        /* Mobile Responsive Styles for Settings */
        @media (max-width: 768px) {
            .service-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px;
            }

            .service-info {
                width: 100%;
            }

            .service-info h3 {
                font-size: 1rem;
                margin-bottom: 8px;
            }

            .service-meta {
                flex-wrap: wrap;
                gap: 10px;
            }

            .service-price {
                font-size: 1.3rem;
                margin-right: 0;
                align-self: flex-start;
            }

            .service-card .action-buttons {
                width: 100%;
                display: flex;
                justify-content: flex-start;
                gap: 10px;
            }

            .service-card .action-buttons .btn-sm {
                padding: 10px 20px;
                flex: 1;
                max-width: 120px;
                justify-content: center;
            }

            .settings-section-header {
                padding: 15px 18px;
            }

            .settings-section-header h3 {
                font-size: 1rem;
            }

            .settings-section-header h3 i {
                font-size: 1rem;
            }

            .settings-section-body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .price-input::before {
                left: 12px;
            }

            .duration-input::after {
                right: 12px;
            }

            .card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .card-header .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .service-card {
                padding: 12px;
            }

            .service-info h3 {
                font-size: 0.95rem;
            }

            .service-price {
                font-size: 1.2rem;
            }

            .service-card .action-buttons .btn-sm {
                padding: 8px 15px;
                font-size: 0.85rem;
            }

            .settings-section-header {
                padding: 12px 15px;
            }

            .settings-section-header h3 {
                font-size: 0.95rem;
                gap: 8px;
            }

            .settings-section-body {
                padding: 12px;
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
                    <li class="active">
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
                    <h1><?php echo t('settings'); ?></h1>
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

                <!-- Add Service Form -->
                <?php if ($showAddForm): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_service'); ?></h2>
                        <a href="settings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <?php echo csrfField(); ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-tag"></i> <?php echo t('service_name'); ?> *
                                    </label>
                                    <input type="text" id="name" name="name" required placeholder="e.g., Classic Haircut">
                                </div>
                                <div class="form-group price-input">
                                    <label for="price">
                                        <?php echo t('price'); ?> *
                                    </label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00">
                                </div>
                                <div class="form-group duration-input">
                                    <label for="duration">
                                        <i class="fas fa-clock"></i> <?php echo t('duration'); ?>
                                    </label>
                                    <input type="number" id="duration" name="duration" min="5" value="30" placeholder="30">
                                </div>
                                <div class="form-group">
                                    <label for="status">
                                        <i class="fas fa-toggle-on"></i> <?php echo t('status'); ?>
                                    </label>
                                    <select id="status" name="status">
                                        <option value="active"><?php echo t('active'); ?></option>
                                        <option value="inactive"><?php echo t('inactive'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_service" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Service Form -->
                <?php if ($editService): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-edit"></i> <?php echo t('edit_service'); ?></h2>
                        <a href="settings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-tag"></i> <?php echo t('service_name'); ?> *
                                    </label>
                                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editService['name']); ?>">
                                </div>
                                <div class="form-group price-input">
                                    <label for="price">
                                        <?php echo t('price'); ?> *
                                    </label>
                                    <input type="number" id="price" name="price" step="1" min="0" required value="<?php echo intval($editService['price']); ?>">
                                </div>
                                <div class="form-group duration-input">
                                    <label for="duration">
                                        <i class="fas fa-clock"></i> <?php echo t('duration'); ?>
                                    </label>
                                    <input type="number" id="duration" name="duration" min="5" value="<?php echo $editService['duration']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="status">
                                        <i class="fas fa-toggle-on"></i> <?php echo t('status'); ?>
                                    </label>
                                    <select id="status" name="status">
                                        <option value="active" <?php echo $editService['status'] === 'active' ? 'selected' : ''; ?>><?php echo t('active'); ?></option>
                                        <option value="inactive" <?php echo $editService['status'] === 'inactive' ? 'selected' : ''; ?>><?php echo t('inactive'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_service" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('update'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Services & Prices Section (Collapsible) -->
                <?php if (!$showAddForm && !$editService): ?>
                <div class="settings-section">
                    <div class="settings-section-header" onclick="toggleSection(this)">
                        <h3><i class="fas fa-cut"></i> <?php echo t('services_prices'); ?></h3>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="settings-section-body">
                        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('add_service'); ?>
                            </a>
                        </div>
                        <?php if ($services->num_rows > 0): ?>
                            <?php while ($service = $services->fetch_assoc()): ?>
                            <div class="service-card">
                                <div class="service-info">
                                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <div class="service-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo $service['duration']; ?> <?php echo t('minutes'); ?></span>
                                        <span class="status-badge <?php echo $service['status']; ?>"><?php echo $service['status'] === 'active' ? t('active') : t('inactive'); ?></span>
                                    </div>
                                </div>
                                <div class="service-price"><?php echo number_format($service['price'], 0); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $service['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo t('confirm_delete_service'); ?>');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" name="delete_service" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-cut"></i>
                            <h3><?php echo t('no_services'); ?></h3>
                            <p><?php echo t('add_first_service'); ?></p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('add_service'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Products & Prices Section -->
                <?php if ($editProduct): ?>
                <!-- Edit Product Form -->
                <div class="settings-section">
                    <div class="settings-section-header" onclick="toggleSection(this)">
                        <h2><i class="fas fa-box"></i> <?php echo t('edit_product'); ?></h2>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="settings-section-body show">
                        <form method="POST" action="" class="service-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-tag"></i> <?php echo t('product_name'); ?> *
                                    </label>
                                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editProduct['name']); ?>">
                                </div>
                                <div class="form-group price-input">
                                    <label for="price">
                                        <?php echo t('price'); ?> *
                                    </label>
                                    <input type="number" id="price" name="price" step="1" min="0" required value="<?php echo intval($editProduct['price']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status">
                                    <i class="fas fa-toggle-on"></i> <?php echo t('status'); ?>
                                </label>
                                <select id="status" name="status">
                                    <option value="active" <?php echo $editProduct['status'] === 'active' ? 'selected' : ''; ?>><?php echo t('active'); ?></option>
                                    <option value="inactive" <?php echo $editProduct['status'] === 'inactive' ? 'selected' : ''; ?>><?php echo t('inactive'); ?></option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_product" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo t('update'); ?>
                                </button>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php elseif ($showAddProductForm): ?>
                <!-- Add Product Form -->
                <div class="settings-section">
                    <div class="settings-section-header" onclick="toggleSection(this)">
                        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_product'); ?></h2>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="settings-section-body show">
                        <form method="POST" action="" class="service-form">
                            <?php echo csrfField(); ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-tag"></i> <?php echo t('product_name'); ?> *
                                    </label>
                                    <input type="text" id="name" name="name" required placeholder="e.g. Hair Gel">
                                </div>
                                <div class="form-group price-input">
                                    <label for="price">
                                        <?php echo t('price'); ?> *
                                    </label>
                                    <input type="number" id="price" name="price" step="1" min="0" required placeholder="e.g. 15000">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status">
                                    <i class="fas fa-toggle-on"></i> <?php echo t('status'); ?>
                                </label>
                                <select id="status" name="status">
                                    <option value="active"><?php echo t('active'); ?></option>
                                    <option value="inactive"><?php echo t('inactive'); ?></option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_product" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> <?php echo t('add_product'); ?>
                                </button>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Products List -->
                <div class="settings-section">
                    <div class="settings-section-header active" onclick="toggleSection(this)">
                        <h3><i class="fas fa-box"></i> <?php echo t('products_prices'); ?></h3>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="settings-section-body show">
                        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                            <a href="?action=add_product" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('add_product'); ?>
                            </a>
                        </div>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="service-card">
                                <div class="service-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="service-meta">
                                        <span class="status-badge <?php echo $product['status']; ?>"><?php echo $product['status'] === 'active' ? t('active') : t('inactive'); ?></span>
                                    </div>
                                </div>
                                <div class="service-price"><?php echo number_format($product['price'], 0); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit_product=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo t('confirm_delete_product'); ?>');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box"></i>
                            <h3><?php echo t('no_products'); ?></h3>
                            <p><?php echo t('add_first_product'); ?></p>
                            <a href="?action=add_product" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('add_product'); ?>
                            </a>
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

        // Toggle section open/close
        function toggleSection(header) {
            header.classList.toggle('active');
            const body = header.nextElementSibling;
            body.classList.toggle('show');
        }
    </script>
</body>
</html>
