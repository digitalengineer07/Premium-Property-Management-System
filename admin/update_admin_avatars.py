import re

# Update manage-renters.php
with open('manage-renters.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_manage = r'<div style="width: 40px; height: 40px; border-radius: 50%; background: #F4F7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; border: 2px solid #FFFFFF; box-shadow: 0 2px 5px rgba\(98, 75, 255, 0\.1\);">.*?</div>'
new_manage = """<div style="width: 40px; height: 40px; border-radius: 50%; background: #F4F7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; border: 2px solid #FFFFFF; box-shadow: 0 2px 5px rgba(98, 75, 255, 0.1); overflow: hidden;">
                                          <?php if (!empty($r['profile_pic'])): ?>
                                              <img src="../<?php echo htmlspecialchars($r['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                          <?php else: ?>
                                              <?php echo $initials ?: '?'; ?>
                                          <?php endif; ?>
                                      </div>"""

content = re.sub(old_manage, new_manage, content, flags=re.DOTALL)
with open('manage-renters.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated manage-renters.php")

# Update view-renter.php
with open('view-renter.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_view = r'<div class="avatar" style="width: 100px; height: 100px; border-radius: 50%; background: #F8FAFC; color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; margin-bottom: 16px; border: 4px solid white; box-shadow: 0 10px 25px rgba\(0,0,0,0\.05\);">.*?</div>'
new_view = """<div class="avatar" style="width: 100px; height: 100px; border-radius: 50%; background: #F8FAFC; color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; margin-bottom: 16px; border: 4px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;">
                          <?php if (!empty($renter['profile_pic'])): ?>
                              <img src="../<?php echo htmlspecialchars($renter['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                          <?php else: ?>
                              <?php echo $initials ?: '?'; ?>
                          <?php endif; ?>
                      </div>"""

content = re.sub(old_view, new_view, content, flags=re.DOTALL)
with open('view-renter.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated view-renter.php")
