import os

# 1. Update payment-approvals_desktop.php
path_desktop = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path_desktop):
    with open(path_desktop, 'r', encoding='utf-8') as f:
        content_d = f.read()

    # CSS update
    css_old = """.top-header { flex-direction: column; align-items: flex-start; gap: 16px; margin-bottom: 20px; }"""
    css_new = """.top-header { display: none !important; }
            .mobile-header-only { display: flex !important; }
            .main-content { padding-top: 100px !important; }"""
    
    if css_old in content_d:
        content_d = content_d.replace(css_old, css_new)

    # Make sure .mobile-header-only is hidden by default
    css_hide_old = """.mobile-bottom-nav {"""
    css_hide_new = """.mobile-header-only { display: none; }\n        .mobile-bottom-nav {"""
    if css_hide_old in content_d:
        content_d = content_d.replace(css_hide_old, css_hide_new)

    # HTML update
    html_old = """    <div class="main-content">
        <div class="top-header">"""
        
    html_new = """    <div class="main-content">
        <!-- Mobile Purple Header -->
        <header class="premium-header-pill mobile-header-only" style="position: fixed; top: 0; left: 0;">
            <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
                <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: #ffffff; margin-top: 2px;"></i>
                    Approvals
                </h1>
            </div>
            
            <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 6px;">
                <div class="header-icon-btn" onclick="openApprovalModal()" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid;">
                    <i class='bx bx-plus' style="color: white;"></i>
                </div>
                <div class="header-icon-btn" id="themeToggleMobile" onclick="const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid;">
                    <i class='bx bx-moon' style="color: white;"></i>
                </div>
                <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
                    <div style="width: 100%; height: 100%; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #624BFF; font-size: 16px; font-weight: 800;">
                        <?php echo strtoupper(substr(trim($display_name ?? 'U'), 0, 1)); ?>
                    </div>
                </a>
            </div>
        </header>

        <div class="top-header">"""
        
    if html_old in content_d:
        content_d = content_d.replace(html_old, html_new)

    with open(path_desktop, 'w', encoding='utf-8') as f:
        f.write(content_d)


# 2. Update payment-approvals_mobile.php
path_mobile = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

if os.path.exists(path_mobile):
    with open(path_mobile, 'r', encoding='utf-8') as f:
        content_m = f.read()

    import re
    # Remove top-bar CSS
    content_m = re.sub(r'\.top-bar \{.*?\n        \}\n', '', content_m, flags=re.DOTALL)
    content_m = re.sub(r'\.top-bar \.avatar \{.*?\n        \}\n', '', content_m, flags=re.DOTALL)
    content_m = re.sub(r'\.top-bar \.greeting \{.*?\n        \}\n', '', content_m, flags=re.DOTALL)
    content_m = re.sub(r'\.top-bar \.name \{.*?\n        \}\n', '', content_m, flags=re.DOTALL)
    content_m = re.sub(r'\.btn-apply \{.*?\n        \}\n', '', content_m, flags=re.DOTALL)

    # Replace top-bar HTML with premium header
    html_mobile_old = """    <div class="top-bar">
        <div class="user-info">
            <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="avatar">
            <div>
                <p class="greeting">Approvals</p>
                <h1 class="name"><?php echo htmlspecialchars($display_name); ?></h1>
            </div>
        </div>
        <button class="btn-apply" onclick="openApprovalModal()">
            <i class='bx bx-plus'></i> Apply
        </button>
    </div>

    <div class="content-area">"""

    html_mobile_new = """    <header class="premium-header-pill" style="position: fixed; top: 0; left: 0;">
        <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
            <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                <i class='bx bx-check-shield' style="font-size: 24px; color: #ffffff; margin-top: 2px;"></i>
                Approvals
            </h1>
        </div>
        
        <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 6px;">
            <div class="header-icon-btn" onclick="openApprovalModal()" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid;">
                <i class='bx bx-plus' style="color: white;"></i>
            </div>
            <div class="header-icon-btn" id="themeToggleMobile" onclick="const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid;">
                <i class='bx bx-moon' style="color: white;"></i>
            </div>
            <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
                <div style="width: 100%; height: 100%; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #624BFF; font-size: 16px; font-weight: 800;">
                    <?php echo strtoupper(substr(trim($display_name ?? 'U'), 0, 1)); ?>
                </div>
            </a>
        </div>
    </header>

    <div class="content-area" style="padding-top: 100px;">"""

    if """<div class="top-bar">""" in content_m:
        content_m = content_m.replace(html_mobile_old, html_mobile_new)
    
    with open(path_mobile, 'w', encoding='utf-8') as f:
        f.write(content_m)

print("Headers updated successfully!")
