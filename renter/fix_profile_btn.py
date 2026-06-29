import os
import re

standard_css = """        .user-profile-pill {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
            white-space: nowrap;
        }
        .user-avatar { width: 38px; height: 38px; background: var(--primary-purple); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; }"""

standard_html = """                <div style="position: relative;">
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

files_to_fix = [
    'profile.php',
    'change-password.php',
    'about-dev.php'
]

for file in files_to_fix:
    if not os.path.exists(file):
        continue
    
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    classes_to_remove = ['.user-profile-pill', '.user-avatar', '.user-info', '.user-info h4', '.user-info p']
    for cls in classes_to_remove:
        pattern2 = r'\s*' + cls.replace('.', r'\.') + r'\s*\{[^}]*\}'
        content = re.sub(pattern2, '', content)

    content = content.replace('</style>', standard_css + '\n    </style>')

    header_actions_start = content.find('<div class="header-actions">')
    if header_actions_start != -1:
        header_actions_end = content.find('</header>', header_actions_start)
        header_actions_block = content[header_actions_start:header_actions_end]
        
        help_idx = header_actions_block.find('Help & Support')
        if help_idx != -1:
            a_close_idx = header_actions_block.find('</a>', help_idx)
            if a_close_idx != -1:
                last_div_idx = header_actions_block.rfind('</div>')
                if last_div_idx != -1:
                    new_header_actions = header_actions_block[:a_close_idx+4] + '\n' + standard_html + '\n            ' + header_actions_block[last_div_idx:]
                    content = content[:header_actions_start] + new_header_actions + content[header_actions_end:]
    
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Updated {file}")
