import os
import re

css_to_add = """        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }"""

html_to_add = """            <div class="header-greeting">
                <h1>Hello, <?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?> 👋</h1>
                <p>Welcome back! You're assigned to <span>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></span></p>
            </div>"""

files_to_update = [
    'dashboard.php',
    'my-bills.php',
    'electricity-record.php',
    'queries.php',
    'notices.php',
    'documents.php',
    'payment-history.php',
    'profile.php',
    'change-password.php',
    'about-dev.php'
]

for file in files_to_update:
    if not os.path.exists(file):
        continue
        
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # 1. Update CSS
    if '.header-greeting h1' not in content:
        # Find .top-header { ... } and insert after it
        match = re.search(r'\.top-header\s*\{[^}]*\}', content)
        if match:
            pos = match.end()
            content = content[:pos] + '\n' + css_to_add + content[pos:]
            
    # 2. Update HTML
    # We want to find <header class="top-header" ... >
    # and <div class="header-actions">
    # and replace everything in between.
    
    header_pattern = re.compile(r'(<header\s+class="top-header"[^>]*>)(.*?)(<div\s+class="header-actions">)', re.DOTALL)
    
    match = header_pattern.search(content)
    if match:
        content = content[:match.start(2)] + '\n' + html_to_add + '\n            ' + content[match.start(3):]
        
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated {file}")
