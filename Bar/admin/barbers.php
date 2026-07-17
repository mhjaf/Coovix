<?php
require_once 'config.php';
requireLogin();

// Check permission
if (!hasPermission('can_barbers')) {
    header('Location: index.php');
    exit();
}

// Add photo column if it doesn't exist
$conn->query("ALTER TABLE barbers ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");

// Add Kurdish and Arabic name columns if they don't exist
$conn->query("ALTER TABLE barbers ADD COLUMN IF NOT EXISTS name_ku VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE barbers ADD COLUMN IF NOT EXISTS name_ar VARCHAR(100) DEFAULT NULL");

// Create uploads directory for barber photos
if (!file_exists('../uploads/barbers')) {
    mkdir('../uploads/barbers', 0755, true);
}

$message = '';
$messageType = '';

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Validate CSRF for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_barber']) && !isset($_POST['toggle_status'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    }
}

// Handle Add Barber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_barber']) && empty($message)) {
    $name = sanitize($conn, $_POST['name']);
    $name_ku = !empty($_POST['name_ku']) ? sanitize($conn, $_POST['name_ku']) : null;
    $name_ar = !empty($_POST['name_ar']) ? sanitize($conn, $_POST['name_ar']) : null;
    $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $status = sanitize($conn, $_POST['status']);
    $photo = null;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/barbers/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = 'barber_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $photo = '/uploads/barbers/' . $fileName;
            } else {
                error_log("Error uploading photo. Path: " . $uploadPath);
                $message = "Error uploading photo. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
            $messageType = "error";
        }
    } else if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Better error messages
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMsg = isset($uploadErrors[$_FILES['photo']['error']]) ? $uploadErrors[$_FILES['photo']['error']] : 'Unknown upload error';
        $message = "Photo upload error: " . $errorMsg;
        $messageType = "error";
    }

    if (!empty($name) && !$message) {
        $sql = "INSERT INTO barbers (name, name_ku, name_ar, user_id, status, photo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiss", $name, $name_ku, $name_ar, $user_id, $status, $photo);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Barber added successfully!";
            $_SESSION['messageType'] = "success";
            header("Location: barbers.php");
            exit();
        } else {
            $message = "Error adding barber. Please try again.";
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Name is required!";
        $messageType = "error";
    }
}

// Handle Delete Barber (POST only with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_barber'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $id = intval($_POST['delete_id']);
        // Delete photo file if exists
        $stmt = $conn->prepare("SELECT photo FROM barbers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['photo'] && file_exists('..' . $row['photo'])) {
                @unlink('..' . $row['photo']);
            }
        }
        $stmt->close();

        $sql = "DELETE FROM barbers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Barber deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting barber.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Handle Update Status (POST only with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $id = intval($_POST['barber_id']);
        $sql = "UPDATE barbers SET status = IF(status='active','inactive','active') WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Barber status updated!";
            $messageType = "success";
        }
        $stmt->close();
    }
}

// Handle Edit Barber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_barber']) && empty($message)) {
    $id = intval($_POST['barber_id']);
    $name = sanitize($conn, $_POST['name']);
    $name_ku = !empty($_POST['name_ku']) ? sanitize($conn, $_POST['name_ku']) : null;
    $name_ar = !empty($_POST['name_ar']) ? sanitize($conn, $_POST['name_ar']) : null;
    $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $status = sanitize($conn, $_POST['status']);
    
    // Validate inputs
    if (empty($name)) {
        $message = "Barber name is required!";
        $messageType = "error";
    } else if (!in_array($status, ['active', 'inactive'])) {
        $message = "Invalid status value!";
        $messageType = "error";
    } else {
        // Get current barber data
        $stmt = $conn->prepare("SELECT photo FROM barbers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentBarber = $result->fetch_assoc();
        $stmt->close();
        
        if (!$currentBarber) {
            $message = "Barber not found!";
            $messageType = "error";
        } else {
            $photo = $currentBarber['photo'];
            $photoUpdated = false;

            // Handle remove photo request
            $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1';

            if ($removePhoto) {
                // Delete the photo file
                if ($photo && file_exists('..' . $photo)) {
                    @unlink('..' . $photo);
                }
                $photo = null;
                $photoUpdated = true;
            }

            // Handle photo upload (only if not removing)
            if (!$removePhoto && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/barbers/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $message = "Failed to create upload directory!";
                            $messageType = "error";
                        }
                    }
                    
                    if (!isset($message)) {
                        // Validate file
                        $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                        $maxFileSize = 5 * 1024 * 1024; // 5MB
                        
                        if (!in_array($fileExtension, $allowedExtensions)) {
                            $message = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
                            $messageType = "error";
                        } else if ($_FILES['photo']['size'] > $maxFileSize) {
                            $message = "File is too large. Maximum size is 5MB.";
                            $messageType = "error";
                        } else {
                            // Generate unique filename
                            $fileName = 'barber_' . $id . '_' . time() . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $fileName;
                            
                            // Attempt upload
                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                                // Delete old photo if exists
                                if ($photo && file_exists('..' . $photo)) {
                                    @unlink('..' . $photo);
                                }
                                $photo = '/uploads/barbers/' . $fileName;
                                $photoUpdated = true;
                            } else {
                                $message = "Error uploading photo. Please check folder permissions (uploads/barbers must be writable).";
                                $messageType = "error";
                            }
                        }
                    }
                } else {
                    // Upload error codes
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
                    ];
                    $errorCode = $_FILES['photo']['error'];
                    $errorMsg = isset($uploadErrors[$errorCode]) ? $uploadErrors[$errorCode] : 'Unknown upload error (' . $errorCode . ')';
                    $message = "Upload error: " . $errorMsg;
                    $messageType = "error";
                }
            }

            // Update barber if no errors
            if (!isset($message) || empty($message)) {
                // Build UPDATE query
                $sql = "UPDATE barbers SET name=?, name_ku=?, name_ar=?, user_id=?, status=?, photo=? WHERE id=?";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $message = "Database error. Please try again.";
                    $messageType = "error";
                } else {
                    $stmt->bind_param("sssissi", $name, $name_ku, $name_ar, $user_id, $status, $photo, $id);
                    
                    if ($stmt->execute()) {
                        if ($photoUpdated) {
                            $_SESSION['message'] = "Barber updated successfully with new photo!";
                        } else {
                            $_SESSION['message'] = "Barber updated successfully!";
                        }
                        $_SESSION['messageType'] = "success";
                        $stmt->close();
                        header("Location: barbers.php");
                        exit();
                    } else {
                        $message = "Error updating barber. Please try again.";
                        $messageType = "error";
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Get barber for editing
$editBarber = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM barbers WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editBarber = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all barbers with linked user info
$barbers = $conn->query("SELECT b.*, u.username as linked_user FROM barbers b LEFT JOIN admin_users u ON b.user_id = u.id ORDER BY b.created_at DESC");

// Get all admin users for dropdown
$adminUsers = $conn->query("SELECT id, username, full_name FROM admin_users WHERE status='active' ORDER BY username ASC");

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" <?php echo isRTL() ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('manage_barbers'); ?> - The Classic Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
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
                    <li class="active">
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
                    <h1><?php echo t('manage_barbers'); ?></h1>
                </div>
                <div class="header-right">
                </div>
            </header>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Add New Barber Button -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <a href="?action=add" class="btn btn-primary" style="width: 100%; max-width: 100%; padding: 15px 20px; font-size: 1rem;">
                        <i class="fas fa-plus"></i> <?php echo t('add_new_barber'); ?>
                    </a>
                </div>
                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
                <?php endif; ?>

                <!-- Add Barber Form -->
                <?php if ($showAddForm): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus"></i> <?php echo t('add_new_barber'); ?></h2>
                        <a href="barbers.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_en'); ?> *
                                    </label>
                                    <input type="text" id="name" name="name" required placeholder="Enter barber name in English">
                                </div>
                                <div class="form-group">
                                    <label for="name_ku">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_ku'); ?>
                                    </label>
                                    <input type="text" id="name_ku" name="name_ku" placeholder="ناوی دەلاک بە کوردی" dir="rtl" style="text-align: right;">
                                </div>
                                <div class="form-group">
                                    <label for="name_ar">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_ar'); ?>
                                    </label>
                                    <input type="text" id="name_ar" name="name_ar" placeholder="اسم الحلاق بالعربية" dir="rtl" style="text-align: right;">
                                </div>
                                <div class="form-group">
                                    <label for="photo">
                                        <i class="fas fa-image"></i> <?php echo t('barber_photo'); ?>
                                    </label>
                                    <input type="file" id="photo" name="photo" accept="image/*" onchange="previewImage(event, 'addPreview')">
                                    <div id="addPreview" class="image-preview" style="margin-top: 10px;"></div>
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
                                <div class="form-group">
                                    <label for="user_id">
                                        <i class="fas fa-user-tag"></i> <?php echo t('linked_admin_user'); ?>
                                    </label>
                                    <select id="user_id" name="user_id">
                                        <option value=""><?php echo t('no_user_all_can_see'); ?></option>
                                        <?php
                                        $adminUsers->data_seek(0);
                                        while ($user = $adminUsers->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['full_name']): ?> (<?php echo htmlspecialchars($user['full_name']); ?>)<?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_barber" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('save_barber'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Barber Form -->
                <?php if ($editBarber): ?>
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-edit"></i> <?php echo t('edit_barber'); ?></h2>
                        <a href="barbers.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="barber_id" value="<?php echo $editBarber['id']; ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="edit_name">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_en'); ?> *
                                    </label>
                                    <input type="text" id="edit_name" name="name" value="<?php echo htmlspecialchars($editBarber['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_name_ku">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_ku'); ?>
                                    </label>
                                    <input type="text" id="edit_name_ku" name="name_ku" value="<?php echo htmlspecialchars($editBarber['name_ku'] ?? ''); ?>" placeholder="ناوی دەلاک بە کوردی" dir="rtl" style="text-align: right;">
                                </div>
                                <div class="form-group">
                                    <label for="edit_name_ar">
                                        <i class="fas fa-user"></i> <?php echo t('barber_name_ar'); ?>
                                    </label>
                                    <input type="text" id="edit_name_ar" name="name_ar" value="<?php echo htmlspecialchars($editBarber['name_ar'] ?? ''); ?>" placeholder="اسم الحلاق بالعربية" dir="rtl" style="text-align: right;">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-image"></i> <?php echo t('barber_photo'); ?>
                                        </label>
                                        <div class="photo-edit-container" style="display: flex; align-items: flex-start; gap: 20px; margin-top: 10px;">
                                            <div id="currentPhotoContainer" style="text-align: center;">
                                                <?php if ($editBarber['photo']): ?>
                                                    <img id="currentPhoto" src="..<?php echo htmlspecialchars($editBarber['photo']); ?>?v=<?php echo time(); ?>" alt="Current Photo" style="width: 120px; height: 120px; border-radius: 12px; object-fit: cover; border: 3px solid #e0e0e0;">
                                                    <div style="margin-top: 8px; font-size: 0.85rem; color: #666;"><?php echo t('current_photo'); ?></div>
                                                <?php else: ?>
                                                    <div style="width: 120px; height: 120px; border-radius: 12px; background: linear-gradient(135deg, #FF6B35, #e55a2b); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: bold;">
                                                        <?php echo strtoupper(substr($editBarber['name'], 0, 1)); ?>
                                                    </div>
                                                    <div style="margin-top: 8px; font-size: 0.85rem; color: #666;"><?php echo t('no_photo'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="flex: 1;">
                                                <div style="margin-bottom: 10px;">
                                                    <input type="file" id="edit_photo" name="photo" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                                    <label for="edit_photo" class="btn btn-primary" style="cursor: pointer; display: inline-block; margin: 0; white-space: nowrap;">
                                                        <i class="fas fa-upload"></i> Upload Photo
                                                    </label>
                                                    <span id="file-name" style="margin-left: 10px; color: #666; font-size: 0.9rem;"></span>
                                                </div>
                                                <script>
                                                    document.getElementById('edit_photo').addEventListener('change', function(e) {
                                                        const file = e.target.files[0];
                                                        if (file) {
                                                            const barberId = <?php echo $editBarber['id']; ?>;
                                                            const fileNameSpan = document.getElementById('file-name');
                                                            const currentPhoto = document.getElementById('currentPhoto');
                                                            const photoContainer = document.getElementById('currentPhotoContainer');
                                                            const uploadLabel = document.querySelector('label[for="edit_photo"]');

                                                            // Show uploading state
                                                            fileNameSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                                                            uploadLabel.style.pointerEvents = 'none';
                                                            uploadLabel.style.opacity = '0.6';

                                                            // Create FormData and upload immediately
                                                            const formData = new FormData();
                                                            formData.append('photo', file);
                                                            formData.append('barber_id', barberId);

                                                            fetch('upload-barber-photo.php', {
                                                                method: 'POST',
                                                                body: formData
                                                            })
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                uploadLabel.style.pointerEvents = 'auto';
                                                                uploadLabel.style.opacity = '1';

                                                                if (data.success) {
                                                                    // Update photo preview
                                                                    if (currentPhoto) {
                                                                        currentPhoto.src = '..' + data.photo;
                                                                        currentPhoto.style.opacity = '1';
                                                                        currentPhoto.style.filter = 'none';
                                                                    } else {
                                                                        photoContainer.innerHTML = '<img id="currentPhoto" src="..' + data.photo + '" alt="Photo" style="width: 120px; height: 120px; border-radius: 12px; object-fit: cover; border: 3px solid #4CAF50;"><div style="margin-top: 8px; font-size: 0.85rem; color: #4CAF50;"><i class="fas fa-check-circle"></i> Photo Updated!</div>';
                                                                    }

                                                                    // Show success message
                                                                    fileNameSpan.innerHTML = '<span style="color: #4CAF50;"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';

                                                                    // Show remove button if it doesn't exist
                                                                    showRemoveButton();

                                                                    // Remove any remove notice
                                                                    const notice = photoContainer.querySelector('.remove-notice');
                                                                    if (notice) notice.remove();

                                                                    // Reset the remove_photo flag
                                                                    document.getElementById('removePhotoInput').value = '0';
                                                                } else {
                                                                    fileNameSpan.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
                                                                }
                                                            })
                                                            .catch(error => {
                                                                uploadLabel.style.pointerEvents = 'auto';
                                                                uploadLabel.style.opacity = '1';
                                                                fileNameSpan.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Upload failed. Please try again.</span>';
                                                                console.error('Upload error:', error);
                                                            });
                                                        }
                                                    });

                                                    function showRemoveButton() {
                                                        const removeBtn = document.getElementById('removePhotoBtn');
                                                        if (removeBtn) {
                                                            removeBtn.style.display = 'inline-block';
                                                        }
                                                    }
                                                </script>
                                                <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">
                                                <button type="button" id="removePhotoBtn" onclick="removePhotoAjax(<?php echo $editBarber['id']; ?>)" class="btn btn-danger" style="margin-bottom: 10px; white-space: nowrap; <?php echo !$editBarber['photo'] ? 'display: none;' : ''; ?>">
                                                    <i class="fas fa-trash"></i> <?php echo t('remove_current_photo'); ?>
                                                </button>
                                                <small style="color: #888; display: block; margin-top: 5px;"><?php echo t('supported_formats'); ?></small>
                                                <small style="color: #666; display: block; margin-top: 8px; font-weight: 500;">
                                                    <i class="fas fa-info-circle" style="color: #FF6B35;"></i> Recommended: 400x400px or larger (square format)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="user_id">
                                            <i class="fas fa-user-tag"></i> <?php echo t('linked_admin_user'); ?>
                                        </label>
                                        <select id="user_id" name="user_id">
                                            <option value=""><?php echo t('no_user_all_can_see'); ?></option>
                                            <?php
                                            $adminUsers->data_seek(0);
                                            while ($user = $adminUsers->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo $editBarber['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['full_name']): ?> (<?php echo htmlspecialchars($user['full_name']); ?>)<?php endif; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($editBarber['status']); ?>">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="edit_barber" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('update_barber'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Barbers Table -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> <?php echo t('all_barbers'); ?> (<?php echo $barbers->num_rows; ?>)</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($barbers->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?php echo t('photo'); ?></th>
                                        <th><?php echo t('barber_name'); ?></th>
                                        <th><?php echo t('linked_admin_user'); ?></th>
                                        <th><?php echo t('status'); ?></th>
                                        <th><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($barber = $barbers->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $barber['id']; ?></td>
                                        <td>
                                            <?php if ($barber['photo']): ?>
                                                <img src="..<?php echo htmlspecialchars($barber['photo']); ?>" alt="<?php echo htmlspecialchars($barber['name']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #FF6B35, #e55a2b); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($barber['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="table-user">
                                                <strong><?php echo htmlspecialchars($barber['name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($barber['linked_user']): ?>
                                            <span class="status-badge active">
                                                <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($barber['linked_user']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="status-badge pending"><?php echo t('all_users_label'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $barber['status']; ?>">
                                                <?php echo t($barber['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $barber['id']; ?>"
                                                   class="btn btn-sm btn-info"
                                                   title="<?php echo t('edit'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display:inline;">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="barber_id" value="<?php echo $barber['id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $barber['status'] === 'active' ? 'warning' : 'success'; ?>" title="<?php echo t('toggle_status'); ?>">
                                                        <i class="fas fa-<?php echo $barber['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo t('confirm_delete_barber'); ?>');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="delete_id" value="<?php echo $barber['id']; ?>">
                                                    <button type="submit" name="delete_barber" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
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
                            <i class="fas fa-user-slash"></i>
                            <h3><?php echo t('no_barbers_found'); ?></h3>
                            <p><?php echo t('add_first_barber'); ?></p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo t('add_first'); ?> <?php echo t('barber'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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

        function previewImage(event, previewId) {
            const file = event.target.files[0];
            const preview = document.getElementById(previewId);

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #e0e0e0;">';
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Reset remove flag when new file is selected
        const editPhotoInput = document.getElementById('edit_photo');
        if (editPhotoInput) {
            editPhotoInput.addEventListener('change', function() {
                const removeInput = document.getElementById('removePhotoInput');
                if (removeInput && this.files.length > 0) {
                    removeInput.value = '0';
                }
            });
        }

        function removePhotoAjax(barberId) {
            if (confirm('Are you sure you want to remove the current photo?')) {
                const removeBtn = document.getElementById('removePhotoBtn');
                const currentPhoto = document.getElementById('currentPhoto');
                const photoContainer = document.getElementById('currentPhotoContainer');
                const fileNameSpan = document.getElementById('file-name');
                const fileInput = document.getElementById('edit_photo');

                // Show loading state
                removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                removeBtn.disabled = true;

                // Create FormData for remove request
                const formData = new FormData();
                formData.append('barber_id', barberId);
                formData.append('remove_photo', '1');

                fetch('upload-barber-photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Get barber initial for placeholder
                        const barberName = document.getElementById('edit_name').value || 'B';
                        const initial = barberName.charAt(0).toUpperCase();

                        // Replace with placeholder
                        photoContainer.innerHTML = '<div style="width: 120px; height: 120px; border-radius: 12px; background: linear-gradient(135deg, #FF6B35, #e55a2b); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: bold;">' + initial + '</div><div style="margin-top: 8px; font-size: 0.85rem; color: #666;">No Photo</div>';

                        // Hide remove button
                        removeBtn.style.display = 'none';
                        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Current Photo';
                        removeBtn.disabled = false;

                        // Clear file input
                        fileInput.value = '';

                        // Show success message
                        fileNameSpan.innerHTML = '<span style="color: #4CAF50;"><i class="fas fa-check-circle"></i> Photo removed successfully!</span>';
                    } else {
                        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Current Photo';
                        removeBtn.disabled = false;
                        fileNameSpan.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
                    }
                })
                .catch(error => {
                    removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Current Photo';
                    removeBtn.disabled = false;
                    fileNameSpan.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Failed to remove photo. Please try again.</span>';
                    console.error('Remove error:', error);
                });
            }
        }
    </script>
</body>
</html>
