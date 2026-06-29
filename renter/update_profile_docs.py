import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update SELECT query
old_query = r"""SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date FROM users WHERE id = \?"""
new_query = """SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date, electricity_document FROM users WHERE id = ?"""
content = re.sub(old_query, new_query, content)

# 2. Update panel header button
old_header = r"""<button class="btn-outline" style="padding: 6px 12px;">View All</button>"""
new_header = """<button class="btn-outline" style="padding: 6px 12px;" onclick="window.location.href='documents.php'">View All</button>"""
content = re.sub(old_header, new_header, content)

# 3. Replace the .doc-list and everything up to the Cropper Modal
pattern = r"""<div class="doc-list">.*?<!-- Cropper Modal -->"""

replacement = """<div class="doc-list">
                    <!-- Aadhar Card -->
                    <div class="doc-item">
                        <div class="doc-icon green"><i class='bx bx-id-card'></i></div>
                        <div class="doc-info"><h4>Aadhar Card</h4></div>
                        <div class="status-pill <?php echo $user['aadhaar_file'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['aadhaar_file'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['aadhaar_file'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['aadhaar_file']): ?>
                                <a href="../<?php echo htmlspecialchars($user['aadhaar_file']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php else: ?>
                                <a href="documents.php" title="Upload"><i class='bx bx-upload'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                    
                    <!-- Agreement Copy -->
                    <div class="doc-item">
                        <div class="doc-icon purple"><i class='bx bx-file-blank'></i></div>
                        <div class="doc-info"><h4>Agreement Copy</h4></div>
                        <div class="status-pill <?php echo $user['agreement_document'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['agreement_document'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['agreement_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['agreement_document']): ?>
                                <a href="../<?php echo htmlspecialchars($user['agreement_document']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>

                    <!-- Electricity Copy -->
                    <div class="doc-item">
                        <div class="doc-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class='bx bx-bolt-circle'></i></div>
                        <div class="doc-info"><h4>Electricity Copy</h4></div>
                        <div class="status-pill <?php echo $user['electricity_document'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['electricity_document'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['electricity_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['electricity_document']): ?>
                                <a href="../<?php echo htmlspecialchars($user['electricity_document']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropper Modal -->"""

content = re.sub(pattern, replacement, content, flags=re.DOTALL)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated profile documents!")
