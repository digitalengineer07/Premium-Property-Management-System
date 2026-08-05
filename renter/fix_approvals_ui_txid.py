import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

target = """                            <?php if (!empty($ap['transaction_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; font-size: 13px; color: var(--text-gray);">
                                    <?php echo htmlspecialchars($ap['transaction_id']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">N/A</span>
                            <?php endif; ?>"""

replacement = """                            <?php if (!empty($ap['transaction_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; font-size: 13px; color: var(--text-gray);">
                                    <?php echo htmlspecialchars($ap['transaction_id']); ?>
                                </span>
                            <?php elseif (!empty($ap['sys_tx_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; font-size: 13px; color: var(--text-gray);">
                                    <?php echo htmlspecialchars($ap['sys_tx_id']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">N/A</span>
                            <?php endif; ?>"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")


filepath_mobile = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

target_m = """                        <div class="ac-value" style="font-family: monospace;">
                            <?php echo !empty($ap['transaction_id']) ? htmlspecialchars($ap['transaction_id']) : 'N/A'; ?>
                        </div>"""

replacement_m = """                        <div class="ac-value" style="font-family: monospace;">
                            <?php 
                                if (!empty($ap['transaction_id'])) echo htmlspecialchars($ap['transaction_id']);
                                else if (!empty($ap['sys_tx_id'])) echo htmlspecialchars($ap['sys_tx_id']);
                                else echo 'N/A'; 
                            ?>
                        </div>"""

if os.path.exists(filepath_mobile):
    with open(filepath_mobile, 'r', encoding='utf-8') as f:
        content_m = f.read()
    
    if target_m in content_m:
        content_m = content_m.replace(target_m, replacement_m)
        with open(filepath_mobile, 'w', encoding='utf-8') as f:
            f.write(content_m)
        print(f"Updated {filepath_mobile}")
    else:
        print(f"Target not found in {filepath_mobile}")
else:
    print(f"File not found: {filepath_mobile}")
