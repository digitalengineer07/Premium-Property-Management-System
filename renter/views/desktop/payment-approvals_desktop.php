<?php
// views/desktop/payment-approvals_desktop.php
?>
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark);">Payment Approvals</h1>
                    <p style="margin: 4px 0 0 0; color: var(--text-gray); font-size: 14px;">Track your cash and UPI payment verifications</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-primary" style="display: flex; align-items: center; gap: 8px; margin-right: 12px; background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer;" onclick="openApprovalModal()">
                    <i class='bx bx-plus'></i> Apply for Approval
                </button>
                <div class="notification-wrapper" style="position: relative; display: inline-block;">
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
                        <div style="max-height: 350px;">
                            <?php if (empty($unread_notifications)): ?>
                                <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                    <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p style="margin: 0; font-size: 13px;">You're all caught up!</p>
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
                                                    <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--text-dark); padding-right: 8px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
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
                            <h4><?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                        </div>
                        <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                    </div>
                    
                    <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: var(--white); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 13px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                            <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                        </a>
                        <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 13px; font-weight: 500; transition: 0.2s;">
                            <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>


        <?php if (!empty($payment_success)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 2px; font-weight: 600;">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i> <?php echo $payment_success; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment_error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 2px; font-weight: 600;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo $payment_error; ?>
            </div>
        <?php endif; ?>

        <div class="approvals-table-container">
            <?php if (count($approvals) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Bill Month</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Ref No / UTR</th>
                        <th>Status</th>
                        <th>Admin Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvals as $ap): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?php echo date('d M Y', strtotime($ap['created_at'])); ?></div>
                            <div style="font-size: 12px; color: var(--text-gray); margin-top: 4px;"><?php echo date('h:i A', strtotime($ap['created_at'])); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-gray);">
                                <?php echo !empty($ap['month']) ? htmlspecialchars($ap['month']) : '-'; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--primary-purple);">&#8377;<?php echo number_format($ap['amount'], 2); ?></div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                                <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px; width: 40px; object-fit: contain;">
                                <?php else: ?>
                                    <i class='bx bx-money' style="color: #10B981; font-size: 18px;"></i> Cash
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($ap['transaction_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 6px; border-radius: 6px; font-size: 11px; color: var(--text-gray); white-space: nowrap; display: inline-block;">
                                    <?php echo htmlspecialchars($ap['transaction_id']); ?>
                                </span>
                            <?php elseif (!empty($ap['sys_tx_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 6px; border-radius: 6px; font-size: 11px; color: var(--text-gray); white-space: nowrap; display: inline-block;">
                                    <?php echo htmlspecialchars($ap['sys_tx_id']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($ap['status']); ?>">
                                <?php if ($ap['status'] == 'Pending'): ?>
                                    <i class='bx bx-time'></i>
                                <?php elseif ($ap['status'] == 'Approved'): ?>
                                    <i class='bx bx-check-double'></i>
                                <?php else: ?>
                                    <i class='bx bx-x'></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ap['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($ap['admin_note'])): ?>
                                <div style="font-size: 13px; color: var(--text-gray); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ap['admin_note']); ?>">
                                    "<?php echo htmlspecialchars($ap['admin_note']); ?>"
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 16px; padding: 16px 0; border-top: 1px solid var(--border);">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-left'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-left'></i></span>
                    <?php endif; ?>
                    
                    <span style="font-size: 14px; font-weight: 800; color: var(--text-dark); min-width: 24px; text-align: center;"><?php echo $page; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-right'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-right'></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php else: ?>
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: rgba(98, 75, 255, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                        <i class='bx bx-check-shield' style="font-size: 40px; color: var(--primary-purple);"></i>
                    </div>
                    <h3 style="margin: 0 0 8px 0; color: var(--text-dark); font-size: 18px;">No Approval Requests</h3>
                    <p style="margin: 0; color: var(--text-gray); font-size: 13px;">You haven't submitted any payment verifications yet.</p>
                </div>
            <?php endif; ?>
        </div>