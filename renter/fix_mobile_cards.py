import os
import re

path = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Update CSS
    old_css = """        .ac-details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }"""
        
    new_css = """        .ac-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 8px;
            font-size: 13px;
            align-items: start;
        }
        .ac-details > div { display: flex; flex-direction: column; }
        .ac-details > div:nth-child(2) { text-align: center; align-items: center; }
        .ac-details > div:nth-child(3) { text-align: right; align-items: flex-end; }
        .ac-value { word-break: break-all; }"""
        
    content = content.replace(old_css, new_css)
    
    # Update HTML
    old_html = """                        <div class="ac-value" style="display: flex; align-items: center; gap: 2px;">
                            <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px; width: 40px; object-fit: contain;"> UPI
                            <?php else: ?>
                                <i class='bx bx-money' style="color: #10B981;"></i> Cash
                            <?php endif; ?>
                        </div>"""
                        
    new_html = """                        <div class="ac-value" style="display: flex; align-items: center; gap: 4px;">
                            <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                <span style="background: rgba(98, 75, 255, 0.1); color: #624BFF; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; border: 1px solid rgba(98, 75, 255, 0.2);">UPI</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.2);"><i class='bx bx-money'></i> Cash</span>
                            <?php endif; ?>
                        </div>"""

    content = content.replace(old_html, new_html)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print("Mobile card layout fixed!")
