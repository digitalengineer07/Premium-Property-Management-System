import os
import re

new_css = """        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }"""

# Matches .user-profile-pill { ... } through .user-info p { ... } or .user-info-sm p { ... }
regex_pattern = r'\.user-profile-pill\s*\{.*?\.user-info(?:-sm)?\s+p\s*\{.*?\}'

for file in os.listdir('.'):
    if file.endswith('.php') and file != 'my-payments.php':
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        modified = False
        
        if file == 'profile.php':
            if 'user-avatar-sm' in content:
                content = content.replace('user-avatar-sm', 'user-avatar')
                content = content.replace('user-info-sm', 'user-info')
                modified = True
                
        new_content, count = re.subn(regex_pattern, new_css, content, flags=re.DOTALL)
        
        if count > 0 or modified:
            with open(file, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {file} (Replaced {count} instances)")
