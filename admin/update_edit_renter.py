import re

with open('edit-renter.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update SELECT query
old_select = r"SELECT name, room_no, phone, email, whatsapp, joining_date, fixed_rent, fixed_maintenance, advance_payment, base_reading, aadhaar_file, agreement_document"
new_select = r"SELECT name, room_no, phone, email, whatsapp, joining_date, fixed_rent, fixed_maintenance, advance_payment, base_reading, aadhaar_file, agreement_document, block, floor, parking"
content = content.replace(old_select, new_select)

# 2. Update POST logic
old_post = r"""        \$joining_date = \$_POST\['joining_date'\] \?\? null;
        if\(empty\(\$joining_date\)\) \$joining_date = null;

        \$stmt = mysqli_prepare\(\$conn, "UPDATE users SET name = \?, room_no = \?, phone = \?, email = \?, whatsapp = \?, joining_date = \? WHERE id = \?"\);
        mysqli_stmt_bind_param\(\$stmt, "ssssssi", \$name, \$room_no, \$phone, \$email, \$whatsapp, \$joining_date, \$edit_id\);"""

new_post = """        $joining_date = $_POST['joining_date'] ?? null;
        if(empty($joining_date)) $joining_date = null;
        
        $block = trim($_POST['block'] ?? '');
        $floor = trim($_POST['floor'] ?? '');
        $parking = trim($_POST['parking'] ?? '');

        $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, room_no = ?, phone = ?, email = ?, whatsapp = ?, joining_date = ?, block = ?, floor = ?, parking = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssssssssi", $name, $room_no, $phone, $email, $whatsapp, $joining_date, $block, $floor, $parking, $edit_id);"""
content = re.sub(old_post, new_post, content)

# 3. Add HTML inputs
old_html = r"""<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
\s*<div>
\s*<label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Room / Flat No\.</label>
\s*<input type="text" name="room_no" value="<\?php echo htmlspecialchars\(\$user\['room_no'\]\); \?>" style=".*?">
\s*</div>
\s*<div>
\s*<label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Joining Date</label>
\s*<input type="date" name="joining_date" value="<\?php echo htmlspecialchars\(\$user\['joining_date'\] \?\? ''\); \?>" style=".*?">
\s*</div>
\s*</div>"""

new_html = """<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Flat / Room No.</label>
                                <input type="text" name="room_no" value="<?php echo htmlspecialchars($user['room_no'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Joining Date</label>
                                <input type="date" name="joining_date" value="<?php echo htmlspecialchars($user['joining_date'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Block / Building</label>
                                <input type="text" name="block" value="<?php echo htmlspecialchars($user['block'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Floor</label>
                                <input type="text" name="floor" value="<?php echo htmlspecialchars($user['floor'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Parking Slot</label>
                                <input type="text" name="parking" value="<?php echo htmlspecialchars($user['parking'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                            </div>
                        </div>"""

content = re.sub(old_html, new_html, content)

with open('edit-renter.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated edit-renter.php")
