import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Replace padding-bottom: 96px with padding-bottom: 85px
        content = re.sub(r'padding-bottom:\s*96px;?', 'padding-bottom: 85px;', content)
        
        # Replace inline padding 
        content = content.replace('padding: 100px 16px 96px 16px;', 'padding: 100px 16px 85px 16px;')
        content = content.replace('padding: 0 16px 96px 16px;', 'padding: 0 16px 85px 16px;')
        content = content.replace('padding: 16px 16px 96px 16px;', 'padding: 16px 16px 85px 16px;')
        
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated padding in {filename} to 85px")
        else:
            print(f"No changes needed for {filename}")
