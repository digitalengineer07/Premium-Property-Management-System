import os
import re

dash_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\dashboard_desktop.php'
appr_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

with open(dash_path, 'r', encoding='utf-8') as f:
    dash_lines = f.readlines()

header_actions = "".join(dash_lines[9:102])

# Add Apply button
apply_btn = """
                <button class="btn-primary" style="display: flex; align-items: center; gap: 8px; margin-right: 12px; background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer;" onclick="openPaymentModal(0, 'Advance / General Payment', 'general', 0, 'Advance/General')">
                    <i class='bx bx-plus'></i> Apply for Approval
                </button>
"""

header_actions = apply_btn + header_actions

new_top_header = f"""        <div class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
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
{header_actions}
            </div>
        </div>
"""

with open(appr_path, 'r', encoding='utf-8') as f:
    app_content = f.read()

# Replace existing top-header block
# It looks like:
#        <div class="top-header">
#            <div class="header-title">
#                <h1>Payment Approvals</h1>
#                <p>Track your submitted payment verifications</p>
#            </div>
#            
#            <button class="btn-primary" onclick="openPaymentModal(0, 'Advance / General Payment', 'general', 0, 'Advance/General')">
#                <i class='bx bx-plus'></i> Apply for Approval
#            </button>
#        </div>

pattern = r'<div class="top-header">.*?Apply for Approval\s*</button>\s*</div>'
app_content = re.sub(pattern, new_top_header, app_content, flags=re.IGNORECASE | re.DOTALL)

with open(appr_path, 'w', encoding='utf-8') as f:
    f.write(app_content)
print("Updated top header in approvals page")
