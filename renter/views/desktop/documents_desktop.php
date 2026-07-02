<?php
// EXCLUSIVE DESKTOP VIEW FOR DOCUMENTS.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-folder' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">My Documents</h1>
                    <p style="margin: 0;">Access your important agreements and files.</p>
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
                    <div id="notifDropdown" style="display: none;">
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
                <a href="#" class="btn-outline"><i class='bx bx-help-circle'></i> Help & Support</a>
                <div style="position: relative;">
                    <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                        <div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">
<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>
</div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $usr['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
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

        <!-- KPI Grid -->
        <div class="kpi-grid-4" style="grid-template-columns: repeat(3, 1fr);">
            <div class="kpi-card">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); margin: 0;"><i class='bx bx-folder'></i></div>
                    <div class="kpi-info" style="text-align: left;">
                        <h4>Total Documents</h4>
                        <h2>2</h2>
                    </div>
                </div>
                <p class="kpi-subtext">All documents</p>
            </div>
            
            <div class="kpi-card">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981; margin: 0;"><i class='bx bx-check-shield'></i></div>
                    <div class="kpi-info" style="text-align: left;">
                        <h4>Verified Documents</h4>
                        <h2><?php echo $verified_count; ?></h2>
                    </div>
                </div>
                <p class="kpi-subtext">Approved & verified</p>
            </div>

            <div class="kpi-card">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; margin: 0;"><i class='bx bx-time-five'></i></div>
                    <div class="kpi-info" style="text-align: left;">
                        <h4>Pending Documents</h4>
                        <h2><?php echo $pending_count; ?></h2>
                    </div>
                </div>
                <p class="kpi-subtext">Awaiting verification</p>
            </div>
        </div>

        <!-- Documents Layout -->
        <div class="docs-layout">
            <!-- Left: List -->
            <div class="list-card">
                <!-- Filters -->
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 16px;">
                    <select style="padding: 10px 32px 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 12px center; background-size: 14px;">
                        <option>All Categories</option>
                    </select>
                    <select style="padding: 10px 32px 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 12px center; background-size: 14px;">
                        <option>All Status</option>
                    </select>
                    
                    <div style="position: relative; flex: 1;">
                        <i class='bx bx-search' style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 18px;"></i>
                        <input type="text" placeholder="Search documents..." style="width: 100%; padding: 10px 16px 10px 42px; border: 1px solid var(--border); border-radius: 8px; background: rgba(0,0,0,0.01); outline: none; font-size: 13px; font-weight: 500; font-family: inherit; color: var(--text-dark); box-sizing: border-box;">
                    </div>
                    
                    <button class="btn-outline" style="margin: 0; border-radius: 8px; padding: 10px 20px;"><i class='bx bx-filter'></i> Filter</button>
                </div>

                <!-- Table -->
                <div style="width: 100%; overflow-x: auto;">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Category</th>
                                <th>Uploaded On</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 16px;">
                                        <div style="width: 44px; height: 44px; border-radius: 12px; background: <?php echo $doc['cat_bg']; ?>; color: <?php echo $doc['cat_color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                                            <i class='bx <?php echo $doc['icon']; ?>'></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px; white-space: nowrap;"><?php echo $doc['name']; ?></div>
                                            <div style="font-size: 11px; font-weight: 500; color: var(--text-gray); white-space: nowrap;"><?php echo $doc['desc']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 11px; font-weight: 700; color: <?php echo $doc['cat_color']; ?>; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); white-space: nowrap;">
                                        <?php echo $doc['category']; ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 2px;"><?php echo $doc['date']; ?></div>
                                    <div style="font-size: 11px; font-weight: 500; color: var(--text-gray);"><?php echo $doc['time']; ?></div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $doc['status']; ?>">
                                        <?php if($doc['status'] == 'Verified'): ?> <i class='bx bx-check-circle'></i> 
                                        <?php elseif($doc['status'] == 'Pending'): ?> <i class='bx bx-time'></i>
                                        <?php else: ?> <i class='bx bx-x-circle'></i> <?php endif; ?>
                                        <?php echo $doc['status']; ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-dark);"><?php echo $doc['size']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex;">
                                        <?php if (!empty($doc['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($doc['url']); ?>" target="_blank" class="action-btn" style="text-decoration: none;" title="View"><i class='bx bx-show'></i></a>
                                        <a href="<?php echo htmlspecialchars($doc['url']); ?>" download class="action-btn" style="text-decoration: none;" title="Download"><i class='bx bx-download'></i></a>
                                        <?php else: ?>
                                        <button class="action-btn" disabled style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-show'></i></button>
                                        <button class="action-btn" disabled style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-download'></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Widgets -->
            <div>
                <!-- Upload Widget -->
                <div id="upload-widget-container">
                    <?php if (empty($user_docs['aadhaar_file'])): ?>
                    <div class="side-widget" style="text-align: center; padding: 40px 24px;">
                        <h3 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 800; color: var(--text-dark);">Upload Identity Proof (Aadhar Card)</h3>
                        
                        <?php if ($upload_msg): ?>
                            <div style="padding: 12px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #10B981; font-size: 13px; font-weight: 600; margin-bottom: 20px;"><?php echo htmlspecialchars($upload_msg); ?></div>
                        <?php endif; ?>
                        <?php if ($upload_err): ?>
                            <div style="padding: 12px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #EF4444; font-size: 13px; font-weight: 600; margin-bottom: 20px;"><?php echo htmlspecialchars($upload_err); ?></div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="upload-area" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; max-width: 800px; margin: 0 auto; border-width: 2px; border-style: dashed;" onclick="document.getElementById('aadhar-upload').click();">
                                <div class="upload-icon" style="width: 80px; height: 80px; font-size: 40px;">
                                    <i class='bx bx-cloud-upload'></i>
                                </div>
                                <h4 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 700; color: var(--text-dark);">Drag and drop your Aadhar Card here or click to browse</h4>
                                <p style="margin: 0 0 32px 0; font-size: 13px; font-weight: 500; color: var(--text-gray);">Supports: PDF, JPG, PNG (Max. 10MB)</p>
                                
                                <input type="file" id="aadhar-upload" name="aadhar_file" accept=".pdf, .jpg, .jpeg, .png" style="display: none;" onchange="handleAjaxUpload(this)" onclick="event.stopPropagation()">
                                <button type="button" id="choose-file-btn" class="btn-primary" style="width: auto; min-width: 200px; padding: 14px 32px; font-size: 15px; text-align: center; display: inline-flex; justify-content: center; align-items: center;">Choose File</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="side-widget" style="text-align: center; padding: 60px 24px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: #10B981; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 24px;"><i class='bx bx-check-shield'></i></div>
                        <h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 800; color: var(--text-dark);">Identity Verified</h3>
                        <p style="margin: 0; font-size: 14px; font-weight: 500; color: var(--text-gray); line-height: 1.6;">Your Aadhar Card has been securely uploaded and verified.<br>You cannot overwrite a verified document.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tips Widget (Moved outside docs-layout) -->
            </div>
        </div>

        <!-- Full Width Tips Widget -->
        <div class="side-widget" style="background: var(--white); margin-top: 24px;">
            <h3 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-bulb' style="color: #F59E0B; font-size: 20px;"></i> Important Tips
            </h3>
            <ul class="tips-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <li><i class='bx bx-check'></i> Upload clear and valid documents for quick verification.</li>
                <li><i class='bx bx-check'></i> Supported formats: PDF, JPG, PNG</li>
                <li><i class='bx bx-check'></i> Max file size: 10MB per document</li>
                <li><i class='bx bx-check'></i> Keep your documents up to date</li>
            </ul>
        </div>