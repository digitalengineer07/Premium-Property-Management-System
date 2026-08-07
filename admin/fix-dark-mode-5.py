import os
import re

fpath = 'reports.php'
if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    orig_content = content
    content = content.replace('ctx.fillStyle = "#0F172A";', 'ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--text-dark").trim() || "#0F172A";')
    content = content.replace('background: linear-gradient(90deg, #0F172A 0%', 'background: linear-gradient(90deg, var(--text-dark) 0%')
    
    if content != orig_content:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Fixed text and border colors in {fpath}')
