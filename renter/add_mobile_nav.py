import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add CSS
    new_css = """        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span { display: none; }
            .nav-item { justify-content: center; padding: 10px 16px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; padding: 24px; }
            .approvals-table-container { overflow-x: auto; }
            table { min-width: 800px; } /* Ensures table doesn't squish unreadably */
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 16px !important; padding-bottom: 90px !important; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 16px; margin-bottom: 20px; }
            .header-actions { width: 100%; justify-content: space-between; }
            .mobile-bottom-nav { display: flex !important; }
            table { min-width: 600px; }
        }

        .mobile-bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: var(--sidebar-bg);
            justify-content: space-around; align-items: center;
            padding: 12px 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
            border-top: 1px solid var(--border);
            z-index: 1000;
        }
        .dark-theme .mobile-bottom-nav { box-shadow: 0 -4px 20px rgba(0,0,0,0.3); }
        .mb-nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            color: var(--text-gray); text-decoration: none; font-size: 10px; font-weight: 600;
            width: 20%;
        }
        .mb-nav-item i { font-size: 22px; transition: 0.2s; }
        .mb-nav-item.active { color: var(--primary-purple); }
        .mb-nav-item.active i { transform: translateY(-2px); }
        .mb-nav-center {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--primary-purple); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer; margin-top: -24px;
            border: 4px solid var(--bg-main); transition: transform 0.2s;
        }"""
        
    old_css = """        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span { display: none; }
            .nav-item { justify-content: center; padding: 10px 16px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; padding: 24px; }
            .approvals-table-container { overflow-x: auto; }
            table { min-width: 800px; } /* Ensures table doesn't squish unreadably */
        }"""
        
    content = content.replace(old_css, new_css)
    
    # 2. Add HTML
    nav_html = """    <!-- Mobile Bottom Navigation (Visible only on small screens) -->
    <nav class="mobile-bottom-nav">
        <a href="dashboard.php" class="mb-nav-item">
            <i class='bx bx-home-alt-2'></i>
            <span>Home</span>
        </a>
        <a href="my-bills.php" class="mb-nav-item">
            <i class='bx bx-receipt'></i>
            <span>Bills</span>
        </a>
        <div class="mb-nav-center" onclick="openApprovalModal()">
            <i class='bx bx-plus'></i>
        </div>
        <a href="payment-approvals.php" class="mb-nav-item active">
            <i class='bx bx-check-shield'></i>
            <span>Approvals</span>
        </a>
        <a href="profile.php" class="mb-nav-item">
            <i class='bx bx-user'></i>
            <span>Profile</span>
        </a>
    </nav>
    
    <?php include "approval_modal.php"; ?>"""
    
    content = content.replace('<?php include "approval_modal.php"; ?>', nav_html)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Mobile responsive navbar added.")
