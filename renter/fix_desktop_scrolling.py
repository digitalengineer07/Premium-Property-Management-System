import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old_css = """        .main-content {
            flex: 1;
            margin-left: 230px;
           
            padding: 32px 40px;
        }"""
        
    new_css = """        .main-content {
            flex: 1;
            margin-left: 230px;
            height: 100vh;
            overflow-y: auto;
            padding: 32px 40px;
            padding-bottom: 80px; /* Extra padding so the bottom isn't cut off */
        }"""
        
    content = content.replace(old_css, new_css)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed main-content scrolling.")
