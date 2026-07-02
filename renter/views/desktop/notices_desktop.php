<?php
// EXCLUSIVE DESKTOP VIEW FOR NOTICES.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-bell' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">Notices & Announcements</h1>
                    <p style="margin: 0;">Stay updated with the latest alerts.</p>
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
                <a href="#" class="btn-outline" style="border-radius: 20px;"><i class='bx bx-help-circle'></i> Help & Support</a>
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
                        <i class='bx bxs-megaphone'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Total Notices</h4>
                        <h2><?php echo $total_notices; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">All time</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                        <i class='bx bx-file'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>New Notices</h4>
                        <h2><?php echo $new_notices; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Last 7 Days</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                        <i class='bx bx-calendar'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>This Month</h4>
                        <h2><?php echo $this_month_notices; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><?php echo $current_month_name; ?></span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                        <i class='bx bx-map-pin'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Important Notices</h4>
                        <h2><?php echo $important_notices; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">High Priority</span>
                </div>
            </div>
        </div>

        <!-- 2-Column Layout -->
        <div class="notice-layout">
            <!-- Left: List -->
            <div class="list-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div class="tabs">
                        <div class="tab active">All Notices</div>
                        <div class="tab">Important</div>
                        <div class="tab">General</div>
                        <div class="tab">Maintenance</div>
                        <div class="tab">Events</div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-left: 16px;">
                        <select style="padding: 8px 32px 8px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 10px center; background-size: 14px;">
                            <option>All Categories</option>
                        </select>
                        <button class="btn-outline" style="padding: 8px 16px; border-radius: 8px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;" id="notice-list-container">
                    <?php if (count($paginated_notices) > 0): ?>
                        <?php foreach($paginated_notices as $i => $n): ?>
                    <div class="notice-item <?php echo $i===0 ? 'active' : ''; ?> <?php echo $n['is_new'] ? 'unread' : ''; ?>" data-id="<?php echo $n['id']; ?>">
                        <div class="ni-dot"></div>
                        <div class="ni-icon" style="background: <?php echo $n['icon_bg']; ?>; color: <?php echo $n['icon_color']; ?>;">
                            <i class='bx <?php echo $n['icon']; ?>'></i>
                        </div>
                        <div class="ni-details">
                            <div class="ni-header">
                                <h4><?php echo htmlspecialchars($n['title']); ?></h4>
                                <span class="ni-badge" style="background: <?php echo $n['badge_bg']; ?>; color: <?php echo $n['badge_color']; ?>;">
                                    <?php echo htmlspecialchars($n['category']); ?>
                                </span>
                            </div>
                            <p class="ni-desc"><?php echo htmlspecialchars($n['desc']); ?></p>
                        </div>
                        <div class="ni-meta" style="flex-direction: row; align-items: center; justify-content: flex-end; gap: 16px; min-width: 150px;">
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <p class="date"><?php echo htmlspecialchars($n['date']); ?></p>
                                <p class="time"><?php echo htmlspecialchars($n['time']); ?></p>
                            </div>
                            <?php if($n['is_new']): ?>
                            <div>
                                <span class="ni-new-badge">New</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 400px; color: var(--text-gray); text-align: center;">
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: rgba(98, 75, 255, 0.05); display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                                <i class='bx bx-ghost' style="font-size: 64px; color: rgba(98, 75, 255, 0.3);"></i>
                            </div>
                            <h3 style="margin: 0 0 8px 0; font-size: 18px; color: var(--text-dark); font-weight: 700;">No Notices Available</h3>
                            <p style="margin: 0; font-size: 14px; max-width: 250px; line-height: 1.5;">You're all caught up! There are currently no active announcements from the management.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Pagination -->
                <?php if ($total_items > 0): ?>
                <div style="margin-top: auto; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                    <span>Showing <?php echo $start_item; ?> to <?php echo $end_item; ?> of <?php echo $total_items; ?> notices</span>
                    <div style="display: flex; gap: 8px;">
                        <a href="?page=<?php echo max(1, $page - 1); ?>" class="page-btn" style="text-decoration: none;"><i class='bx bx-chevron-left'></i></a>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="?page=<?php echo $p; ?>" class="page-btn <?php echo $p === $page ? 'active' : ''; ?>" style="text-decoration: none;"><?php echo $p; ?></a>
                        <?php endfor; ?>
                        <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="page-btn" style="text-decoration: none;"><i class='bx bx-chevron-right'></i></a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>