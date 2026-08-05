import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Reduce main-content padding-bottom from 80px to 40px
    content = content.replace("padding-bottom: 80px;", "padding-bottom: 40px;")
    
    # Reduce pagination padding from 20px 0 to 12px 0
    content = content.replace("padding: 20px 0; border-top: 1px solid var(--border);", "padding: 16px 0; border-top: 1px solid var(--border);")
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Reduced bottom spacing.")
