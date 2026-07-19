import os
import re

filepath = r'c:\xampp\htdocs\renter-system\admin\bill-generator.php'

with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Target 1
content = re.sub(
    r'<label>Past Dues</label>',
    r'<label>Advance Applied (Auto)</label>',
    content
)

content = re.sub(
    r'<input type="number" id="dues" placeholder="0" oninput="calculateBill\(\)" style="padding-left: 40px;">',
    r'<input type="number" id="dues" placeholder="0" readonly style="padding-left: 40px; background-color: #f1f5f9; cursor: not-allowed; color: #10B981; font-weight: 700;">',
    content
)

# Target 3
content = re.sub(
    r'<span style="color: rgba\(255,255,255,0\.8\);">Arrears/Dues</span>',
    r'<span style="color: rgba(255,255,255,0.8);">Advance Applied</span>',
    content
)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated bill-generator.php")
