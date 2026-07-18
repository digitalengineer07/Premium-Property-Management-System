import os
import re

folders = [
    r'C:\xampp\htdocs\renter-system\renter',
    r'C:\xampp\htdocs\renter-system\renter\views\desktop'
]

for folder in folders:
    for root, dirs, files in os.walk(folder):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()

                modified = False

                # Reduce gap in nav-menu
                if '.nav-menu {' in content:
                    new_content = re.sub(r'(\.nav-menu\s*\{[^}]*)gap:\s*8px;', r'\1gap: 4px;', content)
                    if new_content != content:
                        content = new_content
                        modified = True
                        
                    new_content = re.sub(r'(\.nav-menu\s*\{[^}]*)margin-top:\s*30px;', r'\1margin-top: 16px;', content)
                    if new_content != content:
                        content = new_content
                        modified = True

                # Reduce padding in nav-item
                if '.nav-item {' in content:
                    new_content = re.sub(r'(\.nav-item\s*\{[^}]*)padding:\s*12px 16px;', r'\1padding: 10px 16px;', content)
                    if new_content != content:
                        content = new_content
                        modified = True

                if modified:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Adjusted sidebar size in {path}")
