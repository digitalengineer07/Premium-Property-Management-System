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
    'dashboard.php',
    'my-bills.php',
    'electricity-record.php',
    'queries.php',
    'notices.php',
    'documents.php',
    'payment-history.php'
]

for file in files_to_fix:
    if not os.path.exists(file):
        continue
    
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update CSS
    # Let's remove any existing .user-profile-pill and related classes and insert our standard one.
    # Find insertion point: usually after .btn-outline-support:hover or .header-actions or similar
    
    # Try to extract the block starting with .user-profile-pill and ending before the next major block
    css_pattern = re.compile(r'\.user-profile-pill\s*\{.*?(?=\n\s*/\*|\n\s*\.|\n\s*\#|\n\s*</style>|\n\s*@media)', re.DOTALL)
    
    # Actually, it's safer to remove specific classes
    classes_to_remove = ['.user-profile-pill', '.user-avatar', '.user-info', '.user-info h4', '.user-info p']
    for cls in classes_to_remove:
        # Regex to remove class definition, e.g. .user-avatar { ... }
        # Match cls exactly followed by space or {
        pattern = r'^\s*' + cls.replace('.', r'\.') + r'(?:\s*\{[^}]*\}|\s+h4\s*\{[^}]*\}|\s+p\s*\{[^}]*\})'
        # Also handle one-liners
        pattern2 = r'\s*' + cls.replace('.', r'\.') + r'\s*\{[^}]*\}'
        content = re.sub(pattern2, '', content)
        # Handle cases where multiple classes are defined together? We don't have that here.

    # Now insert the standard CSS
    # Insert right before </style>
    content = content.replace('</style>', standard_css + '\n    </style>')

    # 2. Update HTML
    # The profile button is always the last child of <div class="header-actions">
    # We can match from the last closing </a> or </div> before it, up to </div>\n        </header>
    
    # The easiest way is to find <div class="header-actions">, and find the corresponding closing </div>
    # But regex for nested divs is hard. Let's find the header-actions block
    
    header_actions_start = content.find('<div class="header-actions">')
    if header_actions_start != -1:
        header_actions_end = content.find('</header>', header_actions_start)
        header_actions_block = content[header_actions_start:header_actions_end]
        
        # We know the profile button starts with <div class="user-profile-pill" or <div style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-left: 8px;"> or similar.
        # Let's just find the "Help & Support" link, and replace everything after its closing </a> up to the end of header_actions_block with our standard_html.
        
        help_idx = header_actions_block.find('Help & Support')
        if help_idx != -1:
            a_close_idx = header_actions_block.find('</a>', help_idx)
            if a_close_idx != -1:
                # Replace from a_close_idx + 4 up to the last </div> before </header>
                # Find the last </div> in header_actions_block
                last_div_idx = header_actions_block.rfind('</div>')
                if last_div_idx != -1:
                    new_header_actions = header_actions_block[:a_close_idx+4] + '\n' + standard_html + '\n            ' + header_actions_block[last_div_idx:]
                    content = content[:header_actions_start] + new_header_actions + content[header_actions_end:]
    
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Updated {file}")
