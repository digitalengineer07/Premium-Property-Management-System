import os

filepath = r'c:\xampp\htdocs\renter-system\admin\view-renter.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                      <div style="text-align: right;">
                          <div style="font-weight: 800; font-size: 22px; color: #10B981;">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></div>
                      </div>
                  </div>

                    <!-- Advance Wallet -->
                    <div style="background: white; border-radius: 20px; padding: 24px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #E2E8F0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 28px; flex-shrink: 0;"><i class='bx bx-wallet'></i></div>
                            <div>
                                <div style="font-weight: 800; color: #0F172A; font-size: 17px; margin-bottom: 6px;">Advance Wallet</div>
                                <div style="color: #64748B; font-size: 13px; font-weight: 500;">Can be used for bills</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 22px; color: #10B981;">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></div>
                        </div>
                    </div>"""

replacement = """                      <div style="text-align: right;">
                          <div style="font-weight: 800; font-size: 22px; color: #10B981;">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></div>
                      </div>
                  </div>"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed admin/view-renter.php")
else:
    print("Target not found")
