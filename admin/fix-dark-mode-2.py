import os
import re

files_to_check = []
for root, dirs, files in os.walk('.'):
    for f in files:
        if f.endswith('.php'):
            files_to_check.append(os.path.join(root, f))

patterns = [
    (r'background:\s*#F8FAFC\b', 'background: var(--bg-main)'),
    (r'background:\s*#f8fafc\b', 'background: var(--bg-main)'),
    (r'background:\s*#F1F5F9\b', 'background: var(--bg-main)'),
    (r'background:\s*#f1f5f9\b', 'background: var(--bg-main)'),
    (r'background-color:\s*#F8FAFC\b', 'background-color: var(--bg-main)'),
    (r'background-color:\s*#f8fafc\b', 'background-color: var(--bg-main)'),
    (r'background-color:\s*#F1F5F9\b', 'background-color: var(--bg-main)'),
    (r'background-color:\s*#f1f5f9\b', 'background-color: var(--bg-main)')
]

for fpath in files_to_check:
    if 'utils_mailer.php' in fpath or 'slip.php' in fpath:
        continue # skip emails and printable slips

    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    orig_content = content
    for p, repl in patterns:
        content = re.sub(p, repl, content)
        
    if content != orig_content:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Fixed {fpath}')
