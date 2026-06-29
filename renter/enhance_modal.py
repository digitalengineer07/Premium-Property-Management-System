import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add CSS for no-scrollbar
css_addition = """
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
"""
if ".no-scrollbar" not in content:
    content = content.replace("</style>", css_addition + "\n    </style>")

# 2. Update Modal HTML
old_modal = r"""<!-- Edit Profile Modal -->
    <div id="editProfileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba\(0,0,0,0\.6\); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur\(5px\);">
        <div style="background: var\(--white\); padding: 32px; border-radius: 24px; max-width: 600px; width: 100%; box-shadow: 0 20px 50px rgba\(0,0,0,0\.3\); max-height: 90vh; overflow-y: auto; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: var\(--text-dark\);">Edit Profile Details</h3>
                <button type="button" onclick="document\.getElementById\('editProfileModal'\)\.style\.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var\(--text-gray\);"><i class='bx bx-x'></i></button>
            </div>"""

new_modal = """<!-- Edit Profile Modal -->
    <div id="editProfileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(8px);">
        <div class="no-scrollbar" style="background: var(--white); padding: 40px; border-radius: 28px; max-width: 620px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.4), inset 0 2px 0 rgba(255,255,255,0.5); max-height: 90vh; overflow-y: auto; box-sizing: border-box; position: relative;">
            
            <!-- Decorative Top Gradient Line -->
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); border-radius: 28px 28px 0 0;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; margin-top: 8px;">
                <h3 style="margin: 0; font-size: 26px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(98, 75, 255, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class='bx bx-edit-alt' style="color: var(--primary-purple); font-size: 24px;"></i>
                    </div>
                    <span style="background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Edit Profile</span>
                </h3>
                <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(0,0,0,0.04); border: none; font-size: 20px; cursor: pointer; color: var(--text-dark); display: flex; align-items: center; justify-content: center; transition: 0.2s;"><i class='bx bx-x'></i></button>
            </div>"""

content = re.sub(old_modal, new_modal, content)

# 3. Enhance input fields in the modal slightly
content = content.replace("background: var(--bg-main);", "background: #F8FAFC;")
content = content.replace("border-radius: 12px;", "border-radius: 14px;")

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated modal design and hid scrollbar!")
