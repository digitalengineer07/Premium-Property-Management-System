<?php
// admin/sidebar.php - Unified Sidebar for Admin Panel
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Immediate Theme Setter to prevent flashes -->
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark-theme');
        } else {
            document.documentElement.classList.remove('dark-theme');
        }
    })();
</script>

<div class="mobile-toggle" id="sidebarToggle">
    <i class='bx bx-menu'></i>
</div>

<aside class="sidebar" id="mainSidebar">
    <div class="brand">
        <div class="brand-icon-wrapper">
            <i class='bx bx-home-alt-2'></i>
        </div>
        <div class="brand-text">
            <h3>Madhav Kunj</h3>
            <p>Utility Management</p>
        </div>
    </div>
    
    <nav class="nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bx-grid-alt'></i> Dashboard
        </a>
        <a href="bill-generator.php" class="nav-item <?php echo $current_page == 'bill-generator.php' ? 'active' : ''; ?>">
            <i class='bx bx-bolt-circle'></i> Bill Generator
        </a>
        <a href="manage-renters.php" class="nav-item <?php echo ($current_page == 'manage-renters.php' || $current_page == 'view-renter.php' || $current_page == 'edit-renter.php') ? 'active' : ''; ?>">
            <i class='bx bx-user'></i> Manage Residents
        </a>
        <a href="electricity-list.php" class="nav-item <?php echo $current_page == 'electricity-list.php' ? 'active' : ''; ?>">
            <i class='bx bxs-bolt'></i> <span>Electricity Record</span>
        </a>
        <a href="visitor-logs.php" class="nav-item <?php echo $current_page == 'visitor-logs.php' ? 'active' : ''; ?>">
            <i class='bx bx-timer'></i> <span>Visitor Logs</span>
        </a>
        <a href="manage-reminders.php" class="nav-item <?php echo $current_page == 'manage-reminders.php' ? 'active' : ''; ?>">
            <i class='bx bx-bell'></i> <span>Reminders</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' || $current_page == 'monthly-report.php' ? 'active' : ''; ?>">
            <i class='bx bx-bar-chart-alt-2'></i> <span>Analytics & Reports</span>
        </a>
        <?php
            $pending_pay_q = mysqli_query($conn, "SELECT COUNT(id) as total FROM payment_notifications WHERE status = 'Pending'");
            $pending_pay_count = mysqli_fetch_assoc($pending_pay_q)['total'] ?? 0;
        ?>
        <a href="payment-verifications.php" class="nav-item <?php echo $current_page == 'payment-verifications.php' ? 'active' : ''; ?>" style="position: relative;">
            <i class='bx bx-check-shield'></i> <span>Verifications</span>
            <?php if ($pending_pay_count > 0): ?>
                <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: #624BFF; color: white; width: 20px; height: 20px; border-radius: 50%; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid var(--white);"><?php echo $pending_pay_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="manage-announcements.php" class="nav-item <?php echo $current_page == 'manage-announcements.php' ? 'active' : ''; ?>">
            <i class='bx bxs-megaphone'></i> <span>Announcements</span>
        </a>
        <a href="manage-queries.php" class="nav-item <?php echo $current_page == 'manage-queries.php' ? 'active' : ''; ?>">
            <i class='bx bx-message-square-detail'></i> <span>Support Queries</span>
        </a>
        <a href="about-dev.php" class="nav-item <?php echo $current_page == 'about-dev.php' ? 'active' : ''; ?>">
            <i class='bx bx-info-circle'></i> About Developer
        </a>
    </nav>
    
    <div class="support-box">
        <div class="support-icon"><i class='bx bx-headphone'></i></div>
        <h4>Need Help?</h4>
        <p>Our support team is ready to help you.</p>
        <a href="manage-queries.php" class="btn-primary" style="padding: 8px 12px; font-size: 12px; border-radius: 8px; justify-content: center; width: 100%;">Contact Support</a>
    </div>

    <div class="sidebar-footer">
        <a href="#" class="nav-item" id="themeToggleSidebar" style="display: flex; align-items: center; gap: 12px;">
            <i class='bx bx-sun'></i> <span class="theme-text">Light Mode</span>
        </a>
        <a href="logout.php" class="nav-item" style="display: flex; align-items: center; gap: 12px; color: #EF4444;">
            <i class='bx bx-log-out'></i> <span>Logout</span>
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    backdrop-filter: blur(4px);
}
@media (max-width: 1024px) {
    .sidebar-overlay.show {
        display: block;
    }
}

@media (max-width: 1024px) {
    .mobile-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        display: flex;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileToggle = document.getElementById('sidebarToggle');

    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        document.body.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeUI(isDark);
    }

    function updateThemeUI(isDark) {
        if (isDark) {
            document.documentElement.classList.add('dark-theme');
            document.body.classList.add('dark-theme');
            if (themeToggle) {
                themeToggle.innerHTML = "<i class='bx bx-sun'></i> <span class='theme-text'>Light Mode</span>";
            }
            const dashToggleIcon = document.querySelector('#themeToggle i');
            if (dashToggleIcon) {
                dashToggleIcon.classList.replace('bx-moon', 'bx-sun');
            }
        } else {
            document.documentElement.classList.remove('dark-theme');
            document.body.classList.remove('dark-theme');
            if (themeToggle) {
                themeToggle.innerHTML = "<i class='bx bx-moon'></i> <span class='theme-text'>Dark Mode</span>";
            }
            const dashToggleIcon = document.querySelector('#themeToggle i');
            if (dashToggleIcon) {
                dashToggleIcon.classList.replace('bx-sun', 'bx-moon');
            }
        }
    }

    // Initialize UI based on current theme
    updateThemeUI(document.documentElement.classList.contains('dark-theme'));

    themeToggle?.addEventListener('click', function(e) {
        e.preventDefault();
        toggleTheme();
    });

    // Also handle the potential header toggle (id="themeToggle")
    document.addEventListener('click', function(e) {
        if (e.target.id === 'themeToggle' || e.target.closest('#themeToggle')) {
            toggleTheme();
        }
    });

    // Mobile Sidebar Toggle
    mobileToggle?.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay?.addEventListener('click', function() {
        sidebar.classList.remove('open');
        this.classList.remove('show');
    });


});
</script>
