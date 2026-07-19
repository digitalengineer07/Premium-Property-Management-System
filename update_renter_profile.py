import os

filepath = r'c:\xampp\htdocs\renter-system\renter\profile.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                      <div style="grid-column: 1 / -1;">
                          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;"><i class='bx bx-check-shield' style="font-size: 16px; vertical-align: middle;"></i> Security Deposit (₹)</label>
                          <input type="text" name="advance_payment" value="<?php echo number_format($user['advance_payment'] ?? 0); ?>" style="width: 100%; padding: 10px 14px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 13px; box-sizing: border-box; cursor: not-allowed; color: var(--text-gray);" readonly>"""

replacement = """                      <div>
                          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;"><i class='bx bx-wallet' style="font-size: 16px; vertical-align: middle;"></i> Advance Wallet (₹)</label>
                          <input type="text" name="advance_payment" value="<?php echo number_format($user['advance_payment'] ?? 0); ?>" style="width: 100%; padding: 10px 14px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 13px; box-sizing: border-box; cursor: not-allowed; color: var(--text-gray);" readonly>
                      </div>
                      <div>
                          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;"><i class='bx bx-check-shield' style="font-size: 16px; vertical-align: middle;"></i> Security Deposit (₹)</label>
                          <input type="text" name="security_deposit" value="<?php echo number_format($user['security_deposit'] ?? 0); ?>" style="width: 100%; padding: 10px 14px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 13px; box-sizing: border-box; cursor: not-allowed; color: var(--text-gray);" readonly>"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed renter/profile.php")
else:
    print("Target not found")
