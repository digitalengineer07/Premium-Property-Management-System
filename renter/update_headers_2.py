import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

titles = {
    'dashboard_mobile.php': 'Dashboard',
    'profile_mobile.php': 'Profile',
    'my-payments_mobile.php': 'Payments',
    'electricity-record_mobile.php': 'Electricity',
    'my-bills_mobile.php': 'My Bills',
    'notices_mobile.php': 'Notices',
    'documents_mobile.php': 'Documents',
    'queries_mobile.php': 'Queries',
    'payment-history_mobile.php': 'History',
    'about-dev_mobile.php': 'About Dev'
}

def get_header_html(title):
    return f"""<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else {{ document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }}">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 22px; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px;">{title}</h1>
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
            title = titles.get(filename, 'Page')
            new_header = get_header_html(title)
            new_content = pattern.sub(new_header, content)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename} with title {title}")
        else:
            print(f"No <header> found in {filename}")
