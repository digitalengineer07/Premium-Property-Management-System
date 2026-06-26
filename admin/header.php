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
            <div class="icon-btn" id="themeToggle">
                <i class='bx bx-moon'></i>
            </div>
            
            <!-- Notifications -->
            <div class="icon-btn">
                <i class='bx bx-bell'></i>
                <div class="badge-dot" style="display: flex; align-items: center; justify-content: center; font-size: 8px; color: white; width: 14px; height: 14px; top: -2px; right: -2px;">3</div>
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
        const dropdown = document.getElementById('adminDropdownMenu');
        const trigger = document.querySelector('.admin-profile-dropdown');
        if (dropdown && trigger && !trigger.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
</script>
