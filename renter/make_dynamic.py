import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update SELECT query
old_query = r"""\$stmt = mysqli_prepare\(\$conn, "SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date, electricity_document FROM users WHERE id = \?"\);"""
new_query = """$stmt = mysqli_prepare($conn, "SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date, electricity_document, dob, gender, address, emergency_contact_name, emergency_contact_relation, emergency_contact_phone, emergency_contact_address, block, floor, parking, joining_date, fixed_rent, advance_payment FROM users WHERE id = ?");"""
content = re.sub(old_query, new_query, content)

# 2. Update Basic Information block
basic_old = r"""<div class="panel">\s*<div class="panel-header">\s*<h3>Basic Information</h3>.*?<div class="info-value" style="text-align: right; line-height: 1\.4;">Madhav Kunj Apartments, Block A,<br>Room <\?php echo htmlspecialchars\(\$user\['room_no'\]\); \?>, City Center, Patna - 800001</div></div>\s*</div>\s*</div>"""
basic_new = """<div class="panel">
                  <div class="panel-header">
                      <h3>Basic Information</h3>
                      <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Full Name</div><div class="info-value"><?php echo htmlspecialchars($user['name'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-envelope'></i> Email Address</div><div class="info-value"><?php echo htmlspecialchars($user['email'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone-call'></i> Alternate Number</div><div class="info-value"><?php echo htmlspecialchars($user['whatsapp'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-calendar'></i> Date of Birth</div><div class="info-value"><?php echo $user['dob'] ? date('d M Y', strtotime($user['dob'])) : '-'; ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-male'></i> Gender</div><div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['address'] ?: '-'); ?></div></div>
                  </div>
              </div>"""
content = re.sub(basic_old, basic_new, content, flags=re.DOTALL)

# 3. Update Residence Details block
res_old = r"""<div class="panel">\s*<div class="panel-header">\s*<h3>Residence Details</h3>.*?<div class="residence-info"><h4>Parking</h4><p>Car Parking \(Slot 42\)</p></div>\s*</div>\s*</div>\s*</div>"""
res_new = """<div class="panel">
                  <div class="panel-header">
                      <h3>Residence Details</h3>
                      <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="residence-grid">
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-home-alt-2'></i></div>
                          <div class="residence-info"><h4>Flat / Room No.</h4><p><?php echo htmlspecialchars($user['room_no'] ?: '-'); ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-building-house'></i></div>
                          <div class="residence-info"><h4>Block / Building</h4><p><?php echo htmlspecialchars($user['block'] ?: '-'); ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-map-alt'></i></div>
                          <div class="residence-info"><h4>Property Name</h4><p><?php echo HOUSE_NAME; ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-layer'></i></div>
                          <div class="residence-info"><h4>Floor</h4><p><?php echo htmlspecialchars($user['floor'] ?: '-'); ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-calendar-event'></i></div>
                          <div class="residence-info"><h4>Move-in Date</h4><p><?php echo $user['joining_date'] ? date('d M Y', strtotime($user['joining_date'])) : '-'; ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-rupee'></i></div>
                          <div class="residence-info"><h4>Monthly Rent</h4><p>₹<?php echo $user['fixed_rent'] ? number_format($user['fixed_rent'], 2) : '-'; ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-check-shield'></i></div>
                          <div class="residence-info"><h4>Security Deposit</h4><p>₹<?php echo $user['advance_payment'] ? number_format($user['advance_payment'], 2) : '-'; ?></p></div>
                      </div>
                      <div class="residence-item">
                          <div class="residence-icon"><i class='bx bx-car'></i></div>
                          <div class="residence-info"><h4>Parking</h4><p><?php echo htmlspecialchars($user['parking'] ?: '-'); ?></p></div>
                      </div>
                  </div>
              </div>"""
content = re.sub(res_old, res_new, content, flags=re.DOTALL)

# 4. Update Emergency Contact block
emg_old = r"""<div class="panel">\s*<div class="panel-header">\s*<h3><i class='bx bx-user-circle'></i> Emergency Contact</h3>.*?<div class="info-value">Patna, Bihar - 800001</div></div>\s*</div>\s*</div>"""
emg_new = """<div class="panel">
                  <div class="panel-header">
                      <h3><i class='bx bx-user-circle'></i> Emergency Contact</h3>
                      <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Contact Name</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_name'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-group'></i> Relationship</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_relation'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['emergency_contact_address'] ?: '-'); ?></div></div>
                  </div>
              </div>"""
content = re.sub(emg_old, emg_new, content, flags=re.DOTALL)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated profile.php dynamically!")
