import os
import glob
import re

files = glob.glob(r'c:\xampp\htdocs\renter-system\renter\*.php')

for filepath in files:
    if os.path.isfile(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        modified = False
        
        # We look for the exact string within the @media query
        if '.sidebar { display: none; }' in content:
            content = content.replace('.sidebar { display: none; }', '/* .sidebar { display: none; } REMOVED FOR MOBILE DRAWER */')
            modified = True
            
        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Fixed sidebar hiding in: {os.path.basename(filepath)}")
