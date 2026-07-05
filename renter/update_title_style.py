import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

old_h1 = r'<h1 class="m-page-title" style="font-size: 22px; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px;">'
new_h1 = r'<h1 class="m-page-title" style="font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px; background: linear-gradient(90deg, #ffffff 0%, #e0c8ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));">'

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if old_h1 in content:
            new_content = content.replace(old_h1, new_h1)
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename}")
        else:
            print(f"Skipped {filename} (not found)")
