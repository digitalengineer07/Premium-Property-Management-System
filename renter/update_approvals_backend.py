import re
import os

path = r'c:\xampp\htdocs\renter-system\renter\payment-approvals.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Replace $p_month assignment
    old_code = "$p_month = 'Advance/General';"
    new_code = "$p_month = !empty($_POST['bill_month']) ? date('F Y', strtotime($_POST['bill_month'].'-01')) : 'Advance/General';"
    
    content = content.replace(old_code, new_code)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated payment-approvals.php successfully.")
