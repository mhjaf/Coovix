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
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <a href="index.php">
                    <i class="fas fa-home"></i>
                    <span><?php echo t('dashboard'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_bookings')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'class="active"' : ''; ?>>
                <a href="bookings.php">
                    <i class="fas fa-calendar-check"></i>
                    <span><?php echo t('bookings'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_products')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'class="active"' : ''; ?>>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span><?php echo t('products'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_expenses')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'class="active"' : ''; ?>>
                <a href="expenses.php">
                    <i class="fas fa-receipt"></i>
                    <span><?php echo t('expenses'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_schedule')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'worktime.php' ? 'class="active"' : ''; ?>>
                <a href="worktime.php">
                    <i class="fas fa-clock"></i>
                    <span><?php echo t('work_time'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_staff_status')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'staff-status.php' ? 'class="active"' : ''; ?>>
                <a href="staff-status.php">
                    <i class="fas fa-user-clock"></i>
                    <span><?php echo t('staff_status'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_barbers')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'barbers.php' ? 'class="active"' : ''; ?>>
                <a href="barbers.php">
                    <i class="fas fa-user-tie"></i>
                    <span><?php echo t('barbers'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_users')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                <a href="users.php">
                    <i class="fas fa-users-cog"></i>
                    <span><?php echo t('users'); ?></span>
                </a>
            </li>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'user-reports.php' ? 'class="active"' : ''; ?>>
                <a href="user-reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span><?php echo t('user_reports'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('can_settings')): ?>
            <li <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>>
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

<script>
// Close sidebar when clicking on a navigation link (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const navLinks = document.querySelectorAll('.sidebar-nav > ul > li > a');

    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            // Only close on mobile (screen width <= 991px)
            if (window.innerWidth <= 991) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
    });

    // Close sidebar when clicking outside (on overlay)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991 && sidebar.classList.contains('active')) {
            // Check if click is outside sidebar and not on menu toggle
            if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        }
    });
});
</script>
