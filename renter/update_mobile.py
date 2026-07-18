import re
import os

path = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Inject Bill Month into the ac-details flex container
    old_html = """                <div class="ac-details">
                    <div>
                        <div class="ac-label">Method</div>"""
    
    new_html = """                <div class="ac-details">
                    <div>
                        <div class="ac-label">Method</div>"""
    
    # We will inject the Bill Month column in the middle of ac-details
    # Let's find the middle part
    
    pattern = r'(<div class="ac-details">\s*<div>\s*<div class="ac-label">Method</div>\s*<div class="ac-value" style="display: flex; align-items: center; gap: 2px;">\s*<\?php if \(strtolower\(\$ap\[\'payment_method\'\]\) === \'upi\'\): \?>\s*<img src="https://upload\.wikimedia\.org/wikipedia/commons/e/e1/UPI-Logo-vector\.svg" alt="UPI" style="height: 12px;"> UPI\s*<\?php else: \?>\s*<i class=\'bx bx-money\' style="color: #10B981;"></i> Cash\s*<\?php endif; \?>\s*</div>\s*</div>)'
    
    replacement = r'\1\n                    <div style="text-align: center;">\n                        <div class="ac-label">Bill Month</div>\n                        <div class="ac-value"><?php echo !empty($ap[\'month\']) ? htmlspecialchars($ap[\'month\']) : \'-\'; ?></div>\n                    </div>'
    
    content = re.sub(pattern, replacement, content)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added Bill Month to mobile view.")
