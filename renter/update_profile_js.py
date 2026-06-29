import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix the Javascript for avatarPreview
old_js = r"const avatarPreview = document\.querySelector\('\.avatar-huge'\);"
new_js = """const avatarPreview = document.getElementById('profileAvatarImg');"""
content = re.sub(old_js, new_js, content)

old_js2 = r"avatarPreview\.src = base64Image;"
new_js2 = """avatarPreview.src = base64Image;
        avatarPreview.style.display = 'block';
        if (document.getElementById('profileAvatarFallback')) {
            document.getElementById('profileAvatarFallback').style.display = 'none';
        }"""
content = re.sub(old_js2, new_js2, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated JS successfully!")
