lines = []
with open('profile.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_css = """        .header-greeting { display: flex; align-items: center; gap: 16px; }
        .header-icon-wrapper { width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0; }
        
        .header-title-area h1 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .header-title-area p { margin: 0; font-size: 14px; color: var(--text-gray); font-weight: 500; }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .header-actions .icon-btn .badge { position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; }
        
        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }

        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: #f8fafc; transform: translateY(-1px); }\n"""

start_idx = -1
end_idx = -1
for i, line in enumerate(lines):
    if '.header-title-area h1 {' in line and start_idx == -1:
        start_idx = i
    if '.btn-outline:hover {' in line:
        end_idx = i

if start_idx != -1 and end_idx != -1:
    lines[start_idx:end_idx+1] = [new_css]
    with open('profile.php', 'w', encoding='utf-8') as f:
        f.writelines(lines)
    print("Replaced CSS successfully!")
else:
    print(f"Could not find indices! start_idx: {start_idx}, end_idx: {end_idx}")
