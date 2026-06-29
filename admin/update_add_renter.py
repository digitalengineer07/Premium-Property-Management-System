import re

with open('admin/add-renter.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Update DB INSERT
old_insert = r"""\$stmt = mysqli_prepare\(\$conn, "INSERT INTO users \(username, password, name, room_no, phone, email, base_reading, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date\) VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, NOW\(\), \?, \?, NOW\(\), \?, 1, \?\)"\);
\s*\$admin_id = \$_SESSION\['admin_id'\] \?\? 1; // Basic fallback if admin_id is not set
\s*mysqli_stmt_bind_param\(\$stmt, "ssssssidddis", \$username, \$hashed, \$name, \$room_no, \$phone, \$email, \$base_reading, \$advance_payment, \$fixed_rent, \$fixed_maintenance, \$admin_id, \$joining_date\);"""

new_insert = """$block = trim($_POST['block'] ?? '');
                $floor = trim($_POST['floor'] ?? '');
                $parking = trim($_POST['parking'] ?? '');
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, name, room_no, phone, email, base_reading, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date, block, floor, parking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, 1, ?, ?, ?, ?)");
                $admin_id = $_SESSION['admin_id'] ?? 1; // Basic fallback if admin_id is not set
                mysqli_stmt_bind_param($stmt, "ssssssidddissss", $username, $hashed, $name, $room_no, $phone, $email, $base_reading, $advance_payment, $fixed_rent, $fixed_maintenance, $admin_id, $joining_date, $block, $floor, $parking);"""
content = re.sub(old_insert, new_insert, content)

# Add HTML fields in Personal Profile section
old_html = r"""<div class="form-group">
\s*<label>Room No / Floor</label>
\s*<input type="text" id="roomNoInput" name="room_no" placeholder="e\.g\. 104, 2nd Floor">
\s*</div>"""

new_html = """<div class="form-group">
                                <label>Flat / Room No.</label>
                                <input type="text" id="roomNoInput" name="room_no" placeholder="e.g. 104">
                            </div>
                            <div class="form-group">
                                <label>Block / Building</label>
                                <input type="text" name="block" placeholder="e.g. Block A">
                            </div>
                            <div class="form-group">
                                <label>Floor</label>
                                <input type="text" name="floor" placeholder="e.g. 2nd Floor">
                            </div>
                            <div class="form-group">
                                <label>Parking Slot</label>
                                <input type="text" name="parking" placeholder="e.g. A-15">
                            </div>"""
content = re.sub(old_html, new_html, content)

with open('admin/add-renter.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated admin/add-renter.php")
