import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_css = r"""\.avatar-card \{ text-align: center; background: #F8F7FF; border: none; padding: 20px; display: flex; flex-direction: column; justify-content: center; height: 340px; \}"""
new_css = """.avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 50px 20px; display: flex; flex-direction: column; justify-content: center; min-height: 350px; }"""

content = re.sub(old_css, new_css, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated height!")
