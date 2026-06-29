import re
import glob

new_avatar_inner = """<?php if (!empty($user['profile_pic'])): ?>
    <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <?php echo strtoupper(substr($display_name ?? $user['name'] ?? 'User', 0, 2)); ?>
<?php endif; ?>"""

for filepath in glob.glob('renter/*.php'):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find <div class="user-avatar">...</div>
    old_avatar_pattern = r'<div class="user-avatar">.*?</div>'
    
    def replacer(match):
        return f'<div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple);">{new_avatar_inner}</div>'
    
    new_content = re.sub(old_avatar_pattern, replacer, content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")
