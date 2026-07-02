<?php
// EXCLUSIVE DESKTOP VIEW FOR QUERIES.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-message-square-dots' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">Raise Query</h1>
                    <p style="margin: 0;">Submit a request or report an issue.</p>
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
                <a href="#" class="btn-outline" style="width: auto; padding: 10px 20px; border-radius: 12px;"><i class='bx bx-help-circle'></i> Help & Support</a>
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
        <div class="kpi-grid-4">
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                        <i class='bx bx-message-rounded-dots'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Total Queries</h4>
                        <h2><?php echo $total_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">All time</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                        <i class='bx bx-time'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Open Queries</h4>
                        <h2><?php echo $open_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Awaiting response</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Resolved Queries</h4>
                        <h2><?php echo $resolved_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Successfully resolved</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                        <i class='bx bx-x-circle'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Closed Queries</h4>
                        <h2><?php echo $closed_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">Closed by you</span>
                </div>
            </div>
        </div>

        <?php if($success): ?>
            <div id="successMsgAlert" style="padding: 16px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: opacity 0.5s ease-out;">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i> <?php echo $success; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('successMsgAlert');
                    if(el) {
                        el.style.opacity = '0';
                        setTimeout(() => el.style.display = 'none', 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>
        <?php if($error): ?>
            <div id="errorMsgAlert" style="padding: 16px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: opacity 0.5s ease-out;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo $error; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('errorMsgAlert');
                    if(el) {
                        el.style.opacity = '0';
                        setTimeout(() => el.style.display = 'none', 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>

        <!-- 2-Column Layout -->
        <div class="query-layout">
            <!-- Left: Form -->
            <div class="form-card">
                <h3 class="form-title">Submit a New Query</h3>
                <form method="POST" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="form-group">
                        <label class="form-label">Query Category</label>
                        <select name="category" class="form-control" required style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px;">
                            <option value="">Select Category</option>
                            <option value="Plumbing">Plumbing</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Housekeeping">Housekeeping</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Enter a short subject" required>
                    </div>
                    <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                        <label class="form-label">Description</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Describe your issue or request in detail..." required style="resize: none; flex: 1; min-height: 80px;"></textarea>
                        <div style="text-align: right; margin-top: 8px; font-size: 11px; color: var(--text-gray); font-weight: 500;">0/500</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Upload Image (Optional)</label>
                        <div class="upload-box" onclick="document.getElementById('fileUpload').click();">
                            <i class='bx bx-upload'></i>
                            <h5>Click to upload <span style="color: var(--text-gray); font-weight: 400;">or drag and drop</span></h5>
                            <p>PNG, JPG, JPEG up to 5MB</p>
                        </div>
                        <input type="file" id="fileUpload" style="display: none;" accept="image/png, image/jpeg, image/jpg">
                    </div>

                    <button type="submit" name="submit_query" class="btn-primary" style="margin-top: auto;">
                        <i class='bx bx-send'></i> Submit Query
                    </button>
                </form>
            </div>

            <!-- Right: List -->
            <div class="list-card">
                <div class="list-header">
                    <h3>My Queries</h3>
                    <div style="display: flex; gap: 12px;">
                        <?php $current_filter = $_GET['status'] ?? 'All Status'; ?>
                        <select class="form-control" onchange="window.location.href='?status=' + this.value;" style="padding: 8px 36px 8px 16px; width: auto; font-weight: 600; font-size: 13px; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;">
                            <option value="All Status" <?php echo $current_filter === 'All Status' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Open" <?php echo $current_filter === 'Open' ? 'selected' : ''; ?>>Open</option>
                            <option value="Resolved" <?php echo $current_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Closed" <?php echo $current_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button class="btn-outline" style="width: auto; padding: 8px 16px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;">
                    <?php 
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = 5;
                    
                    // Filter logic
                    $filter_status = $_GET['status'] ?? 'All Status';
                    $filtered_queries = [];
                    foreach ($queries as $q) {
                        if ($filter_status === 'All Status' || $q['ui_status'] === $filter_status) {
                            $filtered_queries[] = $q;
                        }
                    }
                    
                    $total_filtered = count($filtered_queries);
                    $total_pages = $total_filtered > 0 ? ceil($total_filtered / $limit) : 1;
                    if ($page > $total_pages) $page = $total_pages;
                    $offset = ($page - 1) * $limit;
                    
                    if(empty($filtered_queries)) {
                        echo '<div style="padding: 40px; text-align: center; color: var(--text-gray);">No queries found for this status.</div>';
                    }
                    $paginated_queries = array_slice($filtered_queries, $offset, $limit);
                    foreach($paginated_queries as $index => $q): 
                        // Map categories to icons and colors
                        $cat = strtolower($q['category']);
                        if (strpos($cat, 'plumbing') !== false) {
                            $icon = 'bx-water'; $bg = 'rgba(245, 158, 11, 0.1)'; $col = '#F59E0B';
                        } elseif (strpos($cat, 'elect') !== false) {
                            $icon = 'bx-bolt-circle'; $bg = 'rgba(59, 130, 246, 0.1)'; $col = '#3B82F6';
                        } elseif (strpos($cat, 'housekeep') !== false || strpos($cat, 'clean') !== false) {
                            $icon = 'bx-brush'; $bg = 'rgba(16, 185, 129, 0.1)'; $col = '#10B981';
                        } elseif (strpos($cat, 'maintain') !== false || strpos($cat, 'maintenance') !== false) {
                            $icon = 'bx-wrench'; $bg = 'rgba(98, 75, 255, 0.1)'; $col = 'var(--primary-purple)';
                        } elseif (strpos($cat, 'parking') !== false) {
                            $icon = 'bx-car'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        } elseif (strpos($cat, 'general') !== false) {
                            $icon = 'bx-category'; $bg = 'rgba(139, 92, 246, 0.1)'; $col = '#8B5CF6';
                        } else {
                            $icon = 'bx-info-circle'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        }

                        // Map Status
                        $st = strtolower($q['ui_status']);
                        if ($st == 'open') {
                            $s_bg = 'rgba(245, 158, 11, 0.1)'; $s_col = '#F59E0B';
                        } elseif ($st == 'in progress') {
                            $s_bg = 'rgba(59, 130, 246, 0.1)'; $s_col = '#3B82F6';
                        } elseif ($st == 'resolved') {
                            $s_bg = 'rgba(16, 185, 129, 0.1)'; $s_col = '#10B981';
                        } else {
                            $s_bg = 'rgba(239, 68, 68, 0.1)'; $s_col = '#EF4444'; // Closed
                        }

                        $date_formatted = date('d M Y', strtotime($q['created_at']));
                        $qid_formatted = '#QRY-' . str_pad($q['id'], 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="query-row" data-id="<?php echo $q['id']; ?>">
                        <div class="query-item" onclick="toggleDetails(<?php echo $index; ?>)" style="cursor: pointer;">
                            <div class="qi-icon" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                                <i class='bx <?php echo $icon; ?>'></i>
                            </div>
                            <div class="qi-details">
                                <h4><?php echo htmlspecialchars($q['subject']); ?></h4>
                                <span class="category"><?php echo htmlspecialchars($q['category']); ?></span>
                                <p><?php echo htmlspecialchars($q['message']); ?></p>
                            </div>
                            <div class="qi-status" style="background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>;">
                                <?php echo htmlspecialchars($q['ui_status']); ?>
                            </div>
                            <div class="qi-meta">
                                <span class="date"><?php echo $date_formatted; ?></span>
                                <span class="qid"><?php echo $qid_formatted; ?></span>
                            </div>
                            <button class="qi-action" id="btn-<?php echo $index; ?>" style="transition: transform 0.3s;"><i class='bx bx-chevron-right'></i></button>
                        </div>
                        <div id="details-<?php echo $index; ?>" style="display: none; padding: 0 0 20px 64px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
                                <p style="font-size: 14px; color: var(--text-dark); margin: 0; line-height: 1.6;"><strong>Full Message:</strong><br><span style="color: var(--text-gray); font-size: 13px;"><?php echo nl2br(htmlspecialchars($q['message'])); ?></span></p>
                                <a href="?action=delete&id=<?php echo $q['id']; ?>" onclick="return confirm('Are you sure you want to delete this query?');" style="color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; flex-shrink: 0; background: rgba(239, 68, 68, 0.05);"><i class='bx bx-trash'></i> Delete</a>
                            </div>
                            <?php if(!empty($q['admin_remark'])): ?>
                                <div style="padding: 16px; background: rgba(98, 75, 255, 0.05); border-left: 4px solid var(--primary-purple); border-radius: 8px;">
                                    <p style="font-size: 13px; color: var(--primary-purple); margin: 0; line-height: 1.5;"><strong>Admin Reply:</strong><br><?php echo nl2br(htmlspecialchars($q['admin_remark'])); ?></p>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 13px; color: var(--text-gray); margin: 0; font-style: italic;">No response from admin yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer Pagination -->
                <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                    <?php 
                    $start_idx = $total_filtered > 0 ? $offset + 1 : 0;
                    $end_idx = min($offset + $limit, $total_filtered);
                    ?>
                    <span>Showing <?php echo $start_idx; ?> to <?php echo $end_idx; ?> of <?php echo $total_filtered; ?> queries</span>
                    <div style="display: flex; gap: 8px;">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                        <?php else: ?>
                            <button class="page-btn" style="opacity: 0.5; cursor: not-allowed;" disabled><i class='bx bx-chevron-left'></i></button>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <button class="page-btn active"><?php echo $i; ?></button>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page-btn"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                        <?php else: ?>
                            <button class="page-btn" style="opacity: 0.5; cursor: not-allowed;" disabled><i class='bx bx-chevron-right'></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>