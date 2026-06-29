import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix avatar-card align-items
old_css = r"""\.avatar-card \{ text-align: center; background: #F8F7FF; border: none; padding: 50px 20px; display: flex; flex-direction: column; justify-content: center; min-height: 350px; \}"""
new_css = """.avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 50px 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 350px; }"""
content = re.sub(old_css, new_css, content)

# Enhance fallback avatar
old_fallback = r"""<div class="avatar-huge" id="profileAvatarFallback" style="display: flex; align-items: center; justify-content: center; background: var\(--primary-purple\); color: white; font-size: 48px; font-weight: 700; letter-spacing: 2px;">"""
new_fallback = """<div class="avatar-huge" id="profileAvatarFallback" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); box-shadow: 0 10px 25px rgba(98, 75, 255, 0.25); color: white; font-size: 48px; font-weight: 700; letter-spacing: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">"""
content = re.sub(old_fallback, new_fallback, content)

# Enhance Room badge
old_badge = r"""<span style="display: inline-block; padding: 6px 16px; background: rgba\(98, 75, 255, 0\.08\); color: var\(--primary-purple\); font-weight: 700; border-radius: 20px; font-size: 13px;">Room <\?php echo htmlspecialchars\(\$user\['room_no'\]\); \?></span>"""
new_badge = """<span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 18px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); font-weight: 700; border-radius: 20px; font-size: 13.5px; box-shadow: inset 0 0 0 1px rgba(98, 75, 255, 0.1);"><i class='bx bx-door-open' style="font-size: 17px;"></i> Room <?php echo htmlspecialchars($user['room_no']); ?></span>"""
content = re.sub(old_badge, new_badge, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated aesthetics!")
