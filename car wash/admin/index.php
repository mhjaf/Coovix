<?php
require_once 'config/database.php';

$error = '';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                // Fetch permissions for the user
                $permStmt = $conn->prepare("SELECT permissions FROM admins WHERE id = ?");
                $permStmt->bind_param("i", $admin['id']);
                $permStmt->execute();
                $permResult = $permStmt->get_result();
                $permRow = $permResult->fetch_assoc();
                $permissions = !empty($permRow['permissions']) ? json_decode($permRow['permissions'], true) : [];
                if (!is_array($permissions)) $permissions = [];
                $permStmt->close();

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_permissions'] = $permissions;
                $_SESSION['last_activity'] = time();

                // Redirect to first allowed page
                $redirectPage = 'dashboard.php';
                $pages = ['dashboard' => 'dashboard.php', 'bookings' => 'bookings.php', 'reports' => 'reports.php', 'settings' => 'settings_new.php', 'users' => 'users.php'];
                foreach ($pages as $perm => $page) {
                    if (in_array($perm, $permissions)) {
                        $redirectPage = $page;
                        break;
                    }
                }
                header('Location: ' . $redirectPage);
                exit();
            }
        }

        // Generic error message - don't reveal whether username or password was wrong
        $error = 'Invalid username or password';

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Coovix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0a0a0f;
            overflow: hidden;
        }

        /* Left Side - Branding */
        .login-branding {
            flex: 1;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(168, 85, 247, 0.06) 0%, transparent 40%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 30px;
        }

        .brand-logo i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-logo h1 {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 4px;
            background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-tagline {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 50px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 24px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateX(5px);
        }

        .feature-item i {
            font-size: 1.5rem;
            color: #6366f1;
        }

        .feature-item span {
            color: #9ca3af;
            font-size: 0.95rem;
        }

        /* Right Side - Login Form */
        .login-form-side {
            width: 500px;
            min-width: 500px;
            background: #111118;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
        }

        .login-form-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, rgba(99, 102, 241, 0.3), transparent);
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #9ca3af;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            font-size: 1.25rem;
            color: #4b5563;
            transition: color 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            color: #ffffff;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .input-wrapper input[type="password"],
        .input-wrapper input#password {
            padding-right: 50px;
        }

        .input-wrapper input::placeholder {
            color: #4b5563;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-wrapper input:focus + i,
        .input-wrapper:focus-within i:not(.password-toggle i) {
            color: #6366f1;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #4b5563;
            cursor: pointer;
            font-size: 1.25rem;
            transition: color 0.3s ease;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #6366f1;
        }

        .login-btn {
            padding: 16px 32px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            font-family: inherit;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn i {
            font-size: 1.25rem;
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-error::before {
            content: '\e900';
            font-family: 'Phosphor';
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-branding {
                display: none;
            }

            .login-form-side {
                width: 100%;
                min-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .login-form-side {
                padding: 40px 24px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .brand-logo h1 {
                font-size: 2rem;
            }
        }

        /* Animated background particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Left Branding Side -->
    <div class="login-branding">
        <div class="particles">
            <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
            <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
            <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
            <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
            <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
            <div class="particle" style="left: 60%; animation-delay: 10s;"></div>
            <div class="particle" style="left: 70%; animation-delay: 12s;"></div>
            <div class="particle" style="left: 80%; animation-delay: 14s;"></div>
            <div class="particle" style="left: 90%; animation-delay: 1s;"></div>
        </div>
        <div class="branding-content">
            <div class="brand-logo">
                <i class="ph-fill ph-car-profile"></i>
                <h1>Coovix</h1>
            </div>
            <p class="brand-tagline">Premium Car Care Management</p>

            <div class="brand-features">
                <div class="feature-item">
                    <i class="ph-fill ph-chart-line-up"></i>
                    <span>Real-time booking analytics & insights</span>
                </div>
                <div class="feature-item">
                    <i class="ph-fill ph-calendar-check"></i>
                    <span>Smart scheduling & appointment management</span>
                </div>
                <div class="feature-item">
                    <i class="ph-fill ph-users-three"></i>
                    <span>Customer relationship management</span>
                </div>
                <div class="feature-item">
                    <i class="ph-fill ph-gear-six"></i>
                    <span>Customizable service configurations</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Login Form Side -->
    <div class="login-form-side">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Enter your credentials to access the admin panel</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="ph-fill ph-warning-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="input-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    <i class="ph ph-user"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="ph ph-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="ph ph-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <span>Sign In</span>
                <i class="ph-bold ph-arrow-right"></i>
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('ph-eye');
                toggleIcon.classList.add('ph-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('ph-eye-slash');
                toggleIcon.classList.add('ph-eye');
            }
        }
    </script>
</body>
</html>
