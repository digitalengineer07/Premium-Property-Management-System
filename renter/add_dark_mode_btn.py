import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

dark_mode_html = """
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>"""

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Check if themeToggleMobile is already in the file to prevent duplicates
        if 'id="themeToggleMobile"' not in content:
            # We want to insert dark_mode_html right after the <div class="m-header-module m-header-right"...>
            pattern = re.compile(r'(<div class="m-header-module m-header-right"[^>]*>)')
            
            if pattern.search(content):
                new_content = pattern.sub(r'\1' + dark_mode_html, content)
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Added dark mode button to {filename}")
            else:
                print(f"m-header-right not found in {filename}")
        else:
            print(f"Dark mode button already exists in {filename}")
