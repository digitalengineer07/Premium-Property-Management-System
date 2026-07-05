import os
import re

directory = r"c:\xampp\htdocs\renter-system\renter\views\mobile"

old_icon_pattern = re.compile(r"<i class='bx (bx-[^']+)' style=\"font-size: 24px; color: rgba\(255,255,255,0.85\); background: rgba\(255,255,255,0.15\); padding: 4px; border-radius: 8px;\"></i>")

def replacer(match):
    icon_class = match.group(1)
    # Return the icon without background, same color as text, properly sized
    return f"<i class='bx {icon_class}' style=\"font-size: 22px; color: #ffffff; margin-top: 2px;\"></i>"

for filename in os.listdir(directory):
    if filename.endswith("_mobile.php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if old_icon_pattern.search(content):
            new_content = old_icon_pattern.sub(replacer, content)
            
            # Ensure the h1 has correct styling: color #fff, no background gradients that might have stayed
            # Wait, the gradient script might have overridden the h1 style!
            # Let's clean up the h1 style just in case.
            old_h1_gradient = r'<h1 class="m-page-title" style="font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px; background: linear-gradient(90deg, #ffffff 0%, #e0c8ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2)); display: flex; align-items: center; gap: 8px;">'
            # If the user has the gradient, we revert it to plain white flex container
            plain_h1 = r'<h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">'
            
            # The h1 tag could have many forms, let's just do a regex replace for the h1 tag if it has background clip
            h1_pattern = re.compile(r'<h1 class="m-page-title"[^>]*>')
            new_content = h1_pattern.sub(plain_h1, new_content)

            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Adjusted icons in {filename}")
        else:
            print(f"Skipped {filename} (pattern not found)")
