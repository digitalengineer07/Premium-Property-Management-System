import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"
header_html = """<header class="premium-header-pill">
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
</header>"""

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Use regex to find the <header>...</header> block. 
        # Some might have classes or styles.
        pattern = re.compile(r'<header[^>]*>.*?</header>', re.DOTALL)
        
        if pattern.search(content):
            new_content = pattern.sub(header_html, content)
            
            # Also, we need to ensure the element right after <header> gets padding-top: 100px so it's not hidden
            # Find the first <div after the header
            # But wait, it's safer to just inject a spacer div right after the header!
            # That way we don't break existing layouts.
            spacer = '\n<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>\n'
            
            # If the file already has my new header, it might already have the spacer or padding.
            if spacer not in new_content and filename != 'dashboard_mobile.php':
                new_content = new_content.replace('</header>', '</header>' + spacer)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename}")
        else:
            print(f"No <header> found in {filename}")
