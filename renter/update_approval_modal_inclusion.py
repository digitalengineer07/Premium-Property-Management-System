import re
import os

files = [
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'
]

for path in files:
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace the include statement
        content = content.replace('include "payment_modal.php";', 'include "approval_modal.php";')
        
        # Replace the button onclick for apply
        # The button has openPaymentModal(0, 'Advance / General Payment', 'general', 0, 'Advance/General') or similar
        content = re.sub(r'onclick="openPaymentModal\([^)]*\)"', 'onclick="openApprovalModal()"', content)
        
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {path}")
