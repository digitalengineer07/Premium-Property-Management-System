import os
import re

folders = [
    r'C:\xampp\htdocs\renter-system\renter',
    r'C:\xampp\htdocs\renter-system\renter\views\desktop'
]

css_scrollbar = """
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

                original_content = content
                
                # Update CSS padding for .nav-item
                content = re.sub(r'(\.nav-item\s*\{[^}]*)padding:\s*[^;]+;', r'\1padding: 8px 16px;', content)
                content = re.sub(r'(\.nav-item\s*\{[^}]*)gap:\s*[^;]+;', r'\1gap: 12px;', content)

                # Update CSS for .nav-menu
                content = re.sub(r'(\.nav-menu\s*\{[^}]*)gap:\s*[^;]+;', r'\1gap: 4px;', content)
                content = re.sub(r'(\.nav-menu\s*\{[^}]*)margin-top:\s*[^;]+;', r'\1margin-top: 16px;', content)
                
                if '.nav-menu {' in content and 'overflow-y: auto;' not in content:
                    content = re.sub(r'(\.nav-menu\s*\{[^}]*)(\})', r'\1 overflow-y: auto;\2', content)
                if '.nav-menu::-webkit-scrollbar' not in content and '.nav-menu {' in content:
                    content = re.sub(r'(\.nav-menu\s*\{[^}]*\})', r'\1' + css_scrollbar, content)

                # Move logout button out of nav-menu
                # Find <nav class="nav-menu"> ... </nav> block and if it contains logout, split it.
                nav_blocks = re.finditer(r'<nav class="nav-menu">(.*?)</nav>', content, flags=re.DOTALL)
                for nav_match in nav_blocks:
                    nav_html = nav_match.group(1)
                    if 'logout.php' in nav_html:
                        # Find the logout anchor tag within this specific nav block
                        logout_pattern = r'(\s*<a[^>]*href="[^"]*logout\.php"[^>]*>.*?</a>\s*)'
                        logout_match = re.search(logout_pattern, nav_html, flags=re.IGNORECASE | re.DOTALL)
                        if logout_match:
                            logout_html = logout_match.group(1)
                            # Remove the margin-top auto from the logout link itself
                            clean_logout = re.sub(r'margin-top:\s*[^;"]*;?', '', logout_html, flags=re.IGNORECASE)
                            
                            new_nav_html = nav_html.replace(logout_match.group(1), '')
                            
                            replacement = f'<nav class="nav-menu">{new_nav_html}</nav>\n        <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border, #E2E8F0);">{clean_logout}</div>'
                            
                            content = content.replace(nav_match.group(0), replacement)

                if content != original_content:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Fixed sidebar perfectly in {path}")
