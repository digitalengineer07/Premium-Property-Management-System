import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

pages = {
    'dashboard_mobile.php': {'title': 'Dashboard', 'icon': 'bx-home-circle'},
    'profile_mobile.php': {'title': 'Profile', 'icon': 'bx-user-circle'},
    'my-payments_mobile.php': {'title': 'Payments', 'icon': 'bx-credit-card'},
    'electricity-record_mobile.php': {'title': 'Electricity', 'icon': 'bx-plug'},
    'my-bills_mobile.php': {'title': 'My Bills', 'icon': 'bx-receipt'},
    'notices_mobile.php': {'title': 'Notices', 'icon': 'bx-bell'},
    'documents_mobile.php': {'title': 'Documents', 'icon': 'bx-folder-open'},
    'queries_mobile.php': {'title': 'Queries', 'icon': 'bx-message-square-dots'},
    'payment-history_mobile.php': {'title': 'History', 'icon': 'bx-history'},
    'about-dev_mobile.php': {'title': 'About Dev', 'icon': 'bx-code-alt'}
}

def get_header_html(title, icon):
    return f"""<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else {{ document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }}">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
            <i class='bx {icon}' style="font-size: 24px; color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.15); padding: 4px; border-radius: 8px;"></i>
            {title}
        </h1>
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
</header>"""

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        pattern = re.compile(r'<header class="premium-header-pill">.*?</header>', re.DOTALL)
        
        if pattern.search(content):
            page_info = pages.get(filename, {'title': 'Page', 'icon': 'bx-file'})
            new_header = get_header_html(page_info['title'], page_info['icon'])
            new_content = pattern.sub(new_header, content)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename} with title {page_info['title']} and icon {page_info['icon']}")
        else:
            print(f"No <header> found in {filename}")
