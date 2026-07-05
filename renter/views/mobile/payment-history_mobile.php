<?php
// EXCLUSIVE MOBILE VIEW FOR PAYMENT-HISTORY.PHP
?>
<header class="premium-header-pill">
    <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu-alt-left'></i>
    </div>
    
    <div class="m-header-module m-header-brand">
        <img src="../assets/img/logo.png" alt="Logo">
        <span><?php echo htmlspecialchars(defined('HOUSE_NAME') ? HOUSE_NAME : 'Madhav Kunj'); ?></span>
    </div>
    
    <div class="m-header-module m-header-right">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-divider"></div>
        <div class="header-icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
    </div>
</header>
<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>

<div class='mobile-page-body animate-up' style='padding: 10px 0;'>
    <?php include __DIR__ . '/../desktop/payment-history_desktop.php'; ?>
</div>