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
    old_avatar_pattern = r'<div class="user-avatar">([^<]*)</div>'
    
    def replacer(match):
        return f'<div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple);">{new_avatar_inner}</div>'
    
    new_content = re.sub(old_avatar_pattern, replacer, content)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")

# Now for admin side
for filepath in glob.glob('admin/header.php'):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # In admin, it's <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="avatar" onerror="...">
    old_admin_avatar = r'<img src="\.\./assets/img/admin-avatar\.jpg" alt="Admin" class="avatar" onerror="[^"]*">'
    new_admin_avatar = r"""<?php 
                    // Let's just use a clean admin avatar with no error 
                ?>
                <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="avatar" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=Admin+User&background=624BFF&color=fff'">"""
    
    # Wait, the user said "Also shows the picture on admin panel". 
    # If they uploaded a profile picture for the admin? Admin doesn't have a profile picture in the database (admin table only has id, username, password).
    # Oh! They meant show the RENTER's picture in the admin panel when viewing the renter! (like manage-renters.php or view-renter.php).
    pass

print("Done processing renter pages")
