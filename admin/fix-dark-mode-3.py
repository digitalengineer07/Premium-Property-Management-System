import os
import re

files_to_check = []
for root, dirs, files in os.walk('.'):
    for f in files:
        if f.endswith('.php'):
            files_to_check.append(os.path.join(root, f))

patterns = [
    (r'color:\s*#1E293B\b', 'color: var(--text-dark)'),
    (r'color:\s*#1e293b\b', 'color: var(--text-dark)'),
    (r'color:\s*#334155\b', 'color: var(--text-dark)'),
    (r'color:\s*#475569\b', 'color: var(--text-gray)'),
    (r'color:\s*#64748B\b', 'color: var(--text-gray)'),
    (r'color:\s*#64748b\b', 'color: var(--text-gray)'),
    (r'border:\s*1px\s*solid\s*#CBD5E1\b', 'border: 1px solid var(--border)'),
    (r'border:\s*1px\s*solid\s*#E2E8F0\b', 'border: 1px solid var(--border)'),
    (r'border-color:\s*#CBD5E1\b', 'border-color: var(--border)'),
    (r'border-color:\s*#E2E8F0\b', 'border-color: var(--border)')
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
        print(f'Fixed text and border colors in {fpath}')
