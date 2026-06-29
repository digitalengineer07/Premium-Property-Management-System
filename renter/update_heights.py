import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Make avatar card height match Account & Security height
old_avatar_css = r"""        \.avatar-card \{ text-align: center; background: #F8F7FF; border: none; padding: 40px 20px; \}"""
new_avatar_css = """        .avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 20px; display: flex; flex-direction: column; justify-content: center; height: 340px; }"""
content = re.sub(old_avatar_css, new_avatar_css, content)

# Make Account & Security panel the same height
old_acc_sec = r"""        <div class="grid-col-right">
            <!-- Account & Security -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-check-shield'></i> Account & Security</h3>"""
new_acc_sec = """        <div class="grid-col-right">
            <!-- Account & Security -->
            <div class="panel" style="height: 340px;">
                <div class="panel-header">
                    <h3><i class='bx bx-check-shield'></i> Account & Security</h3>"""
content = re.sub(old_acc_sec, new_acc_sec, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated heights successfully!")
