import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remove the hardcoded height on the Account & Security panel
old_panel = r"""<div class="panel" style="height: 340px;">"""
new_panel = """<div class="panel">"""
content = re.sub(old_panel, new_panel, content)

# 2. Adjust avatar-card height back to normal so it doesn't leave massive empty space now that the right side is smaller
old_css = r"""\.avatar-card \{ text-align: center; background: #F8F7FF; border: none; padding: 50px 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 350px; \}"""
new_css = """.avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 32px 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 270px; }"""
content = re.sub(old_css, new_css, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated heights!")
