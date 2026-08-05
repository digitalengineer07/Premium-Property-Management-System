import re
import os

files = [
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'
]

css_links = """    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
"""

for path in files:
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace font links
        content = re.sub(r'<link href="https://fonts\.googleapis\.com/css2\?family=Plus\+Jakarta\+Sans[^>]+>', '', content)
        content = re.sub(r'<link href=\'https://unpkg\.com/boxicons@2\.1\.4/css/boxicons\.min\.css\' rel=\'stylesheet\'>', css_links, content)
        
        # Replace font-family CSS
        content = content.replace("font-family: 'Plus Jakarta Sans', sans-serif;", "font-family: 'Outfit', sans-serif;")
        
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated fonts in {path}")
