import os
import re

folders = [
    r'C:\xampp\htdocs\renter-system\renter',
    r'C:\xampp\htdocs\renter-system\renter\views\desktop'
]

css_to_add = """
        .nav-menu::-webkit-scrollbar { width: 4px; }
        .nav-menu::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
"""

for folder in folders:
    for root, dirs, files in os.walk(folder):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()

                modified = False

                # 1. Make nav-menu scrollable
                if '.nav-menu {' in content and 'overflow-y: auto;' not in content:
                    content = re.sub(r'(\.nav-menu\s*\{[^}]*)(\})', r'\1 overflow-y: auto;\2', content)
                    if '.nav-menu::-webkit-scrollbar' not in content:
                        content = re.sub(r'(\.nav-menu\s*\{[^}]*\})', r'\1' + css_to_add, content)
                    modified = True

                # 2. Extract Logout button and place it outside nav-menu
                logout_regex = r'(\s*<a[^>]*href="[^"]*logout\.php"[^>]*>.*?</a>\s*)(</nav>)'
                match = re.search(logout_regex, content, re.IGNORECASE | re.DOTALL)
                if match:
                    logout_html = match.group(1)
                    # Remove from inside nav
                    content = content.replace(match.group(0), '\n        </nav>\n        <div class="sidebar-footer" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border, #E2E8F0);">' + logout_html + '</div>')
                    
                    # Ensure margin-top is removed from the logout link itself so it doesn't push down unnecessarily
                    content = re.sub(r'(<a[^>]*href="[^"]*logout\.php"[^>]*style="[^"]*)margin-top:\s*[^;"]*;?', r'\1', content, flags=re.IGNORECASE)
                    
                    modified = True

                if modified:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Updated sidebar layout in {path}")
