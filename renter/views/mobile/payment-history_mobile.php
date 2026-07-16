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
        display: grid !important;
        grid-template-columns: auto 1fr !important;
        align-items: center !important;
        gap: 4px 8px !important;
        padding: 12px !important;
        min-width: unset !important;
        margin-bottom: 0 !important;
    }
    .mobile-page-body .kpi-min-info {
        display: contents !important;
    }
    .mobile-page-body .kpi-min-icon {
        grid-column: 1 !important;
        grid-row: 1 !important;
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
    }
    .mobile-page-body .kpi-min-icon i {
        font-size: 16px !important;
    }
    .mobile-page-body .kpi-card-minimal h4 {
        grid-column: 2 !important;
        grid-row: 1 !important;
        margin: 0 !important;
        font-size: 10px !important;
        white-space: normal !important;
        text-align: left !important;
    }
    .mobile-page-body .kpi-card-minimal h2 {
        grid-column: 1 / -1 !important;
        grid-row: 2 !important;
        text-align: center !important;
        font-size: 16px !important;
        margin-top: 6px !important;
        margin-bottom: 2px !important;
    }
    .mobile-page-body .kpi-card-minimal .kpi-min-tag {
        grid-column: 1 / -1 !important;
        grid-row: 3 !important;
        text-align: center !important;
        font-size: 9px !important;
        white-space: normal !important;
    }

    /* 3. Fix the Filter Section (Side-by-side for first 3 filters) */
    .mobile-page-body .tabs-header {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 8px !important;
        padding: 16px 12px !important;
    }
    .mobile-page-body .filter-group {
        width: 100% !important;
        min-width: 0 !important;
        flex: none !important;
    }
    .mobile-page-body .filter-group > label {
        font-size: 10px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        margin-bottom: 4px !important;
    }
    .mobile-page-body .filter-select, 
    .mobile-page-body .filter-group > div {
        width: 100% !important;
        min-width: 0 !important;
        padding: 8px 4px !important;
        font-size: 11px !important;
    }
    .mobile-page-body .filter-group > div span {
        font-size: 11px !important;
    }
    .mobile-page-body .filter-group > div i {
        margin-right: 4px !important;
    }
    .mobile-page-body .filter-group:nth-child(4),
    .mobile-page-body .filter-group:nth-child(5) {
        grid-column: 1 / -1 !important;
    }
    .mobile-page-body .btn-outline-support {
        width: 100% !important;
        justify-content: center !important;
        margin-top: 4px;
    }

    /* 4. Fix Table Layout (Convert to Mobile Box Type) */
    .mobile-page-body .payments-container {
        border-radius: 0 !important;
        border-left: none !important;
        border-right: none !important;
        margin-left: -16px;
        margin-right: -16px;
        width: calc(100% + 32px) !important;
        background: transparent !important;
        box-shadow: none !important;
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
    }
    
    .mobile-page-body .payments-table, 
    .mobile-page-body .payments-table tbody {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
    }
    
    .mobile-page-body .payments-table thead {
        display: none !important;
    }
    
    .mobile-page-body .payments-table tr {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        margin: 0 16px 16px 16px !important;
        border: 1px solid var(--border) !important;
        border-radius: 16px !important;
        background: var(--white) !important;
        padding: 0 !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important;
        overflow: hidden !important;
    }
    
    .mobile-page-body .payments-table td {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: flex-start !important;
        padding: 12px 16px !important;
        border: none !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        text-align: left !important;
        font-size: 13px !important;
    }
    
    /* Hide the ID and Bill Date columns to save space */
    .mobile-page-body .payments-table td:nth-child(1),
    .mobile-page-body .payments-table td:nth-child(4) {
        display: none !important;
    }
    
    /* VERY IMPORTANT: Allow JS to hide rows for pagination by respecting inline display: none */
    .mobile-page-body .payments-table tr[style*="display: none"],
    .mobile-page-body .payments-table tr[style*="display: none;"] {
        display: none !important;
    }
    
    /* Style the Bill Type as a card header spanning both columns */
    .mobile-page-body .payments-table td:nth-child(2) {
        grid-column: 1 / span 2 !important;
        flex-direction: row !important;
        justify-content: flex-start !important;
        align-items: center !important;
        background: rgba(248, 250, 252, 0.8) !important;
        border-bottom: 1px solid var(--border) !important;
        padding: 16px !important;
        order: 1;
    }
    
    .mobile-page-body .td-bill-type {
        text-align: left !important;
    }
    
    /* Grid placement and borders for the rest of the cells */
    .mobile-page-body .payments-table td:nth-child(6) { order: 2; border-right: 1px solid rgba(0,0,0,0.05) !important; } /* Amount */
    .mobile-page-body .payments-table td:nth-child(7) { order: 3; } /* Status */
    .mobile-page-body .payments-table td:nth-child(3) { order: 4; border-right: 1px solid rgba(0,0,0,0.05) !important; } /* Period */
    .mobile-page-body .payments-table td:nth-child(5) { order: 5; } /* Due Date */
    .mobile-page-body .payments-table td:nth-child(8) { order: 6; border-right: 1px solid rgba(0,0,0,0.05) !important; border-bottom: none !important; } /* Paid On */
    .mobile-page-body .payments-table td:nth-child(9) { order: 7; border-bottom: none !important; } /* Mode */
    
    /* Inject Labels on top of values */
    .mobile-page-body .payments-table td::before {
        display: block !important;
        font-size: 9px !important;
        font-weight: 700 !important;
        color: var(--text-gray) !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 4px !important;
        text-transform: uppercase !important;
    }
    
    /* Specific label texts */
    .mobile-page-body .payments-table td:nth-child(3)::before { content: "FOR PERIOD"; }
    .mobile-page-body .payments-table td:nth-child(5)::before { content: "DUE DATE"; }
    .mobile-page-body .payments-table td:nth-child(6)::before { content: "AMOUNT"; }
    .mobile-page-body .payments-table td:nth-child(7)::before { content: "STATUS"; }
    .mobile-page-body .payments-table td:nth-child(8)::before { content: "PAID ON"; }
    .mobile-page-body .payments-table td:nth-child(9)::before { content: "PAYMENT MODE"; }
    
    /* Fix UPI Logo size in mobile box */
    .mobile-page-body .payments-table td img {
        height: 14px !important;
        width: auto !important;
        max-width: 40px !important;
        object-fit: contain !important;
    }
    .mobile-page-body .payments-table td:nth-child(9) div {
        justify-content: flex-start !important;
        margin-top: 2px !important;
    }
    
    /* Fix Pagination for Mobile (< 1 > format) */
    .mobile-page-body .payments-container > div:last-child {
        justify-content: center !important;
        padding: 16px 16px 4px 16px !important;
        margin-bottom: 0 !important;
    }
    .mobile-page-body .payments-container > div:last-child > div:first-child {
        display: none !important; /* Hide 'Showing 1 to 5...' */
    }
    .mobile-page-body .pagination .page-btn {
        display: none !important; /* Hide all page numbers */
    }
    .mobile-page-body .pagination .page-btn:first-child,
    .mobile-page-body .pagination .page-btn:last-child,
    .mobile-page-body .pagination .page-btn.active {
        display: flex !important; /* Only show <, current active page, and > */
    }

    /* FORCE NAVIGATION BAR TO BOTTOM VIEWPORT */
    .mobile-bottom-nav {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 999999 !important;
        display: flex !important;
        transform: none !important;
    }
    
    body {
        padding-bottom: 76px !important; /* Just enough for the nav bar */
    }
}
</style>

<div style="height: 10px; width: 100%; display: block; flex-shrink: 0;"></div>

<div class='mobile-page-body animate-up' style='padding: 80px 0 10px 0;'>
    <?php include __DIR__ . '/../desktop/payment-history_desktop.php'; ?>
</div>