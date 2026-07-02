<?php
// EXCLUSIVE MOBILE VIEW FOR ABOUT-DEV
?>
<!-- EXCLUSIVE MOBILE VIEW FOR ABOUT-DEV -->
<header class="mobile-only-header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 4px 18px 4px;">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu' style="font-size: 28px; color: var(--text-dark);"></i>
    </div>
    <div class="m-header-center" style="text-align: left; flex: 1; margin-left: 12px;">
        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0;">About Developer</h2>
        <p style="font-size: 11px; color: var(--text-gray); margin: 2px 0 0 0;">Meet the creator</p>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="icon-btn" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
        <a href="dashboard.php" style="color: var(--text-dark); font-size: 22px; text-decoration: none; display: flex; align-items: center;"><i class='bx bx-home'></i></a>
    </div>
</header>
<div class="mobile-generic-content animate-up" style="padding: 8px 0;">
    <?php include __DIR__ . '/../desktop/about-dev_desktop.php'; ?>
</div>
