import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace avatar CSS
old_avatar_css = r"""        \.avatar-huge \{ width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid var\(--white\); box-shadow: 0 8px 20px rgba\(0,0,0,0\.08\); background: var\(--white\); \}
        \.btn-edit-avatar \{ position: absolute; bottom: 5px; right: 5px; width: 36px; height: 36px; border-radius: 50%; background: var\(--white\); border: none; box-shadow: 0 4px 10px rgba\(0,0,0,0\.1\); color: var\(--primary-purple\); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: var\(--transition\); \}"""

new_avatar_css = """        .avatar-huge { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: var(--white); }
        .btn-edit-avatar { position: absolute; bottom: 4px; right: 4px; width: 40px; height: 40px; border-radius: 50%; background: var(--white); border: 3px solid #F8F7FF; box-shadow: 0 4px 10px rgba(0,0,0,0.05); color: var(--primary-purple); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: var(--transition); }"""

content = re.sub(old_avatar_css, new_avatar_css, content)

# Replace the HTML badge part
old_badge_html = r"""<span style="display: inline-block; padding: 6px 16px; background: var\(--white\); color: var\(--primary-purple\); font-weight: 700; border-radius: 20px; font-size: 13px; box-shadow: 0 2px 6px rgba\(0,0,0,0\.05\);">Room <\?php echo htmlspecialchars\(\$user\['room_no'\]\); \?></span>"""

new_badge_html = """<span style="display: inline-block; padding: 6px 16px; background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); font-weight: 700; border-radius: 20px; font-size: 13px;">Room <?php echo htmlspecialchars($user['room_no']); ?></span>"""

content = re.sub(old_badge_html, new_badge_html, content)

# Update name spacing to match exactly
old_name_html = r"""<h2 style="margin: 0 0 8px 0; font-weight: 800; font-size: 24px; color: var\(--text-dark\);">"""
new_name_html = """<h2 style="margin: 0 0 12px 0; font-weight: 800; font-size: 22px; color: var(--text-dark); letter-spacing: -0.5px;">"""
content = re.sub(old_name_html, new_name_html, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated profile picture styling!")
