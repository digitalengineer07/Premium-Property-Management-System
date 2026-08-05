import os
import re

path_mobile = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'
path_desktop = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

# Extract sidebar from desktop
with open(path_desktop, 'r', encoding='utf-8') as f:
    desktop_content = f.read()

# Grab the whole aside block
match = re.search(r'(<aside class="sidebar">.*?</aside>)', desktop_content, re.DOTALL)
if match:
    sidebar_html = match.group(1)
    
    with open(path_mobile, 'r', encoding='utf-8') as f:
        mobile_content = f.read()
        
    # 1. Insert the sidebar right before the <div class="content-area"> if it's not already there
    if '<aside class="sidebar">' not in mobile_content:
        mobile_content = mobile_content.replace('<div class="content-area"', sidebar_html + '\n\n    <div class="content-area"')
        
    # 2. Add renter.js right before </body> if it's not already there
    if 'renter.js' not in mobile_content:
        js_tag = '<script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>\n</body>'
        mobile_content = mobile_content.replace('</body>', js_tag)
        
    with open(path_mobile, 'w', encoding='utf-8') as f:
        f.write(mobile_content)
        
    print("Sidebar and renter.js injected into mobile view successfully.")
else:
    print("Could not find sidebar in desktop view.")
