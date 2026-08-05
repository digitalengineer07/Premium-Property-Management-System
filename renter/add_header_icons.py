import os

def update_file(path, is_desktop=False):
    if not os.path.exists(path): return
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Search for the newly added purple header
    if 'class="premium-header-pill' not in content:
        return

    # Extract the header block to replace it
    import re
    # Find the header block
    header_pattern = r'(<header class="premium-header-pill[^>]*>)(.*?)(</header>)'
    
    match = re.search(header_pattern, content, flags=re.DOTALL)
    if not match:
        return
        
    old_header_inner = match.group(2)
    
    # We will build a complete replacement for the header
    header_class = 'premium-header-pill mobile-header-only' if is_desktop else 'premium-header-pill'
    
    new_header = f"""<header class="{header_class}" style="position: fixed; top: 0; left: 0;">
            <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
                <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else {{ document.querySelector('.sidebar')?.classList.toggle('mobile-drawer-open'); }}" style="color: white; font-size: 28px; cursor: pointer;">
                    <i class='bx bx-menu-alt-left'></i>
                </div>
                <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: #ffffff; margin-top: 2px;"></i>
                    Approvals
                </h1>
            </div>
            
            <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 6px;">
                <div class="header-icon-btn" onclick="openApprovalModal()" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid; cursor: pointer;">
                    <i class='bx bx-plus' style="color: white;"></i>
                </div>
                <div class="header-icon-btn" onclick="if(typeof openNotif==='function') openNotif(); else alert('Notifications');" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid; cursor: pointer; position: relative;">
                    <i class='bx bx-bell' style="color: white;"></i>
                    <?php if (isset($unread_count) && $unread_count > 0): ?>
                        <span class="m-notif-badge" style="position: absolute; top: 0; right: 0; width: 10px; height: 10px; background: #EF4444; border-radius: 50%; border: 2px solid #624BFF;"></span>
                    <?php endif; ?>
                </div>
                <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
                    <div style="width: 100%; height: 100%; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #624BFF; font-size: 16px; font-weight: 800;">
                        <?php echo strtoupper(substr(trim($display_name ?? 'U'), 0, 1)); ?>
                    </div>
                </a>
            </div>
        </header>"""

    new_content = content[:match.start()] + new_header + content[match.end():]
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(new_content)

path_desktop = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'
path_mobile = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

update_file(path_desktop, is_desktop=True)
update_file(path_mobile, is_desktop=False)

print("Headers updated successfully!")
