import os
import re

files_to_check = []
for root, dirs, files in os.walk('.'):
    for f in files:
        if f.endswith('.php'):
            files_to_check.append(os.path.join(root, f))

patterns = [
    # Remaining dark text colors
    (r'color:\s*#0F172A\b', 'color: var(--text-dark)'),
    (r'color:\s*#0f172a\b', 'color: var(--text-dark)'),
    (r'color:\s*#111827\b', 'color: var(--text-dark)'),
    (r'color:\s*#4B5563\b', 'color: var(--text-gray)'),
    (r'color:\s*#4b5563\b', 'color: var(--text-gray)'),
    (r'color:\s*#CBD5E1\b', 'color: var(--text-gray)'),
    (r'color:\s*#cbd5e1\b', 'color: var(--text-gray)'),
    
    # Remaining light backgrounds
    (r'background:\s*#F4F7FF\b', 'background: var(--bg-main)'),
    (r'background:\s*#f4f7ff\b', 'background: var(--bg-main)'),
    (r'background:\s*#F3F4F6\b', 'background: var(--bg-main)'),
    (r'background:\s*#f3f4f6\b', 'background: var(--bg-main)'),
    (r'background:\s*#EEF2FF\b', 'background: var(--bg-main)'),
    (r'background:\s*#eef2ff\b', 'background: var(--bg-main)'),
    (r'background-color:\s*#F4F7FF\b', 'background-color: var(--bg-main)'),
    (r'background-color:\s*#F3F4F6\b', 'background-color: var(--bg-main)'),
    
    # Remaining light borders
    (r'border-color:\s*#E0E7FF\b', 'border-color: var(--border)'),
    (r'border-color:\s*#e0e7ff\b', 'border-color: var(--border)'),
    (r'border-color:\s*#CBD5E1\b', 'border-color: var(--border)'),
    (r'border:\s*1px\s*solid\s*#CBD5E1\b', 'border: 1px solid var(--border)'),
    
    # Chart.js hardcoded grid colors (mainly in reports.php and dashboard.php)
    (r"grid:\s*\{\s*color:\s*'#F1F5F9'", "grid: { color: 'rgba(100, 116, 139, 0.15)'"),
    (r"grid:\s*\{\s*color:\s*'#f1f5f9'", "grid: { color: 'rgba(100, 116, 139, 0.15)'"),
    (r"color:\s*'#475569'", "color: '#94A3B8'") # Chart legend text
]

for fpath in files_to_check:
    if 'utils_mailer.php' in fpath or 'slip.php' in fpath:
        continue

    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    orig_content = content
    for p, repl in patterns:
        content = re.sub(p, repl, content, flags=re.IGNORECASE)
        
    if content != orig_content:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Fixed missing dark mode colors in {fpath}')
