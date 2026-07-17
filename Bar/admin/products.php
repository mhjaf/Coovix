<?php
require_once 'config.php';
requireLogin();

// Add can_products column if it doesn't exist and grant to admin
$conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_products TINYINT(1) DEFAULT 0");
$conn->query("UPDATE admin_users SET can_products=1 WHERE id=1");

// Check permission
if (!hasPermission('can_products')) {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';
$currentUserId = $_SESSION['admin_id'];

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Handle Delete Sale (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sale']) && empty($message)) {
    $id = intval($_POST['delete_id']);
    $whereClause = "id = ?";
    $params = [$id];
    $types = "i";
    if (!hasPermission('can_users')) {
        $whereClause .= " AND user_id = ?";
        $params[] = $currentUserId;
        $types .= "i";
    }
    $sql = "DELETE FROM product_sales WHERE " . $whereClause;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $message = "Sale record deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting sale.";
        $messageType = "error";
    }
    $stmt->close();
}

// Get filter user from GET parameter (for admins)
$filterUserId = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
$monthFilter = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : '';
$dateFilter = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';

// Check if any filter parameters exist in the URL
$hasFilterParams = isset($_GET['filter_user']) || isset($_GET['month']) || isset($_GET['date']);

// Default to today's date only if no filter parameters at all
if (!$hasFilterParams) {
    $dateFilter = date('Y-m-d');
}

// If user filter is selected without month/date filter, default to current month
if ($filterUserId > 0 && !$monthFilter && !$dateFilter) {
    $monthFilter = date('Y-m');
}

// Build parameterized query conditions
$whereConditions = [];
$paramTypes = "";
$paramValues = [];

// User filter
if (!hasPermission('can_users')) {
    $whereConditions[] = "ps.user_id = ?";
    $paramTypes .= "i";
    $paramValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $whereConditions[] = "ps.user_id = ?";
    $paramTypes .= "i";
    $paramValues[] = $filterUserId;
}

// Date/Month filter (month takes priority)
if ($monthFilter) {
    $whereConditions[] = "DATE_FORMAT(ps.sale_date, '%Y-%m') = ?";
    $paramTypes .= "s";
    $paramValues[] = $monthFilter;
} elseif ($dateFilter) {
    $whereConditions[] = "ps.sale_date = ?";
    $paramTypes .= "s";
    $paramValues[] = $dateFilter;
}

$userFilter = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

// Get all users for filter dropdown (admins only)
$allUsers = [];
if (hasPermission('can_users')) {
    $usersResult = $conn->query("SELECT id, username FROM admin_users ORDER BY username ASC");
    while ($row = $usersResult->fetch_assoc()) {
        $allUsers[] = $row;
    }
}

// Get all products for dropdown
$productsList = [];
$productsResult = $conn->query("SELECT id, name, price FROM products WHERE status = 'active' ORDER BY name ASC");
while ($row = $productsResult->fetch_assoc()) {
    $productsList[] = $row;
}

// Handle Buy Product (Add Sale)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_product']) && empty($message)) {
    $product_id = intval($_POST['product_id']);
    $product_name = sanitize($conn, $_POST['product_name']);
    $quantity = intval($_POST['quantity'] ?? 1);
    $price = floatval($_POST['price']);
    $total = $price * $quantity;
    $customer_name = sanitize($conn, $_POST['customer_name'] ?? '');
    $sale_date = sanitize($conn, $_POST['sale_date']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');

    $sql = "INSERT INTO product_sales (product_id, product_name, quantity, price, total, customer_name, sale_date, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiddsssi", $product_id, $product_name, $quantity, $price, $total, $customer_name, $sale_date, $notes, $currentUserId);

    if ($stmt->execute()) {
        $message = "Product sale recorded successfully!";
        $messageType = "success";
        header("Location: products.php");
        exit();
    } else {
        $message = "Error recording sale.";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Edit Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sale']) && empty($message)) {
    $id = intval($_POST['id']);
    $product_id = intval($_POST['product_id']);
    $product_name = sanitize($conn, $_POST['product_name']);
    $quantity = intval($_POST['quantity'] ?? 1);
    $price = floatval($_POST['price']);
    $total = $price * $quantity;
    $customer_name = sanitize($conn, $_POST['customer_name'] ?? '');
    $sale_date = sanitize($conn, $_POST['sale_date']);
    $notes = sanitize($conn, $_POST['notes'] ?? '');

    $whereClause = "id=?";
    $params = [$product_id, $product_name, $quantity, $price, $total, $customer_name, $sale_date, $notes, $id];
    $types = "isiddsssi";
    if (!hasPermission('can_users')) {
        $whereClause .= " AND user_id = ?";
        $params[] = $currentUserId;
        $types .= "i";
    }

    $sql = "UPDATE product_sales SET product_id=?, product_name=?, quantity=?, price=?, total=?, customer_name=?, sale_date=?, notes=? WHERE " . $whereClause;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $message = "Sale updated successfully!";
        $messageType = "success";
        header("Location: products.php");
        exit();
    } else {
        $message = "Error updating sale.";
        $messageType = "error";
    }
    $stmt->close();
}

// Get all sales with user filter - grouped by date for day-by-day display
$salesQuery = "SELECT ps.*, au.username FROM product_sales ps LEFT JOIN admin_users au ON ps.user_id = au.id" . $userFilter . " ORDER BY ps.sale_date DESC, ps.created_at DESC";

$salesByDate = [];
$tempSales = [];

if (!empty($paramValues)) {
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param($paramTypes, ...$paramValues);
    $stmt->execute();
    $sales = $stmt->get_result();
} else {
    $sales = $conn->query($salesQuery);
}

if ($sales->num_rows > 0) {
    while ($row = $sales->fetch_assoc()) {
        $tempSales[] = $row;
        $date = $row['sale_date'];
        if (!isset($salesByDate[$date])) {
            $salesByDate[$date] = [
                'sales' => [],
                'total_quantity' => 0,
                'total_revenue' => 0,
                'count' => 0
            ];
        }
        $salesByDate[$date]['sales'][] = $row;
        $salesByDate[$date]['total_quantity'] += $row['quantity'];
        $salesByDate[$date]['total_revenue'] += $row['total'];
        $salesByDate[$date]['count']++;
    }
}
if (isset($stmt)) $stmt->close();

// Build count filter conditions (without ps. alias)
$countConditions = [];
$countParamTypes = "";
$countParamValues = [];

if (!hasPermission('can_users')) {
    $countConditions[] = "user_id = ?";
    $countParamTypes .= "i";
    $countParamValues[] = $currentUserId;
} elseif ($filterUserId > 0) {
    $countConditions[] = "user_id = ?";
    $countParamTypes .= "i";
    $countParamValues[] = $filterUserId;
}

if ($monthFilter) {
    $countConditions[] = "DATE_FORMAT(sale_date, '%Y-%m') = ?";
    $countParamTypes .= "s";
    $countParamValues[] = $monthFilter;
} elseif ($dateFilter) {
    $countConditions[] = "sale_date = ?";
    $countParamTypes .= "s";
    $countParamValues[] = $dateFilter;
}

$countWhere = !empty($countConditions) ? " WHERE " . implode(" AND ", $countConditions) : "";

// Get counts with prepared statements
if (!empty($countParamValues)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_sales" . $countWhere);
    $stmt->bind_param($countParamTypes, ...$countParamValues);
    $stmt->execute();
    $totalSales = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // For today's sales, add CURDATE condition
    $todayConditions = $countConditions;
    $todayConditions[] = "sale_date = CURDATE()";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_sales WHERE " . implode(" AND ", $todayConditions));
    $stmt->bind_param($countParamTypes, ...$countParamValues);
    $stmt->execute();
    $todaySales = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(total) as sum FROM product_sales" . $countWhere);
    $stmt->bind_param($countParamTypes, ...$countParamValues);
    $stmt->execute();
    $totalRevenue = $stmt->get_result()->fetch_assoc()['sum'] ?? 0;
    $stmt->close();
} else {
    $totalSales = $conn->query("SELECT COUNT(*) as count FROM product_sales")->fetch_assoc()['count'];
    $todaySales = $conn->query("SELECT COUNT(*) as count FROM product_sales WHERE sale_date = CURDATE()")->fetch_assoc()['count'];
    $totalRevenue = $conn->query("SELECT SUM(total) as sum FROM product_sales")->fetch_assoc()['sum'] ?? 0;
}

// Get available months that have sales data
$availableMonths = [];
$monthsQuery = "SELECT DISTINCT DATE_FORMAT(sale_date, '%Y-%m') as month_value, DATE_FORMAT(sale_date, '%Y-%m-01') as month_date FROM product_sales";

if (!hasPermission('can_users')) {
    $stmt = $conn->prepare($monthsQuery . " WHERE user_id = ? ORDER BY month_value DESC");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $availableMonthsResult = $stmt->get_result();
    $stmt->close();
} elseif ($filterUserId > 0) {
    $stmt = $conn->prepare($monthsQuery . " WHERE user_id = ? ORDER BY month_value DESC");
    $stmt->bind_param("i", $filterUserId);
    $stmt->execute();
    $availableMonthsResult = $stmt->get_result();
    $stmt->close();
} else {
    $availableMonthsResult = $conn->query($monthsQuery . " ORDER BY month_value DESC");
}

while ($row = $availableMonthsResult->fetch_assoc()) {
    $availableMonths[] = $row;
}

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';

// Get sale for edit
$editSale = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM product_sales WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editSale = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('products'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 15px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .stat-card .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: white;
            flex-shrink: 0;
        }
        .stat-card .stat-icon.primary { background: linear-gradient(135deg, #FF6B35, #ff8c5a); }
        .stat-card .stat-icon.success { background: linear-gradient(135deg, #28a745, #48c768); }
        .stat-card .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #3fc3d8); }
        .stat-card .stat-info h3 { font-size: 1.3rem; color: #333; margin: 0; font-weight: 700; }
        .stat-card .stat-info p { color: #666; font-size: 0.75rem; margin: 0; font-weight: 500; }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .stat-card {
                padding: 10px 8px;
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            
            .stat-card .stat-icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .stat-card .stat-info h3 {
                font-size: 1rem;
                font-weight: 700;
            }
            
            .stat-card .stat-info p {
                font-size: 0.65rem;
                line-height: 1.2;
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
                    <li class="active">
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
                    <h1><?php echo t('products'); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="openFilterModal()" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> <?php echo t('filter'); ?>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Buy Products Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <a href="?action=add" class="btn btn-primary" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-shopping-cart"></i> <?php echo t('buy_products'); ?>
                    </a>
                </div>
                <!-- Active Filters -->
                <?php if ($filterUserId || $monthFilter || $dateFilter): ?>
                <div class="filter-container" style="margin-bottom: 15px;">
                    <?php if ($filterUserId): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-user"></i> <?php 
                            $selectedUser = array_filter($allUsers, function($u) use ($filterUserId) { return $u['id'] == $filterUserId; });
                            echo !empty($selectedUser) ? reset($selectedUser)['username'] : 'User #' . $filterUserId;
                        ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($dateFilter): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-calendar-day"></i> <?php echo date('M d, Y', strtotime($dateFilter)); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($monthFilter): ?>
                    <span class="filter-badge" style="display: inline-block; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.9rem; margin-right: 10px;">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('F Y', strtotime($monthFilter . '-01')); ?>
                    </span>
                    <?php endif; ?>
                    <a href="products.php" class="btn btn-sm" style="font-size: 0.85rem; padding: 4px 10px; background: #dc3545; color: white; text-decoration: none; border-radius: 20px;">
                        <i class="fas fa-times"></i> <?php echo t('clear_all_filters'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
                <?php endif; ?>

                <!-- Buy Product Form -->
                <?php if ($showAddForm || $editSale): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-shopping-cart"></i> <?php echo $editSale ? t('edit_sale') : t('buy_products'); ?></h2>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <?php echo csrfField(); ?>
                            <?php if ($editSale): ?>
                            <input type="hidden" name="id" value="<?php echo $editSale['id']; ?>">
                            <?php endif; ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="product_id">
                                        <i class="fas fa-box"></i> <?php echo t('select_product'); ?> *
                                    </label>
                                    <select id="product_id" name="product_id" required onchange="updateProductPrice()">
                                        <option value="">-- <?php echo t('choose_product'); ?> --</option>
                                        <?php foreach ($productsList as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                            data-price="<?php echo intval($product['price']); ?>"
                                            <?php echo ($editSale && $editSale['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name']); ?> - <?php echo number_format($product['price'], 0); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="product_name" name="product_name" value="<?php echo $editSale ? htmlspecialchars($editSale['product_name']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="quantity">
                                        <i class="fas fa-hashtag"></i> <?php echo t('quantity'); ?> *
                                    </label>
                                    <input type="number" id="quantity" name="quantity" min="1" value="<?php echo $editSale ? $editSale['quantity'] : '1'; ?>" required onchange="updateTotal()">
                                </div>
                                <div class="form-group">
                                    <label for="price">
                                        <i class="fas fa-tag"></i> <?php echo t('price'); ?>
                                    </label>
                                    <input type="number" id="price" name="price" step="1" min="0" style="color: #FF6B35; font-weight: 700; font-size: 1.2rem;" value="<?php echo $editSale ? intval($editSale['price']) : '0'; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="total_display">
                                        <i class="fas fa-calculator"></i> <?php echo t('total'); ?>
                                    </label>
                                    <input type="text" id="total_display" style="color: #28a745; font-weight: 700; font-size: 1.2rem; background: #f8f9fa;" value="<?php echo $editSale ? number_format($editSale['total'], 0) : '0'; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="sale_date">
                                        <i class="fas fa-calendar"></i> <?php echo t('date'); ?> *
                                    </label>
                                    <input type="date" id="sale_date" name="sale_date" required value="<?php echo $editSale ? $editSale['sale_date'] : date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label for="notes">
                                        <i class="fas fa-sticky-note"></i> <?php echo t('notes'); ?>
                                    </label>
                                    <textarea id="notes" name="notes" rows="2" placeholder="<?php echo t('notes_placeholder'); ?>"><?php echo $editSale ? htmlspecialchars($editSale['notes']) : ''; ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="<?php echo $editSale ? 'edit_sale' : 'buy_product'; ?>" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i> <?php echo $editSale ? t('update_sale') : t('complete_sale'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalSales; ?></h3>
                            <p><?php echo t('total_sales'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todaySales; ?></h3>
                            <p><?php echo t('today_sales'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totalRevenue, 0); ?></h3>
                            <p><?php echo t('total_revenue'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sales Table - Day by Day -->
                <?php if (!empty($salesByDate)): ?>
                    <?php foreach ($salesByDate as $date => $dayData): ?>
                    <div class="dashboard-card" style="margin-bottom: 20px;">
                        <div class="card-header" style="background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white;">
                            <h2 style="margin: 0; font-size: 1.2rem;">
                                <i class="fas fa-calendar-day"></i> 
                                <?php echo date('l, F j, Y', strtotime($date)); ?>
                            </h2>
                            <div style="display: flex; gap: 20px; font-size: 0.95rem;">
                                <span><i class="fas fa-shopping-bag"></i> <?php echo $dayData['count']; ?> Sales</span>
                                <span><i class="fas fa-boxes"></i> <?php echo $dayData['total_quantity']; ?> Items</span>
                                <span><?php echo number_format($dayData['total_revenue'], 0); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('id'); ?></th>
                                            <th><?php echo t('product'); ?></th>
                                            <th><?php echo t('qty'); ?></th>
                                            <th><?php echo t('price'); ?></th>
                                            <th><?php echo t('total'); ?></th>
                                            <th><?php echo t('time'); ?></th>
                                            <?php if (hasPermission('can_users')): ?>
                                            <th><?php echo t('user'); ?></th>
                                            <?php endif; ?>
                                            <th><?php echo t('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dayData['sales'] as $sale): ?>
                                        <tr>
                                            <td data-label="<?php echo t('id'); ?>">#<?php echo $sale['id']; ?></td>
                                            <td data-label="<?php echo t('product'); ?>"><strong><?php echo htmlspecialchars($sale['product_name']); ?></strong></td>
                                            <td data-label="<?php echo t('qty'); ?>"><?php echo $sale['quantity']; ?></td>
                                            <td data-label="<?php echo t('price'); ?>"><?php echo number_format($sale['price'], 0); ?></td>
                                            <td data-label="<?php echo t('total'); ?>"><strong style="color: #FF6B35;"><?php echo number_format($sale['total'], 0); ?></strong></td>
                                            <td data-label="<?php echo t('time'); ?>"><?php echo $sale['created_at'] ? date('g:i A', strtotime($sale['created_at'])) : '-'; ?></td>
                                            <?php if (hasPermission('can_users')): ?>
                                            <td data-label="<?php echo t('user'); ?>">
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($sale['username'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?action=edit&id=<?php echo $sale['id']; ?><?php echo $filterUserId ? '&filter_user=' . $filterUserId : ''; ?><?php echo $monthFilter ? '&month=' . $monthFilter : ''; ?><?php echo $dateFilter ? '&date=' . $dateFilter : ''; ?>" class="btn btn-sm btn-primary" title="Edit" style="margin-right: 5px;">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this sale record?');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="delete_id" value="<?php echo $sale['id']; ?>">
                                                        <button type="submit" name="delete_sale" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot style="background: #f8f9fa; font-weight: 600;">
                                        <tr>
                                            <td colspan="2" style="text-align: right; padding-right: 15px;"><?php echo t('day_total'); ?>:</td>
                                            <td><?php echo $dayData['total_quantity']; ?></td>
                                            <td></td>
                                            <td><strong style="color: #28a745; font-size: 1.1rem;"><?php echo number_format($dayData['total_revenue'], 0); ?></strong></td>
                                            <td colspan="<?php echo hasPermission('can_users') ? '3' : '2'; ?>"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="dashboard-card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h3><?php echo t('no_sales_yet'); ?></h3>
                            <p><?php echo t('start_first_sale'); ?></p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('buy_products'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Unified Filter Modal -->
    <div id="filterModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: white; margin: 10% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="padding: 20px 25px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.3rem; font-weight: 600;"><i class="fas fa-filter"></i> <?php echo t('filter_products'); ?></h3>
                <span class="close" onclick="closeFilterModal()" style="color: white; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <?php if (hasPermission('can_users')): ?>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modalUserFilter" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;"><i class="fas fa-user"></i> <?php echo t('filter_by_user'); ?></label>
                    <select id="modalUserFilter" class="form-control" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="0"><?php echo t('all_users'); ?></option>
                        <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filterUserId == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modalDateFilter" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;"><i class="fas fa-calendar-day"></i> <?php echo t('filter_by_date'); ?></label>
                    <input type="date" id="modalDateFilter" class="form-control" value="<?php echo isset($_GET['date']) ? $dateFilter : ''; ?>" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>
                <div class="form-group">
                    <label for="modalMonthFilter" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;"><i class="fas fa-calendar-alt"></i> <?php echo t('filter_by_month'); ?></label>
                    <select id="modalMonthFilter" class="form-control" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value=""><?php echo t('all_months'); ?></option>
                        <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo $month['month_value']; ?>" <?php echo ($monthFilter === $month['month_value']) ? 'selected' : ''; ?>>
                            <?php echo date('F Y', strtotime($month['month_date'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle"></i> <?php echo t('only_months_with_sales'); ?>
                    </small>
                </div>
            </div>
            <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                <button onclick="resetFilters()" class="btn btn-secondary" style="padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem;">
                    <i class="fas fa-undo"></i> <?php echo t('reset'); ?>
                </button>
                <button onclick="applyFilters()" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white;">
                    <i class="fas fa-check"></i> <?php echo t('apply_filters'); ?>
                </button>
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

        function updateProductPrice() {
            const select = document.getElementById('product_id');
            const selectedOption = select.options[select.selectedIndex];
            const price = parseInt(selectedOption.getAttribute('data-price')) || 0;
            const name = selectedOption.getAttribute('data-name') || '';

            document.getElementById('price').value = price;
            document.getElementById('product_name').value = name;
            updateTotal();
        }

        function updateTotal() {
            const price = parseInt(document.getElementById('price').value) || 0;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const total = price * quantity;
            document.getElementById('total_display').value = total.toLocaleString();
        }

        // Unified filter modal functions
        function openFilterModal() {
            document.getElementById('filterModal').style.display = 'flex';
            document.getElementById('filterModal').style.alignItems = 'center';
            document.getElementById('filterModal').style.justifyContent = 'center';
        }

        function closeFilterModal() {
            document.getElementById('filterModal').style.display = 'none';
        }

        function applyFilters() {
            const url = new URL(window.location.href);
            
            <?php if (hasPermission('can_users')): ?>
            const userId = document.getElementById('modalUserFilter').value;
            if (userId && userId != '0') {
                url.searchParams.set('filter_user', userId);
            } else {
                url.searchParams.delete('filter_user');
            }
            <?php endif; ?>
            
            const date = document.getElementById('modalDateFilter').value;
            if (date) {
                url.searchParams.set('date', date);
            } else {
                url.searchParams.delete('date');
            }
            
            const month = document.getElementById('modalMonthFilter').value;
            if (month) {
                url.searchParams.set('month', month);
            } else {
                url.searchParams.delete('month');
            }
            
            window.location.href = url.toString();
        }

        function resetFilters() {
            const url = new URL(window.location.href);
            url.searchParams.delete('filter_user');
            url.searchParams.delete('date');
            url.searchParams.delete('month');
            window.location.href = url.toString();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('filterModal');
            if (event.target === modal) {
                closeFilterModal();
            }
        }
        // Auto-refresh at midnight to show new day's data
        function checkMidnight() {
            const now = new Date();
            const midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            const timeUntilMidnight = midnight - now;
            
            setTimeout(() => {
                window.location.reload();
            }, timeUntilMidnight);
        }
        checkMidnight();    </script>
</body>
</html>
