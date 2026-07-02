<?php
// EXCLUSIVE DESKTOP VIEW FOR PROFILE.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


    <header class="header-renter">
        <div class="header-greeting">
            <div class="header-icon-wrapper">
                <i class='bx bx-user-circle' style="font-size: 24px; color: var(--primary-purple);"></i>
            </div>
            <div class="header-title-area">
                <h1>Profile Settings</h1>
                <p>View and update your personal information and preferences.</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="notification-wrapper">
                <div class="icon-btn bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'block' : 'none';">
                    <i class='bx bx-bell'></i>
                    <?php if ($unread_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Notification Dropdown -->
                <div id="notifDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 320px; z-index: 1000; overflow: hidden;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark);">Notifications</h3>
                        <?php if($unread_count > 0): ?>
                            <span style="font-size: 11px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 8px; border-radius: 10px; font-weight: 600;"><?php echo $unread_count; ?> New</span>
                        <?php endif; ?>
                    </div>
                    <div style="max-height: 350px; overflow-y: auto;">
                        <?php if (empty($unread_notifications)): ?>
                            <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <div class="notif-item animate-up" data-id="<?php echo $notif['id']; ?>" style="border-bottom: 1px solid var(--border); position: relative; overflow: hidden; background: var(--white); cursor: default;">
                                    <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: #EF4444; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1;">
                                        <i class='bx bx-trash'></i>
                                    </div>
                                    <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; position: relative; z-index: 2; background: var(--white); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                            <i class='bx <?php echo $notif['icon']; ?>'></i>
                                        </div>
                                        <div style="flex: 1; padding-right: 36px;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                                <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text-dark); padding-right: 8px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                <span style="font-size: 11px; color: var(--text-gray); font-weight: 600; white-space: nowrap;"><?php echo date('M d', strtotime($notif['time'])); ?></span>
                                            </div>
                                            <p style="margin: 0; font-size: 13px; color: var(--text-gray); line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        </div>
                                        <button onclick="dismissNotification('<?php echo $notif['id']; ?>', this)" style="position: absolute; right: 12px; top: 16px; background: none; border: none; font-size: 18px; color: var(--text-gray); opacity: 0.5; cursor: pointer; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.05)'; this.style.opacity='1'" onmouseout="this.style.background='none'; this.style.opacity='0.5'" title="Dismiss">
                                            <i class='bx bx-x'></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="icon-btn" id="themeToggle" style="cursor: pointer;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i')||(this.tagName==='I'?this:null);if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
                    <i class='bx bx-moon'></i>
                </div>
            <a href="queries.php" class="btn-outline-support"><i class='bx bx-help-circle'></i> Help & Support</a>
          
            <div style="position: relative;">
                <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                    <div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">
<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                        <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                    </div>
                    <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                </div>
                
                <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: var(--white); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                    <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                        <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                    </a>
                    <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 14px; font-weight: 500; transition: 0.2s;">
                        <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <?php if ($errmsg): ?>
        <div id="statusAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 14px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errmsg); ?>
        </div>
    <?php elseif ($success): ?>
        <div id="statusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 14px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease;">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid animate-up">
        <!-- LEFT COLUMN -->
        <div class="grid-col-left">
            <!-- Avatar Card -->
            <div class="panel avatar-card">
                <div class="avatar-wrapper">
                    <?php if (!empty($profile_pic) && file_exists("../" . $profile_pic)): ?>
                        <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="avatar-huge" id="profileAvatarImg">
                    <?php else: ?>
                        <div class="avatar-huge" id="profileAvatarFallback" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); box-shadow: 0 10px 25px rgba(98, 75, 255, 0.25); color: white; font-size: 48px; font-weight: 700; letter-spacing: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                        </div>
                        <img src="" alt="profile" class="avatar-huge" id="profileAvatarImg" style="display: none;">
                    <?php endif; ?>
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
                <h2 style="margin: 0 0 12px 0; font-weight: 800; font-size: 22px; color: var(--text-dark); letter-spacing: -0.5px;"><?php echo htmlspecialchars($display_name); ?></h2>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 18px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); font-weight: 700; border-radius: 20px; font-size: 13.5px; box-shadow: inset 0 0 0 1px rgba(98, 75, 255, 0.1);"><i class='bx bx-door-open' style="font-size: 17px;"></i> Room <?php echo htmlspecialchars($user['room_no']); ?></span>
            </div>

            <div class="panel">
                  <div class="panel-header">
                      <h3>Basic Information</h3>
                      <button class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='flex'"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Full Name</div><div class="info-value"><?php echo htmlspecialchars($user['name'] ?: '-'); ?></div></div>
                      <?php
                      $email_val = $user['email'] ?: '-';
                      $em_len = strlen($email_val);
                      $em_fs = $em_len > 30 ? '11px' : ($em_len > 24 ? '12px' : ($em_len > 18 ? '13px' : '14px'));
                      ?>
                      <div class="info-row"><div class="info-label"><i class='bx bx-envelope'></i> Email Address</div><div class="info-value" style="font-size: <?php echo $em_fs; ?>; max-width: 70%; white-space: nowrap;"><?php echo htmlspecialchars($email_val); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone-call'></i> Alternate Number</div><div class="info-value"><?php echo htmlspecialchars($user['whatsapp'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-calendar'></i> Date of Birth</div><div class="info-value"><?php echo $user['dob'] ? date('d M Y', strtotime($user['dob'])) : '-'; ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-male'></i> Gender</div><div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['address'] ?: '-'); ?></div></div>
                  </div>
              </div>

            <!-- Residence Details -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Residence Details</h3>
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
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="info-value">••••••••</div>
                            <button type="button" id="btnOpenChangePwdModal" class="btn-outline" style="padding: 4px 12px; flex-shrink: 0; cursor: pointer;" onclick="document.getElementById('changePasswordModal').style.display='flex'; return false;">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-envelope'></i> Login Email</div>
                        <?php
                        $login_email = $user['email'] ?: 'user@example.com';
                        $len_login = strlen($login_email);
                        $fs_login = $len_login > 30 ? '11px' : ($len_login > 24 ? '12px' : ($len_login > 18 ? '13px' : '14px'));
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; max-width: 70%;">
                            <div class="info-value" style="font-size: <?php echo $fs_login; ?>; max-width: 100%; white-space: nowrap;"><?php echo htmlspecialchars($login_email); ?></div>
                            <button type="button" class="btn-outline" style="padding: 4px 12px; flex-shrink: 0;" onclick="var ep = document.getElementById('editProfileModal'); if(ep) { ep.style.display='flex'; setTimeout(function(){ var ei = ep.querySelector('input[name=\'email\']'); if(ei) { ei.focus(); ei.select(); } }, 100); } return false;">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-shield-quarter'></i> Two-Factor Auth</div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="info-value value-green">Enabled</div>
                            <button class="btn-outline" style="padding: 4px 12px; flex-shrink: 0;">Manage</button>
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
                      <button class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='flex'"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Contact Name</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_name'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-group'></i> Relationship</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_relation'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['emergency_contact_address'] ?: '-'); ?></div></div>
                  </div>
              </div>

            <!-- Linked Documents -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-file'></i> Linked Documents</h3>
                    <button class="btn-outline" style="padding: 6px 12px;" onclick="window.location.href='documents.php'">View All</button>
                </div>
                <div class="doc-list">
                    <!-- Aadhar Card -->
                    <div class="doc-item">
                        <div class="doc-icon green"><i class='bx bx-id-card'></i></div>
                        <div class="doc-info"><h4>Aadhar Card</h4></div>
                        <div class="status-pill <?php echo $user['aadhaar_file'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['aadhaar_file'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['aadhaar_file'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['aadhaar_file']): ?>
                                <?php $aadhaar_url = (strpos($user['aadhaar_file'], 'uploads/') === 0) ? '../' . $user['aadhaar_file'] : '../uploads/aadhaar/' . $user['aadhaar_file']; ?>
                                <a href="<?php echo htmlspecialchars($aadhaar_url); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php else: ?>
                                <a href="documents.php#upload-widget-container" title="Upload"><i class='bx bx-upload'></i></a>
                            <?php endif; ?>
                            <a href="documents.php#upload-widget-container"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                    
                    <!-- Agreement Copy -->
                    <div class="doc-item">
                        <div class="doc-icon purple"><i class='bx bx-file-blank'></i></div>
                        <div class="doc-info"><h4>Agreement Copy</h4></div>
                        <div class="status-pill <?php echo $user['agreement_document'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['agreement_document'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['agreement_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['agreement_document']): ?>
                                <?php $agree_url = (strpos($user['agreement_document'], 'uploads/') === 0) ? '../' . $user['agreement_document'] : '../uploads/agreements/' . $user['agreement_document']; ?>
                                <a href="<?php echo htmlspecialchars($agree_url); ?>" target="_blank"><i class='bx bx-show'></i></a>
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
                                <?php $elec_url = (strpos($user['electricity_document'], 'uploads/') === 0) ? '../' . $user['electricity_document'] : '../uploads/documents/' . $user['electricity_document']; ?>
                                <a href="<?php echo htmlspecialchars($elec_url); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>