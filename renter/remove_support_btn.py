import re
import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove the Help & Support button
    pattern = r'<a href="queries\.php" class="btn-outline-support">\s*<i class=\'bx bx-help-circle\'></i> Help & Support\s*</a>'
    content = re.sub(pattern, '', content, flags=re.IGNORECASE)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Removed Help & Support button successfully.")
