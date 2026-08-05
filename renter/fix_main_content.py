import re
import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Add margin-left to .main-content
    # Find .main-content block
    content = re.sub(r'(\.main-content\s*\{\s*flex:\s*1;)', r'\1\n            margin-left: 230px;', content)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Fixed margin-left in {path}")
