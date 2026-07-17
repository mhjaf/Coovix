<?php
require_once 'config/database.php';
require_once 'config/lang.php';
requirePermission('users');

$conn = getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    // Add new user
    if ($action === 'add') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

        if (empty($username) || empty($password)) {
            $message = __('fill_required_fields');
            $messageType = 'error';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long';
            $messageType = 'error';
        } elseif (empty($permissions)) {
            $message = 'Please select at least one permission';
            $messageType = 'error';
        } else {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Username already exists';
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $permissionsJson = json_encode($permissions);
                $stmt = $conn->prepare("INSERT INTO admins (username, password, permissions) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashedPassword, $permissionsJson);

                if ($stmt->execute()) {
                    $message = __('user_added');
                    $messageType = 'success';
                } else {
                    $message = 'Error creating user';
                    $messageType = 'error';
                }
            }
            $stmt->close();
        }
    }

    // Update user
    if ($action === 'update') {
        $id = (int)$_POST['user_id'];
        $username = sanitize($_POST['username']);
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($username)) {
            $message = __('required_field');
            $messageType = 'error';
        } elseif (!empty($newPassword) && strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters long';
            $messageType = 'error';
        } elseif (empty($permissions)) {
            $message = 'Please select at least one permission';
            $messageType = 'error';
        } else {
            $permissionsJson = json_encode($permissions);

            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ?, permissions = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $hashedPassword, $permissionsJson, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username = ?, permissions = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $permissionsJson, $id);
            }

            if ($stmt->execute()) {
                $message = __('user_updated');
                $messageType = 'success';
            } else {
                $message = 'Error updating user';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    // Delete user
    if ($action === 'delete') {
        $id = (int)$_POST['user_id'];

        // Don't allow deleting yourself
        if ($id == $_SESSION['admin_id']) {
            $message = __('cannot_delete_self');
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = __('user_deleted');
                $messageType = 'success';
            } else {
                $message = 'Error deleting user';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Get all users
$users = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" dir="<?php echo getLangDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('users'); ?> - Coovix Admin</title>
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
                <?php if (hasPermission('bookings')): ?><a href="bookings.php"><?php echo __('bookings'); ?></a><?php endif; ?>
                <?php if (hasPermission('reports')): ?><a href="reports.php"><?php echo __('reports'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="settings_new.php"><?php echo __('settings'); ?></a><?php endif; ?>
                <?php if (hasPermission('settings')): ?><a href="work_time.php"><?php echo __('work_time'); ?></a><?php endif; ?>
                <?php if (hasPermission('users')): ?><a href="users.php" class="active"><?php echo __('users'); ?></a><?php endif; ?>

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
                <h1><?php echo __('manage_users'); ?></h1>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Add New User -->
            <div class="card">
                <div class="card-header" style="cursor: pointer;" onclick="toggleCreateUserForm()">
                    <h2><?php echo __('add_user'); ?> <span id="toggleIcon">▼</span></h2>
                </div>
                <div id="createUserForm" style="display: none;">
                    <form method="POST" class="service-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username"><?php echo __('username'); ?> *</label>
                                <input type="text" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password"><?php echo __('password'); ?> *</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('permissions'); ?> *</label>
                            <div class="permissions-checkboxes">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[]" value="dashboard"> <?php echo __('dashboard'); ?>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[]" value="bookings"> <?php echo __('bookings'); ?>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[]" value="reports"> <?php echo __('reports'); ?>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[]" value="settings"> <?php echo __('settings'); ?>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[]" value="users"> <?php echo __('users'); ?>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo __('add_user'); ?></button>
                    </form>
                </div>
            </div>

            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h2><?php echo __('manage_users'); ?></h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo __('id'); ?></th>
                            <th><?php echo __('username'); ?></th>
                            <th><?php echo __('permissions'); ?></th>
                            <th><?php echo __('date'); ?></th>
                            <th><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td>
                                        <form method="POST" class="inline-edit-form" id="form-<?php echo $user['id']; ?>">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="inline-input">
                                    </td>
                                    <td>
                                        <?php
                                        $userPerms = !empty($user['permissions']) ? json_decode($user['permissions'], true) : [];
                                        if (!is_array($userPerms)) $userPerms = [];
                                        ?>
                                        <div class="permissions-checkboxes-inline">
                                            <label class="checkbox-label-small">
                                                <input type="checkbox" name="permissions[]" value="dashboard" <?php echo in_array('dashboard', $userPerms) ? 'checked' : ''; ?>> <?php echo __('dashboard'); ?>
                                            </label>
                                            <label class="checkbox-label-small">
                                                <input type="checkbox" name="permissions[]" value="bookings" <?php echo in_array('bookings', $userPerms) ? 'checked' : ''; ?>> <?php echo __('bookings'); ?>
                                            </label>
                                            <label class="checkbox-label-small">
                                                <input type="checkbox" name="permissions[]" value="reports" <?php echo in_array('reports', $userPerms) ? 'checked' : ''; ?>> <?php echo __('reports'); ?>
                                            </label>
                                            <label class="checkbox-label-small">
                                                <input type="checkbox" name="permissions[]" value="settings" <?php echo in_array('settings', $userPerms) ? 'checked' : ''; ?>> <?php echo __('settings'); ?>
                                            </label>
                                            <label class="checkbox-label-small">
                                                <input type="checkbox" name="permissions[]" value="users" <?php echo in_array('users', $userPerms) ? 'checked' : ''; ?>> <?php echo __('users'); ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions-cell">
                                            <input type="password" name="new_password" placeholder="<?php echo __('password'); ?> (optional)" class="inline-input" style="margin-bottom: 5px;">
                                            <button type="submit" class="btn btn-small btn-success"><?php echo __('save_changes'); ?></button>
                                        </form>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('<?php echo __('confirm_delete_user'); ?>');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger"><?php echo __('delete'); ?></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function toggleCreateUserForm() {
            const form = document.getElementById('createUserForm');
            const icon = document.getElementById('toggleIcon');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                icon.textContent = '▲';
            } else {
                form.style.display = 'none';
                icon.textContent = '▼';
            }
        }

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
