import os
import re

files_to_check = []
for root, dirs, files in os.walk('.'):
    for f in files:
        if f.endswith('.php'):
            files_to_check.append(os.path.join(root, f))

patterns = [
    (r'background:\s*#FFFFFF\b', 'background: var(--white)'),
    (r'background:\s*#ffffff\b', 'background: var(--white)'),
    (r'background:\s*#fff\b', 'background: var(--white)'),
    (r'background:\s*#FFF\b', 'background: var(--white)'),
    (r'background:\s*white\b', 'background: var(--white)'),
    (r'background-color:\s*white\b', 'background-color: var(--white)'),
    (r'background-color:\s*#FFFFFF\b', 'background-color: var(--white)')
]

for fpath in files_to_check:
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    orig_content = content
    for p, repl in patterns:
        content = re.sub(p, repl, content)
        
    if content != orig_content:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Fixed {fpath}')
