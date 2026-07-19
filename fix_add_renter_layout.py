import os
filepath = r'c:\xampp\htdocs\renter-system\admin\add-renter.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Financial Initial Setup</h4>
                        <div class="form-group">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 600; color: #1E293B;">Advance Wallet (₹)</label>"""

replacement = """                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Financial Initial Setup</h4>
                        <div class="form-group">
                            <div style="display: flex; gap: 10px; align-items: stretch; width: 100%;">
                                <div style="flex: 1;">
                                    <label class="form-label" style="font-weight: 600; color: #1E293B;">Advance Wallet (₹)</label>"""

content = content.replace(target, replacement)

target2 = """                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 600; color: #1E293B;">Security Deposit (₹)</label>"""
replacement2 = """                                <div style="flex: 1;">
                                    <label class="form-label" style="font-weight: 600; color: #1E293B;">Security Deposit (₹)</label>"""
content = content.replace(target2, replacement2)

target3 = """                            <button type="button" class="btn-outline" onclick="generateAdvanceQR()" style="padding: 0 16px; border-radius: 12px; height: 48px; flex-shrink: 0;"><i class='bx bx-qr-scan'></i> QR</button>
                            </div>"""
replacement3 = """                                <div style="display: flex; align-items: flex-end;">
                                    <button type="button" class="btn-outline" onclick="generateAdvanceQR()" style="padding: 0 16px; border-radius: 12px; height: 48px; flex-shrink: 0;"><i class='bx bx-qr-scan'></i> QR</button>
                                </div>
                            </div>"""
content = content.replace(target3, replacement3)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Fixed add-renter.php layout")
