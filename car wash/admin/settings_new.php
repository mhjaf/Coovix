<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('settings');

$conn = getConnection();
$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'services';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // AJAX price updates don't have CSRF token from form, skip for those
    if ($action !== 'update_price' && $action !== 'get_price_matrix') {
        requireCSRF();
    }

    // === CATEGORY ACTIONS ===
    if ($action === 'add_category') {
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');

        if (empty($name)) {
            $message = 'Category name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, name_ku, name_ar) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $name_ku, $name_ar);

            if ($stmt->execute()) {
                $message = 'Category added successfully';
                $messageType = 'success';
                $activeTab = 'categories';
            } else {
                $message = 'Error adding category';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_category') {
        $id = (int)$_POST['category_id'];
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $status = sanitize($_POST['status']);

        if (empty($name)) {
            $message = 'Category name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, name_ku = ?, name_ar = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $name_ku, $name_ar, $status, $id);

            if ($stmt->execute()) {
                $message = 'Category updated successfully';
                $messageType = 'success';
                $activeTab = 'categories';
            } else {
                $message = 'Error updating category';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_category') {
        $id = (int)$_POST['category_id'];

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Category deleted successfully';
            $messageType = 'success';
            $activeTab = 'categories';
        } else {
            $message = 'Error deleting category';
            $messageType = 'error';
        }
        $stmt->close();
    }

    // === CAR TYPE ACTIONS ===
    if ($action === 'add_cartype') {
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');

        if (empty($name)) {
            $message = 'Car type name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO car_types (name, name_ku, name_ar) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $name_ku, $name_ar);

            if ($stmt->execute()) {
                $message = 'Car type added successfully';
                $messageType = 'success';
                $activeTab = 'cartypes';
            } else {
                $message = 'Error adding car type';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_cartype') {
        $id = (int)$_POST['cartype_id'];
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $status = sanitize($_POST['status']);

        if (empty($name)) {
            $message = 'Car type name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE car_types SET name = ?, name_ku = ?, name_ar = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $name_ku, $name_ar, $status, $id);

            if ($stmt->execute()) {
                $message = 'Car type updated successfully';
                $messageType = 'success';
                $activeTab = 'cartypes';
            } else {
                $message = 'Error updating car type';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_cartype') {
        $id = (int)$_POST['cartype_id'];

        $stmt = $conn->prepare("DELETE FROM car_types WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Car type deleted successfully';
            $messageType = 'success';
            $activeTab = 'cartypes';
        } else {
            $message = 'Error deleting car type';
            $messageType = 'error';
        }
        $stmt->close();
    }

    // === WASH TYPE ACTIONS ===
    if ($action === 'add_washtype') {
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');

        if (empty($name)) {
            $message = 'Wash type name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO wash_types (name, name_ku, name_ar) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $name_ku, $name_ar);

            if ($stmt->execute()) {
                $message = 'Wash type added successfully';
                $messageType = 'success';
                $activeTab = 'washtypes';
            } else {
                $message = 'Error adding wash type';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_washtype') {
        $id = (int)$_POST['washtype_id'];
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $status = sanitize($_POST['status']);

        if (empty($name)) {
            $message = 'Wash type name is required';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE wash_types SET name = ?, name_ku = ?, name_ar = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $name_ku, $name_ar, $status, $id);

            if ($stmt->execute()) {
                $message = 'Wash type updated successfully';
                $messageType = 'success';
                $activeTab = 'washtypes';
            } else {
                $message = 'Error updating wash type';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_washtype') {
        $id = (int)$_POST['washtype_id'];

        $stmt = $conn->prepare("DELETE FROM wash_types WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Wash type deleted successfully';
            $messageType = 'success';
            $activeTab = 'washtypes';
        } else {
            $message = 'Error deleting wash type';
            $messageType = 'error';
        }
        $stmt->close();
    }

    // === SERVICE ACTIONS ===
    if ($action === 'add_service') {
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $price = (float)$_POST['price'];
        $description = sanitize($_POST['description']);
        $description_ku = sanitize($_POST['description_ku'] ?? '');
        $description_ar = sanitize($_POST['description_ar'] ?? '');
        $category = sanitize($_POST['category']);

        if (empty($name) || $price <= 0 || empty($category)) {
            $message = 'Please fill in all required fields';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO services (name, name_ku, name_ar, price, description, description_ku, description_ar, category, car_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'All Types')");
            $stmt->bind_param("sssdssss", $name, $name_ku, $name_ar, $price, $description, $description_ku, $description_ar, $category);

            if ($stmt->execute()) {
                $message = 'Service added successfully';
                $messageType = 'success';
                $activeTab = 'services';
            } else {
                $message = 'Error adding service';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_service') {
        $id = (int)$_POST['service_id'];
        $name = sanitize($_POST['name']);
        $name_ku = sanitize($_POST['name_ku'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $price = (float)$_POST['price'];
        $description = sanitize($_POST['description']);
        $description_ku = sanitize($_POST['description_ku'] ?? '');
        $description_ar = sanitize($_POST['description_ar'] ?? '');
        $category = sanitize($_POST['category']);
        $status = sanitize($_POST['status']);

        if (empty($name) || $price <= 0 || empty($category)) {
            $message = 'Please fill in all required fields';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE services SET name = ?, name_ku = ?, name_ar = ?, price = ?, description = ?, description_ku = ?, description_ar = ?, category = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssdsssssi", $name, $name_ku, $name_ar, $price, $description, $description_ku, $description_ar, $category, $status, $id);

            if ($stmt->execute()) {
                $message = 'Service updated successfully';
                $messageType = 'success';
                $activeTab = 'services';
            } else {
                $message = 'Error updating service';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_service') {
        $id = (int)$_POST['service_id'];

        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Service deleted successfully';
            $messageType = 'success';
            $activeTab = 'services';
        } else {
            $message = 'Error deleting service';
            $messageType = 'error';
        }
        $stmt->close();
    }

    // === PRICE MATRIX ACTIONS (AJAX) ===
    if ($action === 'update_price') {
        header('Content-Type: application/json');
        $service_id = (int)$_POST['service_id'];
        $car_type_id = (int)$_POST['car_type_id'];
        $wash_type_id = (int)$_POST['wash_type_id'];
        $price = (float)$_POST['price'];

        if ($service_id <= 0 || $car_type_id <= 0 || $wash_type_id <= 0 || $price < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO service_pricing (service_id, car_type_id, wash_type_id, price)
                                VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE price = ?");
        $stmt->bind_param("iiidd", $service_id, $car_type_id, $wash_type_id, $price, $price);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Price updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_price_matrix') {
        header('Content-Type: application/json');

        $priceMatrix = [];
        $result = $conn->query("
            SELECT s.id as service_id, s.name as service_name, s.category, s.price as base_price,
                   ct.id as car_type_id, ct.name as car_type_name, ct.icon as car_type_icon,
                   COALESCE(sp.price, s.price) as price
            FROM services s
            CROSS JOIN car_types ct
            LEFT JOIN service_pricing sp ON s.id = sp.service_id AND ct.id = sp.car_type_id
            WHERE s.status = 'active' AND ct.status = 'active' AND ct.name != 'All Types'
            ORDER BY s.category, s.name, ct.name
        ");

        while ($row = $result->fetch_assoc()) {
            $priceMatrix[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $priceMatrix]);
        exit;
    }
}

// Get data
$categories = $conn->query("SELECT id, name, name_ku, name_ar, icon, status FROM categories ORDER BY name");
$carTypes = $conn->query("SELECT id, name, name_ku, name_ar, icon, status FROM car_types ORDER BY name");
$washTypes = $conn->query("SELECT id, name, name_ku, name_ar, icon, status FROM wash_types ORDER BY name");
$services = $conn->query("SELECT id, name, name_ku, name_ar, price, description, description_ku, description_ar, category, car_type, status FROM services ORDER BY category, name");

// Group services by category for display
$servicesByCategory = [];
$servicesResult = $conn->query("SELECT id, name, name_ku, name_ar, price, description, description_ku, description_ar, category, car_type, status FROM services ORDER BY category, name");
while ($service = $servicesResult->fetch_assoc()) {
    $servicesByCategory[$service['category']][] = $service;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('settings'); ?> - Coovix Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <?php if (isRTL()): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, sans-serif; }
    </style>
    <?php endif; ?>
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: #374151;
        }

        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #111827;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .item-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .item-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .item-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #111827;
        }

        .item-icon {
            font-size: 1.5rem;
        }

        .item-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .item-status.active {
            background: #dcfce7;
            color: #166534;
        }

        .item-status.inactive {
            background: #fef2f2;
            color: #991b1b;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #eef2ff;
            color: #6366f1;
            border: 1px solid #c7d2fe;
        }

        .btn-edit:hover {
            background: #6366f1;
            color: white;
        }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            margin: 16px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            padding: 24px 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #111827;
        }

        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .modal-close:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #1f2937;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Price Matrix Styles */
        .price-category-section {
            margin-bottom: 40px;
        }

        .price-category-title {
            color: #6366f1;
            font-size: 1.25rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .price-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .price-table thead {
            background: #f9fafb;
        }

        .price-table th {
            padding: 14px 12px;
            text-align: center;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        .price-table th.service-col {
            text-align: left;
            min-width: 180px;
        }

        .price-table th.base-price-col {
            min-width: 100px;
            background: #f3f4f6;
        }

        .price-table th.cartype-col {
            min-width: 110px;
        }

        .cartype-icon {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }

        .price-table tbody tr {
            transition: background-color 0.2s;
        }

        .price-table tbody tr:hover {
            background: #f9fafb;
        }

        .price-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        .price-table td.service-name {
            text-align: left;
            font-weight: 500;
            color: #111827;
        }

        .price-table td.base-price {
            background: #f3f4f6;
            color: #6b7280;
            font-weight: 500;
        }

        .price-cell {
            position: relative;
        }

        .price-input {
            width: 80px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
            background: #ffffff;
            color: #1f2937;
            transition: all 0.2s;
        }

        .price-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .price-input.saving {
            background: #fef3c7;
            border-color: #f59e0b;
        }

        .price-input.saved {
            background: #dcfce7;
            border-color: #22c55e;
        }

        .price-input.error {
            background: #fef2f2;
            border-color: #ef4444;
        }

        .price-status {
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        /* Wash Type Sub-Tabs */
        .wash-type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            padding: 5px;
            background: #f3f4f6;
            border-radius: 12px;
            width: fit-content;
        }

        .wash-type-tab {
            padding: 10px 20px;
            border: none;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .wash-type-tab:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .wash-type-tab.active {
            background: #6366f1;
            color: white;
        }

        .wash-type-content {
            display: none;
        }

        .wash-type-content.active {
            display: block;
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
                <?php if (hasPermission('settings')): ?><a href="settings_new.php" class="active"><?php echo __('settings'); ?></a><?php endif; ?>
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
                        <a href="?lang=en&tab=<?php echo $activeTab; ?>" class="lang-option <?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇬🇧</span> English
                        </a>
                        <a href="?lang=ku&tab=<?php echo $activeTab; ?>" class="lang-option <?php echo getCurrentLang() === 'ku' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇮🇶</span> کوردی
                        </a>
                        <a href="?lang=ar&tab=<?php echo $activeTab; ?>" class="lang-option <?php echo getCurrentLang() === 'ar' ? 'active' : ''; ?>">
                            <span class="lang-flag">🇸🇦</span> العربية
                        </a>
                    </div>
                </div>

                <a href="logout.php"><?php echo __('logout'); ?></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1><?php echo __('settings'); ?></h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button type="button" class="tab <?php echo $activeTab === 'services' ? 'active' : ''; ?>" data-tab="services" onclick="showTab('services')"><?php echo __('services'); ?></button>
                <button type="button" class="tab <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" data-tab="categories" onclick="showTab('categories')"><?php echo __('categories'); ?></button>
                <button type="button" class="tab <?php echo $activeTab === 'cartypes' ? 'active' : ''; ?>" data-tab="cartypes" onclick="showTab('cartypes')"><?php echo __('car_types'); ?></button>
                <button type="button" class="tab <?php echo $activeTab === 'washtypes' ? 'active' : ''; ?>" data-tab="washtypes" onclick="showTab('washtypes')"><?php echo __('wash_types'); ?></button>
                <button type="button" class="tab <?php echo $activeTab === 'prices' ? 'active' : ''; ?>" data-tab="prices" onclick="showTab('prices')"><?php echo __('price_matrix'); ?></button>
            </div>

            <!-- Services Tab -->
            <div id="services-tab" class="tab-content <?php echo $activeTab === 'services' ? 'active' : ''; ?>">
                <div class="page-header">
                    <h2><?php echo __('manage_services'); ?></h2>
                    <button type="button" class="btn-add" onclick="openModal('addServiceModal')">+ <?php echo __('add_service'); ?></button>
                </div>

                <?php foreach ($servicesByCategory as $categoryName => $categoryServices): ?>
                    <div style="margin-bottom: 40px;">
                        <h3 style="color: #6366f1; font-size: 1.25rem; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                            <?php echo htmlspecialchars($categoryName); ?> Services
                        </h3>
                        <div class="items-grid">
                            <?php foreach ($categoryServices as $service): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <div class="item-title">
                                            <span><?php echo htmlspecialchars(getLocalizedName($service)); ?></span>
                                        </div>
                                        <span class="item-status <?php echo $service['status']; ?>"><?php echo $service['status']; ?></span>
                                    </div>
                                    <div style="color: #6b7280; margin-bottom: 10px;">
                                        <div>💰 Price: <strong style="color: #111827;"><?php echo number_format($service['price'], 0); ?></strong></div>
                                        <div>🚗 Car Type: <strong style="color: #111827;"><?php echo htmlspecialchars($service['car_type']); ?></strong></div>
                                    </div>
                                    <div class="item-actions">
                                        <button type="button" class="btn-edit" onclick='editService(<?php echo json_encode($service); ?>)'>✏️ Edit</button>
                                        <form method="POST" style="flex:1;" onsubmit="return confirm('Delete this service?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_service">
                                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                            <button type="submit" class="btn-delete" style="width:100%;">🗑️ Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Categories Tab -->
            <div id="categories-tab" class="tab-content <?php echo $activeTab === 'categories' ? 'active' : ''; ?>">
                <div class="page-header">
                    <h2><?php echo __('manage_categories'); ?></h2>
                    <button type="button" class="btn-add" onclick="openModal('addCategoryModal')">+ <?php echo __('add_category'); ?></button>
                </div>

                <div class="items-grid">
                    <?php 
                    $categories->data_seek(0);
                    while ($category = $categories->fetch_assoc()): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <div class="item-title">
                                    <span><?php echo htmlspecialchars(getLocalizedName($category)); ?></span>
                                </div>
                                <span class="item-status <?php echo $category['status']; ?>"><?php echo $category['status']; ?></span>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-edit" onclick='editCategory(<?php echo json_encode($category); ?>)'>✏️ Edit</button>
                                <form method="POST" style="flex:1;" onsubmit="return confirm('Delete this category?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="btn-delete" style="width:100%;">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Car Types Tab -->
            <div id="cartypes-tab" class="tab-content <?php echo $activeTab === 'cartypes' ? 'active' : ''; ?>">
                <div class="page-header">
                    <h2><?php echo __('manage_car_types'); ?></h2>
                    <button type="button" class="btn-add" onclick="openModal('addCarTypeModal')">+ <?php echo __('add_car_type'); ?></button>
                </div>

                <div class="items-grid">
                    <?php
                    $carTypes->data_seek(0);
                    while ($carType = $carTypes->fetch_assoc()): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <div class="item-title">
                                    <span><?php echo htmlspecialchars(getLocalizedName($carType)); ?></span>
                                </div>
                                <span class="item-status <?php echo $carType['status']; ?>"><?php echo $carType['status']; ?></span>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-edit" onclick='editCarType(<?php echo json_encode($carType); ?>)'>✏️ Edit</button>
                                <form method="POST" style="flex:1;" onsubmit="return confirm('Delete this car type?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_cartype">
                                    <input type="hidden" name="cartype_id" value="<?php echo $carType['id']; ?>">
                                    <button type="submit" class="btn-delete" style="width:100%;">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Wash Types Tab -->
            <div id="washtypes-tab" class="tab-content <?php echo $activeTab === 'washtypes' ? 'active' : ''; ?>">
                <div class="page-header">
                    <h2><?php echo __('manage_wash_types'); ?></h2>
                    <button type="button" class="btn-add" onclick="openModal('addWashTypeModal')">+ <?php echo __('add_wash_type'); ?></button>
                </div>

                <div class="items-grid">
                    <?php
                    $washTypes->data_seek(0);
                    while ($washType = $washTypes->fetch_assoc()): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <div class="item-title">
                                    <span><?php echo htmlspecialchars(getLocalizedName($washType)); ?></span>
                                </div>
                                <span class="item-status <?php echo $washType['status']; ?>"><?php echo $washType['status']; ?></span>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-edit" onclick='editWashType(<?php echo json_encode($washType); ?>)'>✏️ Edit</button>
                                <form method="POST" style="flex:1;" onsubmit="return confirm('Delete this wash type?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_washtype">
                                    <input type="hidden" name="washtype_id" value="<?php echo $washType['id']; ?>">
                                    <button type="submit" class="btn-delete" style="width:100%;">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Price Matrix Tab -->
            <div id="prices-tab" class="tab-content <?php echo $activeTab === 'prices' ? 'active' : ''; ?>">
                <div class="page-header">
                    <h2><?php echo __('price_matrix'); ?></h2>
                    <p style="color: #6b7280; margin: 0;"><?php echo __('set_prices'); ?></p>
                </div>

                <?php
                // Get wash types for sub-tabs
                $washTypesForMatrix = [];
                $washTypesMatrixResult = $conn->query("SELECT id, name, name_ku, name_ar, icon FROM wash_types WHERE status = 'active' ORDER BY id");
                while ($wt = $washTypesMatrixResult->fetch_assoc()) {
                    $washTypesForMatrix[] = $wt;
                }

                // Get car types for table headers (excluding "All Types")
                $carTypesForMatrix = [];
                $carTypesResult = $conn->query("SELECT id, name, name_ku, name_ar, icon FROM car_types WHERE status = 'active' AND name != 'All Types' ORDER BY name");
                while ($ct = $carTypesResult->fetch_assoc()) {
                    $carTypesForMatrix[] = $ct;
                }

                // Get services grouped by category
                $servicesForMatrix = [];
                $servicesMatrixResult = $conn->query("SELECT id, name, name_ku, name_ar, category, price FROM services WHERE status = 'active' ORDER BY category, name");
                while ($srv = $servicesMatrixResult->fetch_assoc()) {
                    $servicesForMatrix[$srv['category']][] = $srv;
                }

                // Get existing prices (including wash_type_id)
                $existingPrices = [];
                $pricesResult = $conn->query("SELECT service_id, car_type_id, wash_type_id, price FROM service_pricing");
                while ($p = $pricesResult->fetch_assoc()) {
                    $existingPrices[$p['wash_type_id']][$p['service_id']][$p['car_type_id']] = $p['price'];
                }
                ?>

                <!-- Wash Type Sub-Tabs -->
                <div class="wash-type-tabs">
                    <?php foreach ($washTypesForMatrix as $index => $wt): ?>
                    <button type="button" class="wash-type-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-wash-type="<?php echo $wt['id']; ?>"
                            onclick="showWashTypeTab(<?php echo $wt['id']; ?>)">
                        <?php echo htmlspecialchars(getLocalizedName($wt)); ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($washTypesForMatrix as $index => $washType): ?>
                <div id="washtype-<?php echo $washType['id']; ?>-content" class="wash-type-content <?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php foreach ($servicesForMatrix as $categoryName => $categoryServices): ?>
                    <div class="price-category-section">
                        <h3 class="price-category-title"><?php echo htmlspecialchars($categoryName); ?></h3>
                        <div class="price-table-wrapper">
                            <table class="price-table">
                                <thead>
                                    <tr>
                                        <th class="service-col">Service</th>
                                        <th class="base-price-col">Base Price</th>
                                        <?php foreach ($carTypesForMatrix as $ct): ?>
                                        <th class="cartype-col">
                                            <span><?php echo htmlspecialchars(getLocalizedName($ct)); ?></span>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryServices as $service): ?>
                                    <tr>
                                        <td class="service-name"><?php echo htmlspecialchars(getLocalizedName($service)); ?></td>
                                        <td class="base-price"><?php echo number_format($service['price'], 0); ?></td>
                                        <?php foreach ($carTypesForMatrix as $ct):
                                            $currentPrice = isset($existingPrices[$washType['id']][$service['id']][$ct['id']])
                                                ? $existingPrices[$washType['id']][$service['id']][$ct['id']]
                                                : $service['price'];
                                        ?>
                                        <td class="price-cell">
                                            <input type="number"
                                                   class="price-input"
                                                   data-service-id="<?php echo $service['id']; ?>"
                                                   data-car-type-id="<?php echo $ct['id']; ?>"
                                                   data-wash-type-id="<?php echo $washType['id']; ?>"
                                                   data-original="<?php echo $currentPrice; ?>"
                                                   value="<?php echo number_format($currentPrice, 0, '', ''); ?>"
                                                   min="0"
                                                   step="1">
                                            <span class="price-status"></span>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <?php if (empty($servicesForMatrix)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💰</div>
                    <p>No active services found. Add services first to set up pricing.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Category Modal -->
    <div class="modal-overlay" id="addCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add Category</h2>
                <button type="button" class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" placeholder="Enter category name in English" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" placeholder="ناوی جۆر بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" placeholder="اسم الفئة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <button type="submit" class="btn-submit">Add Category</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal-overlay" id="editCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Category</h2>
                <button type="button" class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCategoryForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" id="edit_category_name" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" id="edit_category_name_ku" placeholder="ناوی جۆر بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" id="edit_category_name_ar" placeholder="اسم الفئة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_category_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Car Type Modal -->
    <div class="modal-overlay" id="addCarTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add Car Type</h2>
                <button type="button" class="modal-close" onclick="closeModal('addCarTypeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_cartype">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" placeholder="Enter car type name in English" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" placeholder="ناوی جۆری ئۆتۆمبێل بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" placeholder="اسم نوع السيارة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <button type="submit" class="btn-submit">Add Car Type</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Car Type Modal -->
    <div class="modal-overlay" id="editCarTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Car Type</h2>
                <button type="button" class="modal-close" onclick="closeModal('editCarTypeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCarTypeForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_cartype">
                    <input type="hidden" name="cartype_id" id="edit_cartype_id">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" id="edit_cartype_name" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" id="edit_cartype_name_ku" placeholder="ناوی جۆری ئۆتۆمبێل بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" id="edit_cartype_name_ar" placeholder="اسم نوع السيارة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_cartype_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Wash Type Modal -->
    <div class="modal-overlay" id="addWashTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add Wash Type</h2>
                <button type="button" class="modal-close" onclick="closeModal('addWashTypeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_washtype">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" required placeholder="e.g., Premium, VIP">
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" placeholder="ناوی جۆری شۆردن بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" placeholder="اسم نوع الغسيل بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <button type="submit" class="btn-submit">Add Wash Type</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Wash Type Modal -->
    <div class="modal-overlay" id="editWashTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Wash Type</h2>
                <button type="button" class="modal-close" onclick="closeModal('editWashTypeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editWashTypeForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_washtype">
                    <input type="hidden" name="washtype_id" id="edit_washtype_id">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name" id="edit_washtype_name" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" id="edit_washtype_name_ku" placeholder="ناوی جۆری شۆردن بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" id="edit_washtype_name_ar" placeholder="اسم نوع الغسيل بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_washtype_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal-overlay" id="addServiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add Service</h2>
                <button type="button" class="modal-close" onclick="closeModal('addServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_service">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()):
                                if ($cat['status'] === 'active'): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endif; endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Service Name (English) *</label>
                        <input type="text" name="name" placeholder="Enter service name in English" required>
                    </div>
                    <div class="form-group">
                        <label>Service Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" placeholder="ناوی خزمەتگوزاری بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Service Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" placeholder="اسم الخدمة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Price *</label>
                        <input type="number" name="price" step="1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description" rows="2" placeholder="Enter service description (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="description_ku" rows="2" placeholder="وەسفی خزمەتگوزاری بە کوردی" dir="rtl" style="text-align: right;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="description_ar" rows="2" placeholder="وصف الخدمة بالعربية" dir="rtl" style="text-align: right;"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Add Service</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal-overlay" id="editServiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Service</h2>
                <button type="button" class="modal-close" onclick="closeModal('editServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editServiceForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_service">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" id="edit_service_category" required>
                            <?php
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()):
                                if ($cat['status'] === 'active'): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endif; endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Service Name (English) *</label>
                        <input type="text" name="name" id="edit_service_name" required>
                    </div>
                    <div class="form-group">
                        <label>Service Name (Kurdish - کوردی)</label>
                        <input type="text" name="name_ku" id="edit_service_name_ku" placeholder="ناوی خزمەتگوزاری بە کوردی" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Service Name (Arabic - العربية)</label>
                        <input type="text" name="name_ar" id="edit_service_name_ar" placeholder="اسم الخدمة بالعربية" dir="rtl" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>Price *</label>
                        <input type="number" name="price" id="edit_service_price" step="1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description" id="edit_service_description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="description_ku" id="edit_service_description_ku" rows="2" placeholder="وەسفی خزمەتگوزاری بە کوردی" dir="rtl" style="text-align: right;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="description_ar" id="edit_service_description_ar" rows="2" placeholder="وصف الخدمة بالعربية" dir="rtl" style="text-align: right;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_service_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Remove active from all tabs and content
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Add active to selected tab and content
            const selectedTab = document.querySelector(`.tab[data-tab="${tabName}"]`);
            const selectedContent = document.getElementById(`${tabName}-tab`);

            if (selectedTab) selectedTab.classList.add('active');
            if (selectedContent) selectedContent.classList.add('active');

            // Update URL
            history.pushState(null, '', `?tab=${tabName}`);
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Edit functions
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.name;
            document.getElementById('edit_category_name_ku').value = category.name_ku || '';
            document.getElementById('edit_category_name_ar').value = category.name_ar || '';
            document.getElementById('edit_category_status').value = category.status;
            openModal('editCategoryModal');
        }

        function editCarType(carType) {
            document.getElementById('edit_cartype_id').value = carType.id;
            document.getElementById('edit_cartype_name').value = carType.name;
            document.getElementById('edit_cartype_name_ku').value = carType.name_ku || '';
            document.getElementById('edit_cartype_name_ar').value = carType.name_ar || '';
            document.getElementById('edit_cartype_status').value = carType.status;
            openModal('editCarTypeModal');
        }

        function editWashType(washType) {
            document.getElementById('edit_washtype_id').value = washType.id;
            document.getElementById('edit_washtype_name').value = washType.name;
            document.getElementById('edit_washtype_name_ku').value = washType.name_ku || '';
            document.getElementById('edit_washtype_name_ar').value = washType.name_ar || '';
            document.getElementById('edit_washtype_status').value = washType.status;
            openModal('editWashTypeModal');
        }

        // Wash Type Tab Switching
        function showWashTypeTab(washTypeId) {
            document.querySelectorAll('.wash-type-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.wash-type-content').forEach(content => content.classList.remove('active'));

            document.querySelector(`.wash-type-tab[data-wash-type="${washTypeId}"]`).classList.add('active');
            document.getElementById(`washtype-${washTypeId}-content`).classList.add('active');
        }

        function editService(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_service_name').value = service.name;
            document.getElementById('edit_service_name_ku').value = service.name_ku || '';
            document.getElementById('edit_service_name_ar').value = service.name_ar || '';
            document.getElementById('edit_service_price').value = Math.round(service.price);
            document.getElementById('edit_service_description').value = service.description || '';
            document.getElementById('edit_service_description_ku').value = service.description_ku || '';
            document.getElementById('edit_service_description_ar').value = service.description_ar || '';
            document.getElementById('edit_service_category').value = service.category;
            document.getElementById('edit_service_status').value = service.status;
            openModal('editServiceModal');
        }

        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Price Matrix - Auto-save on change
        let saveTimeout = null;

        document.querySelectorAll('.price-input').forEach(input => {
            input.addEventListener('change', function() {
                savePrice(this);
            });

            input.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                    savePrice(this);
                }
            });
        });

        function savePrice(input) {
            const serviceId = input.dataset.serviceId;
            const carTypeId = input.dataset.carTypeId;
            const washTypeId = input.dataset.washTypeId;
            const price = parseFloat(input.value) || 0;
            const original = parseFloat(input.dataset.original) || 0;

            // Skip if no change
            if (price === original) return;

            // Show saving state
            input.classList.remove('saved', 'error');
            input.classList.add('saving');

            const formData = new FormData();
            formData.append('action', 'update_price');
            formData.append('service_id', serviceId);
            formData.append('car_type_id', carTypeId);
            formData.append('wash_type_id', washTypeId);
            formData.append('price', price);

            fetch('settings_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                input.classList.remove('saving');
                if (data.success) {
                    input.classList.add('saved');
                    input.dataset.original = price;
                    setTimeout(() => input.classList.remove('saved'), 2000);
                } else {
                    input.classList.add('error');
                    setTimeout(() => input.classList.remove('error'), 2000);
                }
            })
            .catch(error => {
                input.classList.remove('saving');
                input.classList.add('error');
                setTimeout(() => input.classList.remove('error'), 2000);
            });
        }

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
