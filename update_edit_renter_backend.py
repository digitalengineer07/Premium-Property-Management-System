import os

filepath = r'c:\xampp\htdocs\renter-system\admin\edit-renter.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target1 = """        $advance_payment = (float)($_POST['advance_payment'] ?? 0);
        $fixed_rent = (float)($_POST['fixed_rent'] ?? 0);"""

replacement1 = """        $advance_payment = (float)($_POST['advance_payment'] ?? 0);
        $security_deposit = (float)($_POST['security_deposit'] ?? 0);
        $fixed_rent = (float)($_POST['fixed_rent'] ?? 0);"""

target2 = """            $types = "sssssssddddssss";
            $params = [$name, $room_no, $phone, $email, $whatsapp, $about, $joining_date, $advance_payment, $advance_payment, $fixed_rent, $fixed_maintenance, $emg_name, $emg_rel, $emg_phone, $emg_addr];"""

replacement2 = """            $types = "sssssssdddddssss";
            $params = [$name, $room_no, $phone, $email, $whatsapp, $about, $joining_date, $advance_payment, $advance_payment, $security_deposit, $fixed_rent, $fixed_maintenance, $emg_name, $emg_rel, $emg_phone, $emg_addr];"""

target3 = """            // If advance_payment is changed, update advance_updated_at
            $sql = "UPDATE users SET name=?, room_no=?, phone=?, email=?, whatsapp=?, about=?, joining_date=?, advance_updated_at = IF(advance_payment != ?, NOW(), advance_updated_at), advance_payment=?, fixed_rent=?, fixed_maintenance=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? {$aadhaar_update} {$agreement_update}";"""

replacement3 = """            // If advance_payment is changed, update advance_updated_at
            $sql = "UPDATE users SET name=?, room_no=?, phone=?, email=?, whatsapp=?, about=?, joining_date=?, advance_updated_at = IF(advance_payment != ?, NOW(), advance_updated_at), advance_payment=?, security_deposit=?, fixed_rent=?, fixed_maintenance=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? {$aadhaar_update} {$agreement_update}";"""

if target1 in content and target2 in content and target3 in content:
    content = content.replace(target1, replacement1)
    content = content.replace(target2, replacement2)
    content = content.replace(target3, replacement3)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated edit-renter.php backend")
else:
    print("Backend Targets not found")
