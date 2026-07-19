import os

filepath = r'c:\xampp\htdocs\renter-system\admin\bill-generator.php'

with open(filepath, 'r') as f:
    content = f.read()

# Target 1: Change label and make dues readonly
target1 = """                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Past Dues</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                    <input type="number" id="dues" placeholder="0" oninput="calculateBill()" style="padding-left: 40px;">
                                </div>
                            </div>"""

replacement1 = """                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Advance Applied (Auto)</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                    <input type="number" id="dues" placeholder="0" readonly style="padding-left: 40px; background-color: #f1f5f9; cursor: not-allowed; color: #10B981; font-weight: 700;">
                                </div>
                            </div>"""


# Target 2: Change auto-fill logic to ONLY allow negative values (advances)
target2 = """                // Auto-fill Dues field (Dues = -Adjustment)
                // If adj is -2000 (Remaining), dues = 2000
                // If adj is 2000 (Extra), dues = -2000
                document.getElementById('dues').value = adj === 0 ? '' : -adj;"""

replacement2 = """                // Auto-fill Advance Applied (dues = -Adjustment, but only if adj is > 0 meaning they have advance)
                document.getElementById('dues').value = adj > 0 ? -adj : '';"""

# Target 3: Update label in the receipt preview
target3 = """<span style="color: rgba(255,255,255,0.8);">Arrears/Dues</span><strong id="calcDues">₹0</strong>"""
replacement3 = """<span style="color: rgba(255,255,255,0.8);">Advance Applied</span><strong id="calcDues">₹0</strong>"""


if target1 in content:
    content = content.replace(target1, replacement1)
else:
    print("Target 1 not found")

if target2 in content:
    content = content.replace(target2, replacement2)
else:
    print("Target 2 not found")
    
if target3 in content:
    content = content.replace(target3, replacement3)
else:
    print("Target 3 not found")

with open(filepath, 'w') as f:
    f.write(content)
print("Updated bill-generator.php")
