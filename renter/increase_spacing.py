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

                original_content = content
                
                # Update CSS padding for .nav-item
                content = re.sub(r'(\.nav-item\s*\{[^}]*)padding:\s*[^;]+;', r'\1padding: 10px 16px;', content)

                # Update CSS for .nav-menu
                content = re.sub(r'(\.nav-menu\s*\{[^}]*)gap:\s*[^;]+;', r'\1gap: 8px;', content)
                
                if content != original_content:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Increased sidebar spacing in {path}")
