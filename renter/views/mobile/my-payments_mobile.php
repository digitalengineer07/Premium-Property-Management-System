<?php
// EXCLUSIVE MOBILE VIEW FOR MY-PAYMENTS.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->
<header class="mobile-only-header">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu'></i>
    </div>
    <div class="m-header-center">
        <h2>My Payments</h2>
        <p>View and manage all your bills & payments</p>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="icon-btn m-bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if ($unread_count > 0): ?>
                <span class="m-notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="m-user-avatar">
            <?php echo strtoupper(substr(trim($display_name ?? $user['name'] ?? 'User'), 0, 2)); ?>
        </div>
        <div class="icon-btn" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
    </div>
</header>