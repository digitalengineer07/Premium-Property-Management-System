import os

filepath = r'c:\xampp\htdocs\renter-system\admin\edit-renter.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                                  <div class="form-group" style="margin: 0;">
                                      <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Deposit / Adv.</label>
                                      <div style="position: relative; display: flex; align-items: center;">
                                          <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                          <input type="number" step="0.01" name="advance_payment" value="<?php echo number_format($user['advance_payment'], 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                      </div>
                                  </div>"""

replacement = """                                  <div class="form-group" style="margin: 0;">
                                      <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Advance Wallet</label>
                                      <div style="position: relative; display: flex; align-items: center;">
                                          <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                          <input type="number" step="0.01" name="advance_payment" value="<?php echo number_format($user['advance_payment'], 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                      </div>
                                  </div>
                                  <div class="form-group" style="margin: 0;">
                                      <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Security Dep.</label>
                                      <div style="position: relative; display: flex; align-items: center;">
                                          <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                          <input type="number" step="0.01" name="security_deposit" value="<?php echo number_format($user['security_deposit'] ?? 0, 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                      </div>
                                  </div>"""

if target in content:
    content = content.replace(target, replacement)
    
    # Also adjust grid columns if needed, wait, the parent is a grid. Let's see the parent.
    # We will just write it first.
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated edit-renter.php UI")
else:
    print("UI Target not found")
