<style>
    .admin-profile-dropdown {
        position: relative;
        cursor: pointer;
    }
    .dropdown-menu-custom {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 12px;
        background: #ffffff;
        border: 1px solid #E2E8F0;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border-radius: 12px;
        width: 180px;
        z-index: 1000;
        flex-direction: column;
    }
    .dropdown-menu-custom.show {
        display: flex;
    }
    .dropdown-menu-custom a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        color: #0F172A;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.2s ease;
    }
    .dropdown-menu-custom a:hover {
        background: #F8FAFC;
    }
    .dropdown-menu-custom a i {
        font-size: 16px;
        color: #64748B;
    }
    .dropdown-menu-custom a.logout-link {
        color: #EF4444;
    }
    .dropdown-menu-custom a.logout-link i {
        color: #EF4444;
    }
    body.dark-theme .dropdown-menu-custom {
        background: #1E293B;
        border-color: #334155;
    }
    body.dark-theme .dropdown-menu-custom a {
        color: #F8FAFC;
    }
    body.dark-theme .dropdown-menu-custom a:hover {
        background: #0F172A;
    }
</style>

<header class="header">
    <div class="header-content">
        <div class="search-bar">
            <i class='bx bx-search'></i>
            <input type="text" id="globalSearch" placeholder="Search billing details, residents, meters...">
        </div>
        <div class="user-profile">
            <!-- Theme Toggle -->
            <div class="icon-btn" id="themeToggle" style="cursor: pointer;">
                    <i class='bx bx-moon'></i>
                </div>
            
            <!-- Notifications -->
            <?php
                if (!isset($conn)) { require_once "../db.php"; }
                $pending_pay_q = mysqli_query($conn, "SELECT COUNT(id) as total FROM payment_notifications WHERE status = 'Pending'");
                $pending_pay_count = mysqli_fetch_assoc($pending_pay_q)['total'] ?? 0;
            ?>
            <div class="icon-btn notif-btn-wrapper" style="position: relative;" onclick="document.getElementById('notifDropdownMenu').classList.toggle('show')">
                <i class='bx bx-bell'></i>
                <?php if ($pending_pay_count > 0): ?>
                    <div class="badge-dot" style="display: flex; align-items: center; justify-content: center; font-size: 8px; color: white; width: 14px; height: 14px; top: -2px; right: -2px;"><?php echo $pending_pay_count; ?></div>
                <?php endif; ?>
                
                <div id="notifDropdownMenu" class="dropdown-menu-custom" style="right: -60px; min-width: 280px; padding: 0; overflow: hidden;">
                    <div style="padding: 12px 16px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-weight: 700; color: #1E293B; display: flex; justify-content: space-between; align-items: center;">
                        <span>Notifications</span>
                        <?php if ($pending_pay_count > 0): ?><span style="background: #EF4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px;"><?php echo $pending_pay_count; ?> New</span><?php endif; ?>
                    </div>
                    <?php if ($pending_pay_count > 0): ?>
                        <a href="payment-verifications.php" style="display: flex; align-items: flex-start; gap: 12px; padding: 16px; border-bottom: 1px solid #E2E8F0; text-decoration: none;">
                            <div style="background: rgba(245, 158, 11, 0.1); color: #D97706; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px;"><i class='bx bx-check-shield'></i></div>
                            <div>
                                <p style="margin: 0 0 4px 0; font-size: 13.5px; font-weight: 600; color: #1E293B;">Verify <?php echo $pending_pay_count; ?> Payments</p>
                                <p style="margin: 0; font-size: 11.5px; color: #64748B; line-height: 1.4;">Residents have submitted payment screenshots awaiting your approval.</p>
                            </div>
                        </a>
                        <a href="payment-verifications.php" style="display: block; text-align: center; padding: 10px; font-size: 12px; font-weight: 600; color: #624BFF; text-decoration: none; background: #F8FAFC;">View all approvals</a>
                    <?php else: ?>
                        <div style="padding: 30px 20px; text-align: center; color: #94A3B8;">
                            <i class='bx bx-bell-off' style="font-size: 32px; margin-bottom: 12px; opacity: 0.5;"></i>
                            <p style="margin: 0; font-size: 13px; font-weight: 500;">You're all caught up!</p>
                            <p style="margin: 4px 0 0 0; font-size: 11px;">No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile -->
            <div class="admin-profile-dropdown" onclick="document.getElementById('adminDropdownMenu').classList.toggle('show')">
                <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="avatar" onerror="this.src='https://ui-avatars.com/api/?name=Admin+User&background=624BFF&color=fff'">
                <div class="admin-info hide-mobile">
                    <h4>Admin User</h4>
                    <p>Administrator</p>
                </div>
                <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                
                <div id="adminDropdownMenu" class="dropdown-menu-custom">
                    <a href="dashboard.php"><i class='bx bx-home'></i> Dashboard</a>
                    <a href="about-dev.php"><i class='bx bx-info-circle'></i> About</a>
                    <div style="border-top: 1px solid #E2E8F0; margin: 4px 0;"></div>
                    <a href="logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('click', function(e) {
        // Handle Profile Dropdown
        const profDropdown = document.getElementById('adminDropdownMenu');
        const profTrigger = document.querySelector('.admin-profile-dropdown');
        if (profDropdown && profTrigger && !profTrigger.contains(e.target)) {
            profDropdown.classList.remove('show');
        }
        
        // Handle Notif Dropdown
        const notifDropdown = document.getElementById('notifDropdownMenu');
        const notifTrigger = document.querySelector('.notif-btn-wrapper');
        if (notifDropdown && notifTrigger && !notifTrigger.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });
</script>
