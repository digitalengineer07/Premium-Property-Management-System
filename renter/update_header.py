import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_html = r"""              <div class="user-profile-pill">
                  <div class="user-avatar"><\?php echo strtoupper\(substr\(\$display_name, 0, 2\)\); \?></div>
                  <div class="user-info">
                      <h4><\?php echo htmlspecialchars\(\$display_name\); \?></h4>
                      <p>Room <\?php echo htmlspecialchars\(\$user\['room_no'\]\); \?></p>
                  </div>
              </div>"""

new_html = """              <div style="position: relative;">
                  <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                      <div class="user-avatar"><?php echo strtoupper(substr($display_name ?? $user['name'] ?? 'User', 0, 2)); ?></div>
                      <div class="user-info">
                          <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                          <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                      </div>
                      <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                  </div>
                  
                  <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                      <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                          <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                      </a>
                      <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 14px; font-weight: 500; transition: 0.2s;">
                          <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                      </a>
                  </div>
              </div>"""

content = re.sub(old_html, new_html, content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated profile header button")
