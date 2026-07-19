import os

filepath = r'c:\xampp\htdocs\renter-system\admin\add-renter.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """                $advance_payment = (float)($_POST['advance_payment'] ?? 0);"""
replacement1 = """                $advance_payment = (float)($_POST['advance_payment'] ?? 0);
                $security_deposit = (float)($_POST['security_deposit'] ?? 0);"""

target2 = """                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, name, room_no, phone, email, base_reading, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date, block, floor, parking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, 1, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssidddissss", $username, $hashed, $name, $room_no, $phone, $email, $base_reading, $advance_payment, $fixed_rent, $fixed_maintenance, $admin_id, $joining_date, $block, $floor, $parking);"""

replacement2 = """                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, name, room_no, phone, email, base_reading, advance_payment, security_deposit, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date, block, floor, parking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, 1, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssiddidddissss", $username, $hashed, $name, $room_no, $phone, $email, $base_reading, $advance_payment, $security_deposit, $fixed_rent, $fixed_maintenance, $admin_id, $joining_date, $block, $floor, $parking);"""

target3 = """                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 600; color: #1E293B;">Advance Wallet (â‚¹)</label>
                                <div style="position: relative; height: 48px;">
                                    <i class='bx bx-wallet' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 20px;"></i>
                                    <input type="number" step="0.01" name="advance_payment" value="0" style="padding-left: 40px; height: 100%; border-radius: 12px; border: 1px solid var(--border);" placeholder="0">
                                </div>
                            </div>"""

replacement3 = """                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 600; color: #1E293B;">Advance Wallet (â‚¹)</label>
                                <div style="position: relative; height: 48px;">
                                    <i class='bx bx-wallet' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 20px;"></i>
                                    <input type="number" step="0.01" name="advance_payment" value="0" style="padding-left: 40px; height: 100%; border-radius: 12px; border: 1px solid var(--border);" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 600; color: #1E293B;">Security Deposit (â‚¹)</label>
                                <div style="position: relative; height: 48px;">
                                    <i class='bx bx-lock-alt' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 20px;"></i>
                                    <input type="number" step="0.01" name="security_deposit" value="0" style="padding-left: 40px; height: 100%; border-radius: 12px; border: 1px solid var(--border);" placeholder="0">
                                </div>
                            </div>"""

target4 = """    let amount = document.getElementsByName('advance_payment')[0].value;
    if (amount === '' || parseFloat(amount) < 0) {
        showError('Advance Payment must be 0 or greater');
        isValid = false;
    }"""

replacement4 = """    let amount = document.getElementsByName('advance_payment')[0].value;
    if (amount === '' || parseFloat(amount) < 0) {
        showError('Advance Payment must be 0 or greater');
        isValid = false;
    }
    
    let secAmount = document.getElementsByName('security_deposit')[0].value;
    if (secAmount === '' || parseFloat(secAmount) < 0) {
        showError('Security Deposit must be 0 or greater');
        isValid = false;
    }"""

if target1 in content and target2 in content and target3 in content and target4 in content:
    content = content.replace(target1, replacement1)
    content = content.replace(target2, replacement2)
    content = content.replace(target3, replacement3)
    content = content.replace(target4, replacement4)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated admin/add-renter.php")
else:
    print("Target not found in admin/add-renter.php")
