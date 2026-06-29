import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Locate boundaries
style_start = content.find('<style>')
script_start = content.find('<script>', style_start)

# We want to replace from <style> all the way to </main> just before <script>
main_end_match = list(re.finditer(r'</main>', content))
if not main_end_match:
    print("Could not find </main>")
    exit(1)
main_end = main_end_match[-1].end()

new_html = """<style>
        :root {
            --primary-purple: #624BFF;
            --bg-main: #F4F6F9;
            --white: #ffffff;
            --text-dark: #1E293B;
            --text-gray: #64748B;
            --border: #E2E8F0;
            --transition: all 0.3s ease;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        
        * { box-sizing: border-box; }
        
        body { margin: 0; background: var(--bg-main); font-family: 'Inter', sans-serif; }

        @media (max-width: 768px) {
            .header-renter { flex-direction: column !important; text-align: center; gap: 15px !important; margin-bottom: 24px !important; }
            .profile-grid { grid-template-columns: 1fr !important; }
            .residence-grid { grid-template-columns: 1fr !important; }
            .preferences-grid { grid-template-columns: 1fr !important; }
        }
        
        .header-renter {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 20px;
        }

        .header-title-area h1 { margin: 0; font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .header-title-area p { margin: 4px 0 0; font-size: 14px; color: var(--text-gray); font-weight: 500; }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-icon { 
            position: relative; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--white); border: 1px solid var(--border); color: var(--text-dark); cursor: pointer; transition: var(--transition);
        }
        .header-icon:hover { background: var(--bg-main); }
        .header-icon .badge { position: absolute; top: -2px; right: -2px; background: #EF4444; color: white; font-size: 10px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--white); }
        
        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); }
        .user-avatar-sm { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info-sm h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info-sm p { font-size: 12px; color: var(--text-gray); margin: 0; }

        .btn-outline { padding: 8px 16px; font-size: 13px; font-weight: 600; background: var(--white); border: 1px solid var(--border); border-radius: 8px; color: var(--primary-purple); cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-outline:hover { background: rgba(98, 75, 255, 0.05); border-color: var(--primary-purple); }

        .profile-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; align-items: start; }
        .grid-col-left { display: flex; flex-direction: column; gap: 24px; }
        .grid-col-right { display: flex; flex-direction: column; gap: 24px; }
        
        .panel { background: var(--white); border-radius: 16px; padding: 24px; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: var(--transition); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-header h3 { font-size: 16px; font-weight: 700; color: var(--text-dark); margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-header h3 i { font-size: 20px; color: var(--primary-purple); }

        /* Avatar Card overrides */
        .avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 40px 20px; }
        .avatar-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
        .avatar-huge { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid var(--white); box-shadow: 0 8px 20px rgba(0,0,0,0.08); background: var(--white); }
        .btn-edit-avatar { position: absolute; bottom: 5px; right: 5px; width: 36px; height: 36px; border-radius: 50%; background: var(--white); border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); color: var(--primary-purple); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: var(--transition); }
        .btn-edit-avatar:hover { transform: scale(1.1); }
        
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-label { display: flex; align-items: center; gap: 10px; color: var(--text-gray); font-size: 13px; font-weight: 500; }
        .info-label i { font-size: 18px; color: var(--text-gray); }
        .info-value { font-size: 14px; font-weight: 600; color: var(--text-dark); text-align: right; max-width: 60%; word-break: break-word; }

        /* Residence Grid */
        .residence-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .residence-item { display: flex; align-items: flex-start; gap: 12px; }
        .residence-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .residence-info h4 { margin: 0 0 4px 0; font-size: 12px; color: var(--text-gray); font-weight: 500; }
        .residence-info p { margin: 0; font-size: 14px; font-weight: 700; color: var(--text-dark); }

        /* Preferences Toggles */
        .preferences-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .pref-item { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px; background: var(--bg-main); border-radius: 12px; border: 1px solid var(--border); }
        .pref-info h4 { margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; }
        .pref-info p { margin: 0; font-size: 11px; color: var(--text-gray); }
        
        .toggle-switch { position: relative; width: 40px; height: 22px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E1; transition: .4s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input:checked + .toggle-slider { background-color: var(--primary-purple); }
        input:checked + .toggle-slider:before { transform: translateX(18px); }

        /* Security Info Values */
        .value-green { color: #10B981 !important; font-weight: 700; }
        
        /* Document List */
        .doc-item { display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--bg-main); border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border); }
        .doc-item:last-child { margin-bottom: 0; }
        .doc-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .doc-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .doc-icon.purple { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); }
        .doc-icon.red { background: rgba(239, 68, 68, 0.1); color: #EF4444; }
        .doc-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .doc-info { flex-grow: 1; }
        .doc-info h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-dark); }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-verified { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        
        .doc-actions { display: flex; align-items: center; gap: 8px; color: var(--text-gray); }
        .doc-actions a { color: var(--text-gray); font-size: 18px; transition: var(--transition); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--white); border: 1px solid var(--border); }
        .doc-actions a:hover { color: var(--primary-purple); border-color: var(--primary-purple); }
        
        .upload-doc-link { display: inline-flex; align-items: center; gap: 6px; color: var(--primary-purple); font-weight: 600; font-size: 13px; text-decoration: none; margin-top: 16px; }
        .upload-doc-link:hover { text-decoration: underline; }

        /* Cropper Modal Styles */
        #cropperModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
        .cropper-content { background: var(--white); padding: 32px; border-radius: 24px; max-width: 500px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .img-container { width: 100%; max-height: 400px; margin-bottom: 24px; overflow: hidden; border-radius: 16px; }
        
        .hidden-form { display: none; }
    </style>
</head>
<body style="display: block;">

<main class="main-renter">
    <header class="header-renter">
        <div class="header-title-area">
            <h1>Profile Settings</h1>
            <p>View and update your personal information and preferences.</p>
        </div>
        <div class="header-actions">
            <div class="header-icon">
                <i class='bx bx-bell'></i>
                <span class="badge">2</span>
            </div>
            <div class="header-icon" id="themeToggle">
                <i class='bx bx-moon'></i>
            </div>
            <a href="queries.php" class="btn-outline"><i class='bx bx-help-circle'></i> Help & Support</a>
            
            <div class="user-profile-pill">
                <div class="user-avatar-sm"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
                <div class="user-info-sm">
                    <h4><?php echo htmlspecialchars($display_name); ?></h4>
                    <p>Room <?php echo htmlspecialchars($user['room_no']); ?></p>
                </div>
                <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
            </div>
        </div>
    </header>

    <?php if ($errmsg): ?>
        <div id="statusAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errmsg); ?>
        </div>
    <?php elseif ($success): ?>
        <div id="statusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease;">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid animate-up">
        <!-- LEFT COLUMN -->
        <div class="grid-col-left">
            <!-- Avatar Card -->
            <div class="panel avatar-card">
                <div class="avatar-wrapper">
                    <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="avatar-huge">
                    <button type="button" class="btn-edit-avatar" onclick="document.getElementById('profilePicInput').click()">
                        <i class='bx bx-camera'></i>
                    </button>
                    <!-- Hidden form for profile pic -->
                    <form method="POST" id="hiddenProfileForm" class="hidden-form">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <input type="hidden" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp']); ?>">
                        <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($user['room_no']); ?>">
                        <input type="hidden" name="about" value="<?php echo htmlspecialchars($user['about']); ?>">
                        <input type="file" id="profilePicInput" accept="image/*">
                        <input type="hidden" name="cropped_image" id="croppedImageInput">
                        <button type="submit" name="save_profile" id="saveProfileBtn"></button>
                    </form>
                </div>
                <h2 style="margin: 0 0 8px 0; font-weight: 800; font-size: 24px; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></h2>
                <span style="display: inline-block; padding: 6px 16px; background: var(--white); color: var(--primary-purple); font-weight: 700; border-radius: 20px; font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">Room <?php echo htmlspecialchars($user['room_no']); ?></span>
            </div>

            <!-- Basic Information -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Basic Information</h3>
                    <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                </div>
                <div class="info-list">
                    <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Full Name</div><div class="info-value"><?php echo htmlspecialchars($user['name'] ?: '-'); ?></div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-envelope'></i> Email Address</div><div class="info-value"><?php echo htmlspecialchars($user['email'] ?: '-'); ?></div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-phone-call'></i> Alternate Number</div><div class="info-value"><?php echo htmlspecialchars($user['whatsapp'] ?: '+91 91234 56789'); ?></div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-calendar'></i> Date of Birth</div><div class="info-value">15 Aug 1995</div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-male'></i> Gender</div><div class="info-value">Male</div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;">Madhav Kunj Apartments, Block A,<br>Room <?php echo htmlspecialchars($user['room_no']); ?>, City Center, Patna - 800001</div></div>
                </div>
            </div>

            <!-- Residence Details -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Residence Details</h3>
                    <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                </div>
                <div class="residence-grid">
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-home-alt-2'></i></div>
                        <div class="residence-info"><h4>Flat / Room No.</h4><p><?php echo htmlspecialchars($user['room_no']); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-building-house'></i></div>
                        <div class="residence-info"><h4>Block / Building</h4><p>Block A</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-map-alt'></i></div>
                        <div class="residence-info"><h4>Property Name</h4><p><?php echo HOUSE_NAME; ?> Apartments</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-layer'></i></div>
                        <div class="residence-info"><h4>Floor</h4><p>2nd Floor</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-calendar-event'></i></div>
                        <div class="residence-info"><h4>Move-in Date</h4><p><?php echo $user['joining_date'] ? date('d M Y', strtotime($user['joining_date'])) : '01 Jan 2024'; ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-rupee'></i></div>
                        <div class="residence-info"><h4>Monthly Rent</h4><p>₹<?php echo number_format($user['fixed_rent'] ?? 8000, 2); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-check-shield'></i></div>
                        <div class="residence-info"><h4>Security Deposit</h4><p>₹<?php echo number_format($user['advance_payment'] ?? 16000, 2); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-car'></i></div>
                        <div class="residence-info"><h4>Parking Slot</h4><p>A-15</p></div>
                    </div>
                </div>
            </div>
            
            <!-- Preferences -->
            <div class="panel">
                <div class="panel-header" style="margin-bottom: 16px;">
                    <h3>Preferences</h3>
                </div>
                <div class="preferences-grid">
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-envelope' style="color: var(--primary-purple);"></i> Email Notifications</h4>
                            <p>Receive important updates via email</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-message-rounded-dots' style="color: var(--primary-purple);"></i> SMS Notifications</h4>
                            <p>Receive important updates via SMS</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-bell' style="color: var(--primary-purple);"></i> Bill Reminders</h4>
                            <p>Receive reminders before due</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="grid-col-right">
            <!-- Account & Security -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-check-shield'></i> Account & Security</h3>
                </div>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-lock-alt'></i> Password</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value">••••••••</div>
                            <button class="btn-outline" style="padding: 4px 12px;" onclick="alert('Password change modal')">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-envelope'></i> Login Email</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value"><?php echo htmlspecialchars($user['email'] ?: 'user@example.com'); ?></div>
                            <button class="btn-outline" style="padding: 4px 12px;">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-shield-quarter'></i> Two-Factor Auth</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value value-green">Enabled</div>
                            <button class="btn-outline" style="padding: 4px 12px;">Manage</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-info-circle'></i> Account Status</div>
                        <div class="info-value value-green">Active</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-user-circle'></i> Emergency Contact</h3>
                    <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
                </div>
                <div class="info-list">
                    <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Contact Name</div><div class="info-value">Ramesh Kumar</div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-group'></i> Relationship</div><div class="info-value">Father</div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value">+91 87654 32109</div></div>
                    <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value">Patna, Bihar - 800001</div></div>
                </div>
            </div>

            <!-- Linked Documents -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-file'></i> Linked Documents</h3>
                    <button class="btn-outline" style="padding: 6px 12px;">View All</button>
                </div>
                <div class="doc-list">
                    <div class="doc-item">
                        <div class="doc-icon green"><i class='bx bx-id-card'></i></div>
                        <div class="doc-info"><h4>Aadhar Card</h4></div>
                        <div class="status-pill status-verified">Verified</div>
                        <div class="doc-actions">
                            <?php if ($aadhaar_file): ?>
                                <a href="../<?php echo htmlspecialchars($aadhaar_file); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="#"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                    <div class="doc-item">
                        <div class="doc-icon purple"><i class='bx bx-file-blank'></i></div>
                        <div class="doc-info"><h4>Agreement Copy</h4></div>
                        <div class="status-pill <?php echo $user['agreement_document'] ? 'status-verified' : 'status-pending'; ?>"><?php echo $user['agreement_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['agreement_document']): ?>
                                <a href="../admin/download-agreement.php?id=<?php echo $user_id; ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="#"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                    <div class="doc-item">
                        <div class="doc-icon red"><i class='bx bx-receipt'></i></div>
                        <div class="doc-info"><h4>Rent Receipt</h4></div>
                        <div class="status-pill status-verified">Verified</div>
                        <div class="doc-actions"><a href="#"><i class='bx bx-show'></i></a><a href="#"><i class='bx bx-chevron-right'></i></a></div>
                    </div>
                    <div class="doc-item">
                        <div class="doc-icon yellow"><i class='bx bx-wallet-alt'></i></div>
                        <div class="doc-info"><h4>Bank Passbook</h4></div>
                        <div class="status-pill status-pending" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Pending</div>
                        <div class="doc-actions"><a href="#"><i class='bx bx-show'></i></a><a href="#"><i class='bx bx-chevron-right'></i></a></div>
                    </div>
                </div>
                <a href="#" class="upload-doc-link" onclick="document.getElementById('aadhaarUploadInput').click()"><i class='bx bx-plus'></i> Upload New Document</a>
                <!-- Hidden Aadhaar Upload Form -->
                <form method="POST" enctype="multipart/form-data" class="hidden-form" id="hiddenAadhaarForm">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    <input type="file" name="aadhaar" id="aadhaarUploadInput" accept="image/*,application/pdf" onchange="document.getElementById('saveAadhaarBtn').click()">
                    <button type="submit" name="save_profile" id="saveAadhaarBtn"></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cropper Modal -->
    <div id="cropperModal">
        <div class="cropper-content animate-up">
            <h3 style="margin-bottom: 15px; font-weight: 700; color: var(--text-dark);">Crop Your Image</h3>
            <div class="img-container">
                <img id="imageToCrop" src="" alt="To Crop">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="closeCropper()" style="padding: 10px 20px;">Cancel</button>
                <button type="button" class="btn-primary" onclick="applyCrop()" style="padding: 10px 20px; background: var(--primary-purple); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Crop & Set</button>
            </div>
        </div>
    </div>
</main>
"""

updated_content = content[:style_start] + new_html + content[main_end:]

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(updated_content)

print("Replaced profile.php layout successfully.")
