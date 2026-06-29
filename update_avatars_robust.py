import re
import glob

# Step 1: Update electricity-record.php query
with open('renter/electricity-record.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace("SELECT name, email, room_no FROM users", "SELECT name, email, room_no, profile_pic FROM users")
with open('renter/electricity-record.php', 'w', encoding='utf-8') as f:
    f.write(content)

# Step 2: Make the avatar logic robust to handle $usr, $user, and $profile_pic
new_avatar_inner = """<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>"""

for filepath in glob.glob('renter/*.php'):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find <div class="user-avatar" ... > ... </div>
    old_avatar_pattern = r'<div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var\(--primary-purple\);">.*?</div>'
    
    def replacer(match):
        return f'<div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">\n{new_avatar_inner}\n</div>'
    
    new_content = re.sub(old_avatar_pattern, replacer, content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")
