<?php
// EXCLUSIVE MOBILE VIEW FOR PAYMENT-HISTORY.PHP
?>
<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-history' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            History
        </h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
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

<style>
/* -------------------------------------------------------------
   MOBILE OVERRIDES FOR DESKTOP COMPONENT 
   Since payment-history_mobile.php includes the desktop file,
   we must aggressively restyle its components for mobile view.
   ------------------------------------------------------------- */
@media screen and (max-width: 768px) {
    /* 1. Hide the duplicate Desktop Header */
    .mobile-page-body .top-header {
        display: none !important;
    }

    /* 2. Fix the KPI Cards (Side-by-side Grid) */
    .mobile-page-body .kpi-grid-4 {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
        padding-bottom: 0 !important;
        margin-left: 0;
        margin-right: 0;
        padding-left: 0;
        padding-right: 0;
        overflow-x: hidden !important;
    }
    .mobile-page-body .kpi-card-minimal {
        min-width: unset !important;
        flex: 1 1 0 !important;
        margin-bottom: 0 !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
        padding: 14px !important;
    }
    .mobile-page-body .kpi-card-minimal h4 {
        font-size: 11px !important;
        line-height: 1.3 !important;
        margin-bottom: 2px !important;
        white-space: normal !important;
    }
    .mobile-page-body .kpi-card-minimal h2 {
        font-size: 17px !important;
    }
    .mobile-page-body .kpi-card-minimal .kpi-min-tag {
        font-size: 9px !important;
        white-space: normal !important;
    }

    /* 3. Fix the Filter Section (Stack vertically) */
    .mobile-page-body .tabs-header {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
        padding: 16px 12px !important;
    }
    .mobile-page-body .filter-group {
        width: 100% !important;
        flex: none !important;
    }
    .mobile-page-body .filter-select {
        width: 100% !important;
        min-width: 100% !important;
    }
    .mobile-page-body .filter-group > div {
        min-width: 100% !important;
    }
    .mobile-page-body .btn-outline-support {
        width: 100% !important;
        justify-content: center !important;
        margin-top: 4px;
    }

    /* 4. Fix Table container padding */
    .mobile-page-body .payments-container {
        border-radius: 0 !important;
        border-left: none !important;
        border-right: none !important;
        margin-left: -16px;
        margin-right: -16px;
        width: calc(100% + 32px) !important;
    }
    .mobile-page-body .payments-table {
        min-width: 800px !important; /* Force horizontal scroll */
    }
}
</style>

<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>

<div class='mobile-page-body animate-up' style='padding: 10px 0;'>
    <?php include __DIR__ . '/../desktop/payment-history_desktop.php'; ?>
</div>