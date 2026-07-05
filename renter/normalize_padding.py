import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # We want to change any padding-bottom that is >= 90px to exactly 96px to ensure a perfect 16px gap.
        # Handle inline styles like `padding: 16px 16px 120px 16px;`
        content = re.sub(r'padding:\s*(\d+px)\s+(\d+px)\s+(\d{2,3}px)\s+(\d+px);', 
                         lambda m: f'padding: {m.group(1)} {m.group(2)} 96px {m.group(4)};' if int(m.group(3)[:-2]) >= 90 else m.group(0), 
                         content)
                         
        content = re.sub(r'padding-bottom:\s*\d{2,3}px;', 'padding-bottom: 96px;', content)
        
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Normalized padding in {filename}")
        else:
            print(f"No changes needed for {filename}")
