<?php
// EXCLUSIVE MOBILE VIEW FOR PROFILE.PHP
$d_name = $user['name'] ?: ($user['username'] ?? 'VJ');
$avatar_initials = strtoupper(substr(trim($d_name), 0, 2));

// Calculate linked documents count
$linked_docs_count = (!empty($user['aadhaar_file']) ? 1 : 0) + (!empty($user['agreement_document']) ? 1 : 0) + (!empty($user['electricity_document']) ? 1 : 0);
?>
<style>
    /* Reset & Base */
    .m-profile-wrapper, .m-profile-wrapper * { box-sizing: border-box; }
    .m-profile-wrapper {
        background: var(--bg-main, #FAFBFC);
        padding-bottom: 120px; /* Space for bottom nav and safe areas */
    }
    
    /* Header */
    .mp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: var(--white, #fff);
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .mp-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .mp-back-btn {
        font-size: 24px;
        color: var(--text-dark, #0F172A);
        text-decoration: none;
        display: flex;
    }
    .mp-title-group h1 {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-dark, #0F172A);
        margin: 0 0 2px 0;
    }
    .mp-title-group p {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin: 0;
    }
    .mp-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mp-bell {
        position: relative;
        font-size: 24px;
        color: var(--text-dark, #0F172A);
    }
    .mp-bell .badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #EF4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--white, #fff);
    }
    .mp-avatar-mini {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-purple, #624BFF);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .mp-container {
        padding: 16px 20px;
    }

    /* User Info Card */
    .mp-user-card {
        background: var(--white, #fff);
        border-radius: 20px;
        padding: 24px 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.02);
        margin-bottom: 24px;
    }
    .mp-avatar-container {
        position: relative;
    }
    .mp-avatar-main {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        background: #F1F5F9;
    }
    .mp-avatar-fallback {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary-purple, #624BFF);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 700;
    }
    .mp-camera-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--white, #fff);
        border: 2px solid var(--white, #fff);
        color: var(--primary-purple, #624BFF);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    .mp-user-details h2 {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-dark, #0F172A);
        margin: 0 0 8px 0;
    }
    .mp-room-badge {
        display: inline-block;
        padding: 4px 10px;
        background: rgba(98, 75, 255, 0.1);
        color: var(--primary-purple, #624BFF);
        font-size: 12px;
        font-weight: 700;
        border-radius: 20px;
    }

    /* Menu List */
    .mp-menu-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 24px;
    }
    .mp-menu-item {
        background: var(--white, #fff);
        border-radius: 16px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        text-decoration: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.02);
        border: 1px solid var(--border, #F1F5F9);
    }
    .mp-menu-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(98, 75, 255, 0.05);
        color: var(--primary-purple, #624BFF);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .mp-menu-text {
        flex: 1;
    }
    .mp-menu-text h3 {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark, #0F172A);
        margin: 0 0 2px 0;
    }
    .mp-menu-text p {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin: 0;
    }
    .mp-menu-arrow {
        color: #CBD5E1;
        font-size: 20px;
    }
    .mp-menu-badge {
        background: rgba(16, 185, 129, 0.15);
        color: #10B981;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
    }

    /* Promo Widget */
    .mp-promo-card {
        background: rgba(98, 75, 255, 0.04);
        border: 1px solid rgba(98, 75, 255, 0.08);
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }
    .mp-promo-img {
        width: 80px;
        height: auto;
    }
    .mp-promo-text h4 {
        font-size: 15px;
        font-weight: 800;
        color: var(--text-dark, #0F172A);
        margin: 0 0 4px 0;
    }
    .mp-promo-text p {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin: 0 0 12px 0;
        line-height: 1.4;
    }
    .mp-btn-download {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--white, #fff);
        color: var(--primary-purple, #624BFF);
        border: 1px solid rgba(98, 75, 255, 0.2);
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    /* Logout Button */
    .mp-btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--white, #fff);
        border: 1px solid rgba(15, 23, 42, 0.1);
        color: var(--text-dark, #0F172A);
        padding: 16px;
        border-radius: 16px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        margin-bottom: 16px;
    }

    /* Dark Theme Mobile Overrides */
    .dark-theme .mp-header { background: var(--sidebar-bg, #111827); border-color: var(--border, #1E293B); }
    .dark-theme .m-profile-wrapper { background: var(--bg-main, #0B0F19); }
    .dark-theme .mp-user-card, .dark-theme .mp-menu-item { background: var(--white, #111827); border-color: var(--border, #1E293B); }
    .dark-theme .mp-camera-btn { background: var(--white, #111827); border-color: var(--white, #111827); }
    .dark-theme .mp-btn-logout { background: transparent; border-color: var(--border, #1E293B); color: var(--text-dark, #F8FAFC); }
</style>

<div class="m-profile-wrapper animate-up">
    <header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-user-circle' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            Profile
        </h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-icon-btn" onclick="openMobileNotif()">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
        <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
            <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../../' . $user['profile_pic'])): ?>
                <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: #624BFF; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: 800;">
                    <?php echo strtoupper(substr(trim($user['name'] ?? ($user['username'] ?? 'U')), 0, 1)); ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</header>
<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>


    <div class="mp-container">
        <div class="mp-user-card">
            <div class="mp-avatar-container" onclick="document.getElementById('profilePicInput').click()" style="cursor: pointer;">
                <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../../' . $user['profile_pic'])): ?>
                    <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="mp-avatar-main">
                <?php else: ?>
                    <div class="mp-avatar-fallback"><?php echo $avatar_initials; ?></div>
                <?php endif; ?>
                <div class="mp-camera-btn">
                    <i class='bx bx-camera'></i>
                </div>
            </div>
            <div class="mp-user-details">
                <h2><?php echo htmlspecialchars($d_name); ?></h2>
                <div class="mp-room-badge">Room <?php echo htmlspecialchars($user['room_no'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <div class="mp-menu-list">
            <a href="#" class="mp-menu-item" onclick="document.getElementById('editProfileModal').style.display='flex'; return false;">
                <div class="mp-menu-icon"><i class='bx bx-user'></i></div>
                <div class="mp-menu-text">
                    <h3>Basic Information</h3>
                    <p>View and edit your personal details</p>
                </div>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>
            
            <a href="#" class="mp-menu-item" onclick="document.getElementById('changePasswordModal').style.display='flex'; return false;">
                <div class="mp-menu-icon"><i class='bx bx-shield'></i></div>
                <div class="mp-menu-text">
                    <h3>Account & Security</h3>
                    <p>Manage password and account security</p>
                </div>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>

            <a href="#" class="mp-menu-item" onclick="document.getElementById('editProfileModal').style.display='flex'; return false;">
                <div class="mp-menu-icon"><i class='bx bx-phone-call'></i></div>
                <div class="mp-menu-text">
                    <h3>Emergency Contact</h3>
                    <p>Update emergency contact information</p>
                </div>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>

            <a href="#" class="mp-menu-item" onclick="document.getElementById('editProfileModal').style.display='flex'; return false;">
                <div class="mp-menu-icon"><i class='bx bx-home'></i></div>
                <div class="mp-menu-text">
                    <h3>Residence Details</h3>
                    <p>View your residence and room details</p>
                </div>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>

            <a href="documents.php" class="mp-menu-item">
                <div class="mp-menu-icon"><i class='bx bx-file'></i></div>
                <div class="mp-menu-text">
                    <h3>Linked Documents</h3>
                    <p>Manage and view your documents</p>
                </div>
                <?php if ($linked_docs_count > 0): ?>
                <div class="mp-menu-badge"><?php echo $linked_docs_count; ?></div>
                <?php endif; ?>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>

            <a href="#" class="mp-menu-item">
                <div class="mp-menu-icon"><i class='bx bx-cog'></i></div>
                <div class="mp-menu-text">
                    <h3>Preferences</h3>
                    <p>Notification and communication preferences</p>
                </div>
                <i class='bx bx-chevron-right mp-menu-arrow'></i>
            </a>
        </div>

        <div class="mp-promo-card">
            <div style="flex-shrink: 0;">
                <div style="width: 50px; height: 80px; background: var(--primary-purple); border-radius: 12px; display: flex; flex-direction: column; align-items: center; padding: 6px; position: relative;">
                    <div style="width: 20px; height: 3px; background: rgba(255,255,255,0.4); border-radius: 2px; margin-bottom: auto;"></div>
                    <div style="width: 30px; height: 30px; background: rgba(255,255,255,0.2); border-radius: 8px; margin-bottom: 6px; display:flex; align-items:center; justify-content:center;"><i class='bx bx-home-heart' style="color:white;"></i></div>
                    <div style="width: 24px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 2px; margin-bottom: 4px;"></div>
                    <div style="width: 30px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; margin-bottom: 4px;"></div>
                    <div style="width: 16px; height: 2px; background: rgba(255,255,255,0.4); border-radius: 1px; position: absolute; bottom: 6px;"></div>
                    <div style="position: absolute; right: -16px; top: 30px; width: 32px; height: 32px; background: var(--primary-purple); border-radius: 50%; border: 3px solid var(--white); display: flex; align-items: center; justify-content: center; color: white;"><i class='bx bx-user'></i></div>
                </div>
            </div>
            <div class="mp-promo-text">
                <h4>Manage on the Go!</h4>
                <p>Update your profile, documents and preferences easily.</p>
                <a href="#" class="mp-btn-download"><i class='bx bx-download'></i> Download App</a>
            </div>
        </div>

        <a href="../logout.php" class="mp-btn-logout">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>
</div>

<nav class="mobile-bottom-nav" style="display: flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; z-index: 2147483647 !important; transform: none !important; position: fixed !important; height: 72px !important; bottom: 8px !important; left: 16px !important; right: 16px !important; border-radius: 24px !important; box-shadow: 0 10px 40px rgba(0,0,0,0.08) !important; border: 1px solid rgba(255,255,255,0.8) !important; padding: 0 16px !important; background: rgba(255,255,255,0.95) !important; backdrop-filter: blur(12px) !important; justify-content: space-around !important; align-items: center !important;">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item "><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item active"><i class='bx bx-user'></i><span>Profile</span></a>
</nav>

<?php include 'mobile_notifications.php'; ?>
