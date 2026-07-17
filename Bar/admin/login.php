<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!checkRateLimit('login', 5, 300)) {
        $error = 'Too many login attempts. Please wait 5 minutes.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } else {
            // Add missing columns if they don't exist
            $conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_products TINYINT(1) DEFAULT 0");
            $conn->query("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS can_expenses TINYINT(1) DEFAULT 0");

            $stmt = $conn->prepare("SELECT id, username, password, can_dashboard, can_barbers, can_schedule, can_bookings, can_users, can_staff_status, can_products, can_expenses, can_settings, status FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if ($user['status'] !== 'active') {
                    $error = 'Account is inactive';
                } elseif (password_verify($password, $user['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['username'];
                    $_SESSION['can_dashboard'] = $user['can_dashboard'];
                    $_SESSION['can_barbers'] = $user['can_barbers'];
                    $_SESSION['can_schedule'] = $user['can_schedule'] ?? 0;
                    $_SESSION['can_bookings'] = $user['can_bookings'];
                    $_SESSION['can_users'] = $user['can_users'];
                    $_SESSION['can_staff_status'] = $user['can_staff_status'] ?? 0;
                    $_SESSION['can_products'] = $user['can_products'] ?? 0;
                    $_SESSION['can_expenses'] = $user['can_expenses'] ?? 0;
                    $_SESSION['can_settings'] = $user['can_settings'] ?? 0;
                    $_SESSION['last_activity'] = time();

                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Login - Coovix Barber</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B35;
            --primary-dark: #e55a2b;
            --primary-light: #ff8c61;
            --secondary: #1a1a2e;
            --dark: #16213e;
            --text-primary: #1a1a2e;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --error: #ef4444;
            --error-bg: #fef2f2;
            --success: #22c55e;
            --border: #e2e8f0;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--dark) 50%, #0f0f23 100%);
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* Animated background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .bg-animation .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .bg-animation .circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }

        .bg-animation .circle:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation-delay: -5s;
        }

        .bg-animation .circle:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(20px, 30px) scale(1.05); }
        }

        /* Login Container */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 40px 32px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo Section */
        .logo {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3); }
            50% { box-shadow: 0 10px 40px rgba(255, 107, 53, 0.5); }
        }

        .logo-icon i {
            font-size: 32px;
            color: var(--white);
        }

        .logo h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .logo p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Error Message */
        .error-message {
            background: var(--error-bg);
            border: 1px solid #fecaca;
            color: var(--error);
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }

        .error-message i {
            font-size: 18px;
            flex-shrink: 0;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 18px 18px 18px 52px;
            border: 2px solid var(--border);
            border-radius: 14px;
            font-size: 16px;
            font-family: inherit;
            font-weight: 500;
            color: var(--text-primary);
            background: var(--white);
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
        }

        .form-group input::placeholder {
            color: var(--text-light);
            font-weight: 400;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
        }

        .form-group input:focus + .input-icon,
        .form-group input:focus ~ .input-icon {
            color: var(--primary);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            margin: -8px;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .password-toggle:hover {
            color: var(--text-secondary);
        }

        .password-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.3);
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.85;
        }

        .btn-login .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        .powered-by {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            color: var(--text-light);
            font-size: 13px;
        }

        .powered-by a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .powered-by a:hover {
            color: var(--primary-dark);
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
                align-items: flex-start;
                padding-top: 10vh;
            }

            .login-container {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .logo-icon {
                width: 64px;
                height: 64px;
                border-radius: 18px;
            }

            .logo-icon i {
                font-size: 28px;
            }

            .logo h1 {
                font-size: 24px;
            }

            .logo {
                margin-bottom: 28px;
            }

            .form-group input {
                padding: 16px 16px 16px 48px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .input-icon {
                left: 16px;
                font-size: 16px;
            }

            .password-toggle {
                right: 14px;
            }

            .btn-login {
                padding: 16px 20px;
                margin-top: 24px;
            }

            .bg-animation .circle:nth-child(1) {
                width: 250px;
                height: 250px;
            }

            .bg-animation .circle:nth-child(2) {
                width: 180px;
                height: 180px;
            }

            .bg-animation .circle:nth-child(3) {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 360px) {
            .login-container {
                padding: 28px 20px;
            }

            .logo h1 {
                font-size: 22px;
            }

            .logo p {
                font-size: 13px;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 16px;
                align-items: center;
            }

            .login-container {
                padding: 24px 32px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .logo-icon {
                width: 56px;
                height: 56px;
                margin-bottom: 12px;
            }

            .logo-icon i {
                font-size: 24px;
            }

            .logo h1 {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .form-group input {
                padding: 14px 14px 14px 44px;
            }

            .btn-login {
                padding: 14px 20px;
                margin-top: 18px;
            }

            .powered-by {
                margin-top: 18px;
                padding-top: 16px;
            }
        }

        /* Safe area for notched phones */
        @supports (padding: max(0px)) {
            body {
                padding-left: max(20px, env(safe-area-inset-left));
                padding-right: max(20px, env(safe-area-inset-right));
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }
        }

        /* Dark mode preference */
        @media (prefers-color-scheme: dark) {
            .login-container {
                background: rgba(30, 30, 45, 0.95);
            }

            .logo h1 {
                color: var(--white);
            }

            .logo p {
                color: var(--text-light);
            }

            .form-group input {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.15);
                color: var(--white);
            }

            .form-group input::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .form-group input:focus {
                border-color: var(--primary);
                background: rgba(255, 255, 255, 0.15);
            }

            .input-icon {
                color: rgba(255, 255, 255, 0.5);
            }

            .password-toggle {
                color: rgba(255, 255, 255, 0.5);
            }

            .powered-by {
                border-color: rgba(255, 255, 255, 0.1);
                color: rgba(255, 255, 255, 0.5);
            }

            .error-message {
                background: rgba(239, 68, 68, 0.15);
                border-color: rgba(239, 68, 68, 0.3);
            }
        }

        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus visible for keyboard navigation */
        .form-group input:focus-visible,
        .btn-login:focus-visible,
        .password-toggle:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <!-- Logo -->
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cut"></i>
                </div>
                <h1>Coovix Barber</h1>
                <p>Admin Dashboard</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="text"
                            name="username"
                            id="username"
                            placeholder="Username"
                            required
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                        >
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Password"
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="btn-text">Sign In</span>
                    <div class="spinner"></div>
                </button>
            </form>

            <!-- Footer -->
            <div class="powered-by">
                Powered by <a href="https://coovix.com" target="_blank" rel="noopener">coovix</a>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (username && password) {
                btn.classList.add('loading');
            }
        });

        // Auto-focus username field on desktop
        if (window.innerWidth > 768) {
            document.getElementById('username').focus();
        }

        // Prevent zoom on input focus (iOS)
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('meta[name="viewport"]').setAttribute('content',
                        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
            });

            input.addEventListener('blur', function() {
                document.querySelector('meta[name="viewport"]').setAttribute('content',
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            });
        });
    </script>
</body>
</html>
