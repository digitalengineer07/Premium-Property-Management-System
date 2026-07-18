import os
import re

path = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Remove the old mobile-bottom-nav inline CSS completely because admin-design-system.css handles it perfectly
    pattern = r'/\* Mobile Bottom Nav \*/.*?\.mb-nav-center \{.*?\n        \}\n'
    content = re.sub(pattern, '', content, flags=re.DOTALL)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print("Cleaned up redundant bottom nav CSS from mobile view")
