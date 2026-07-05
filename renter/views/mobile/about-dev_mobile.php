<?php
// EXCLUSIVE MOBILE VIEW FOR ABOUT-DEV
?>
<!-- EXCLUSIVE MOBILE VIEW FOR ABOUT-DEV -->
<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px; background: linear-gradient(90deg, #ffffff 0%, #e0c8ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));">About Dev</h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="header-icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
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

<div class="mobile-generic-content animate-up" style="padding: 8px 0;">
    <?php include __DIR__ . '/../desktop/about-dev_desktop.php'; ?>
</div>
