import re
import os

dashboard_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\dashboard_desktop.php'
approvals_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

with open(dashboard_path, 'r', encoding='utf-8') as f:
    dash_content = f.read()

# Extract header-actions from dashboard_desktop.php
actions_match = re.search(r'<div class="header-actions">(.*?)</div>\s*</header>', dash_content, flags=re.IGNORECASE | re.DOTALL)
if actions_match:
    header_actions_html = actions_match.group(1)
    
    # We will inject the Apply for approval button at the start of header_actions_html
    apply_btn = """
                <button class="btn btn-primary" onclick="openPaymentModal()" style="display: flex; align-items: center; gap: 8px; margin-right: 12px; background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer;">
                    <i class='bx bx-plus'></i> Apply for Approval
                </button>
"""
    header_actions_html = apply_btn + header_actions_html
    
    # The new top header
    new_header = f"""
        <header class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark);">Payment Approvals</h1>
                    <p style="margin: 4px 0 0 0; color: var(--text-gray); font-size: 14px;">Track your cash and UPI payment verifications</p>
                </div>
            </div>
            <div class="header-actions" style="display: flex; align-items: center; gap: 16px;">
{header_actions_html}
            </div>
        </header>
"""
    
    with open(approvals_path, 'r', encoding='utf-8') as f:
        app_content = f.read()
        
    # Replace the old page-header
    # The old page-header looks like <div class="page-header" ...> ... </div>
    app_content = re.sub(r'<div class="page-header".*?</button>\s*</div>', new_header, app_content, flags=re.IGNORECASE | re.DOTALL)
    
    with open(approvals_path, 'w', encoding='utf-8') as f:
        f.write(app_content)
    print("Successfully added top header to payment-approvals_desktop.php")
else:
    print("Could not find header-actions in dashboard_desktop.php")
