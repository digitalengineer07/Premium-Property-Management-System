import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update the PHP DB update logic
old_db_logic = r"""        // Update DB
        if \(empty\(\$errmsg\)\) \{
            \$email = trim\(\$_POST\['email'\] \?\? ''\);
            
            if \(\$profileUploadedPath && \$aadhaarPath\) \{.*?\$success = "Profile updated\.";
        \}"""

new_db_logic = """        // Update DB
        if (empty($errmsg)) {
            $email = trim($_POST['email'] ?? '');
            $dob = empty($_POST['dob']) ? null : $_POST['dob'];
            $gender = empty($_POST['gender']) ? null : $_POST['gender'];
            $address = empty($_POST['address']) ? null : $_POST['address'];
            $emg_name = empty($_POST['emergency_contact_name']) ? null : $_POST['emergency_contact_name'];
            $emg_rel = empty($_POST['emergency_contact_relation']) ? null : $_POST['emergency_contact_relation'];
            $emg_phone = empty($_POST['emergency_contact_phone']) ? null : $_POST['emergency_contact_phone'];
            $emg_addr = empty($_POST['emergency_contact_address']) ? null : $_POST['emergency_contact_address'];

            if ($profileUploadedPath && $aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=?, aadhaar_file=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $aadhaarPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } elseif ($profileUploadedPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } elseif ($aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, aadhaar_file=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $aadhaarPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            logAction($conn, "renter", $user_id, "Updated profile and files");
            $success = "Profile updated.";
        }"""
content = re.sub(old_db_logic, new_db_logic, content, flags=re.DOTALL)

# 2. Update the onclick handlers for the edit buttons
content = content.replace("""onclick="alert('Edit form would open here.')">""", """onclick="document.getElementById('editProfileModal').style.display='flex'">""")

# 3. Add the modal before the cropper modal
modal_html = """<!-- Edit Profile Modal -->
    <div id="editProfileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px);">
        <div style="background: var(--white); padding: 32px; border-radius: 24px; max-width: 600px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-dark);">Edit Profile Details</h3>
                <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-gray);"><i class='bx bx-x'></i></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <input type="hidden" name="save_profile" value="1">
                <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($user['room_no'] ?? ''); ?>">
                <input type="hidden" name="about" value="<?php echo htmlspecialchars($user['about'] ?? ''); ?>">
                
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--primary-purple);"><i class='bx bx-user'></i> Basic Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Alternate Number</label>
                        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Gender</label>
                        <select name="gender" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="width: 100%; height: 1px; background: var(--border); margin: 24px 0;"></div>

                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--primary-purple);"><i class='bx bx-plus-medical'></i> Emergency Contact</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Relationship</label>
                        <input type="text" name="emergency_contact_relation" value="<?php echo htmlspecialchars($user['emergency_contact_relation'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Phone Number</label>
                        <input type="text" name="emergency_contact_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Contact Address</label>
                        <input type="text" name="emergency_contact_address" value="<?php echo htmlspecialchars($user['emergency_contact_address'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); font-size: 14px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='none'" style="border: none;">Cancel</button>
                    <button type="submit" class="btn-primary" style="width: auto; padding: 12px 32px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cropper Modal -->"""
content = content.replace("<!-- Cropper Modal -->", modal_html)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Added Edit Modal!")
