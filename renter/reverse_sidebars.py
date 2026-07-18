import os
import glob

files = glob.glob(r'c:\xampp\htdocs\renter-system\renter\*.php')

for filepath in files:
    if os.path.isfile(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        modified = False
        
        if '/* .sidebar { display: none !important; } REMOVED FOR MOBILE DRAWER */' in content:
            content = content.replace('/* .sidebar { display: none !important; } REMOVED FOR MOBILE DRAWER */', '.sidebar { display: none !important; }')
            modified = True
            
        if '/* .sidebar { display: none; } REMOVED FOR MOBILE DRAWER */' in content:
            content = content.replace('/* .sidebar { display: none; } REMOVED FOR MOBILE DRAWER */', '.sidebar { display: none; }')
            modified = True
            
        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Reverted sidebar hiding in: {os.path.basename(filepath)}")

desktop_approvals = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'
if os.path.exists(desktop_approvals):
    with open(desktop_approvals, 'r', encoding='utf-8') as f:
        content = f.read()
    if '/* .sidebar { display: none !important; } REMOVED FOR MOBILE DRAWER */' in content:
        new_content = content.replace('/* .sidebar { display: none !important; } REMOVED FOR MOBILE DRAWER */', '.sidebar { display: none !important; }')
        with open(desktop_approvals, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Reverted sidebar hiding in: {os.path.basename(desktop_approvals)}")
