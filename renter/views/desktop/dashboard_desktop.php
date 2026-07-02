<?php
// EXCLUSIVE DESKTOP VIEW FOR DASHBOARD.PHP
?>
<!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting">
                <h1>Hello, <?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?> 👋</h1>
                <p>Welcome back! You're assigned to <span>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></span></p>
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
                <a href="queries.php" class="btn-outline-support">
                    <i class='bx bx-help-circle'></i> Help & Support
                </a>
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

        <!-- Alerts -->
        <?php if (!empty($payment_success)): ?>
            <div id="paymentStatusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
                <i class='bx bx-check-circle'></i> <?php echo $payment_success; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('paymentStatusAlert');
                    if(el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-10px)';
                        setTimeout(() => el.remove(), 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>
        <?php if (!empty($payment_error)): ?>
            <div id="paymentErrorAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
                <i class='bx bx-error-circle'></i> <?php echo $payment_error; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('paymentErrorAlert');
                    if(el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-10px)';
                        setTimeout(() => el.remove(), 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Payment Reminder Banner -->
        <?php if ($show_banner): ?>
        <div class="reminder-banner animate-up">
            <div class="reminder-content">
                <div class="reminder-icon">
                    <i class='bx bxs-bell-ring bx-tada'></i>
                </div>
                <div class="reminder-text">
                    <h3>Payment Reminder!</h3>
                    <p>It's the <?php echo date('jS'); ?> of the month. Your bills for <?php echo implode(', ', array_unique($overdue_list)); ?> are still pending.<br>Please clear them to avoid service interruptions.</p>
                </div>
            </div>
            <button onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')" class="btn-pay-now">
                Pay Now <i class='bx bx-right-arrow-alt'></i>
            </button>
            <i class='bx bxs-calendar reminder-bg-art'></i>
        </div>
        <?php endif; ?>

        <!-- 3-Col KPI Cards -->
        <div class="kpi-grid animate-up">
            <!-- Total Outstanding -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box <?php echo $total_due > 0 ? 'red' : 'green'; ?>" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-credit-card'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Total Outstanding</div>
                        <div class="kpi-amount" style="margin-bottom: 0; <?php echo $total_due > 0 ? 'color: #FF4B6B;' : ''; ?>"><?php echo money($total_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <?php if ($total_due > 0): ?>
                        <div class="kpi-tag alert"><i class='bx bx-error-circle'></i> Payment Due</div>
                    <?php else: ?>
                        <div class="kpi-tag success"><i class='bx bx-check-circle'></i> All Clear</div>
                    <?php endif; ?>
                    <button class="btn-pay-now-trigger" onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')" style="display:none;"></button>
                </div>
                <svg class="kpi-sparkline <?php echo $total_due > 0 ? 'red' : 'green'; ?>" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradRed" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#FF4B6B;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#FF4B6B;stop-opacity:0" />
                        </linearGradient>
                        <linearGradient id="gradGreen" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#10B981;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#10B981;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L10,30 L20,33 L30,25 L40,30 L50,20 L60,23 L70,15 L80,17 L90,10 L100,5 L100,40 L0,40 Z" fill="url(#<?php echo $total_due > 0 ? 'gradRed' : 'gradGreen'; ?>)" />
                    <path d="M0,35 L10,30 L20,33 L30,25 L40,30 L50,20 L60,23 L70,15 L80,17 L90,10 L100,5" fill="none" stroke="<?php echo $total_due > 0 ? '#FF4B6B' : '#10B981'; ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Electricity Due -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box yellow" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-bolt-circle'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Electricity Due</div>
                        <div class="kpi-amount" style="margin-bottom: 0;"><?php echo money($elec_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <div class="kpi-due-date"><i class='bx bx-calendar'></i> Due Date: <?php echo date('t M Y'); ?></div>
                </div>
                <svg class="kpi-sparkline yellow" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradYellow" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#F59E0B;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#F59E0B;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L15,33 L30,27 L45,30 L60,20 L75,23 L90,13 L100,5 L100,40 L0,40 Z" fill="url(#gradYellow)" />
                    <path d="M0,35 L15,33 L30,27 L45,30 L60,20 L75,23 L90,13 L100,5" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Rent Due -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box purple" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-home'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Rent Due</div>
                        <div class="kpi-amount" style="margin-bottom: 0;"><?php echo money($rent_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <div class="kpi-due-date"><i class='bx bx-calendar'></i> Due Date: 05 <?php echo date('M Y', strtotime('+1 month')); ?></div>
                </div>
                <svg class="kpi-sparkline purple" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradPurple" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5 L100,40 L0,40 Z" fill="url(#gradPurple)" />
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5" fill="none" stroke="#8B5CF6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- 3-Col Main Dashboard Grid -->
        <div class="dashboard-3col animate-up">
            <!-- Col 1: Upcoming Bills -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-calendar-event'></i> Upcoming Bills</h3>
                    <a href="my-bills.php#all-bills-container" class="panel-link">View All</a>
                </div>
                
                <div style="display: flex; flex-direction: column; flex: 1;">
                    <?php 
                    $pending_bills_display = [];
                    foreach ($merged_rents as $r) {
                        if (isset($r['status']) && $r['status'] == 'Due') {
                            $pending_bills_display[] = ['type' => 'rent', 'month' => $r['month'], 'amount' => $r['amount']];
                        }
                    }
                    foreach ($elecs as $e) {
                        if (isset($e['status']) && $e['status'] == 'Due') {
                            $pending_bills_display[] = ['type' => 'elec', 'month' => $e['month'], 'amount' => $e['amount']];
                        }
                    }
                    $pending_bills_display = array_slice($pending_bills_display, 0, 3);
                    ?>

                    <?php if (empty($pending_bills_display)): ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px; margin: auto;">
                        <i class='bx bx-check-circle' style="font-size: 32px; color: #10B981; margin-bottom: 8px;"></i><br>
                        No upcoming bills! You're all caught up.
                    </div>
                    <?php else: ?>
                        <?php foreach($pending_bills_display as $pb): ?>
                        <div class="bill-item">
                            <div class="bill-left">
                                <?php if ($pb['type'] == 'rent'): ?>
                                    <div class="bill-icon"><i class='bx bx-home'></i></div>
                                <?php else: ?>
                                    <div class="bill-icon yellow"><i class='bx bx-bolt-circle'></i></div>
                                <?php endif; ?>
                                <div class="bill-info">
                                    <h4><?php echo $pb['type'] == 'rent' ? 'Rent' : 'Electricity'; ?> for <?php echo htmlspecialchars($pb['month']); ?></h4>
                                    <p>Due Date: <?php 
                                        $ts = strtotime($pb['month']);
                                        if ($pb['type'] == 'rent') {
                                            echo '05 ' . date('M Y', strtotime('+1 month', $ts));
                                        } else {
                                            echo date('t M Y', $ts);
                                        }
                                    ?></p>
                                </div>
                            </div>
                            <div class="bill-right">
                                <h4 <?php echo $pb['type'] == 'elec' ? 'style="color: #F59E0B;"' : ''; ?>><?php echo money($pb['amount']); ?></h4>
                                <p <?php echo $pb['type'] == 'elec' ? 'style="color: #F59E0B;"' : ''; ?>>Pending</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <a href="my-bills.php#all-bills-container" class="btn-view-all">View All Bills</a>
                </div>
            </div>

            <!-- Col 2: Quick Actions -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-zap'></i> Quick Actions</h3>
                </div>
                <div class="quick-actions-grid">
                    <?php if ($total_due > 0): ?>
                    <a href="#" class="action-card" onclick="openPaymentModal(<?php echo (float)$total_due; ?>, '<?php echo $elec_due > 0 ? "Rent + Main. + Electricity" : "Rent + Main."; ?>', 'total'); return false;">
                        <div class="action-icon"><i class='bx bx-credit-card-alt'></i></div>
                        <h4>Pay Dues</h4>
                        <p>Make secure payments</p>
                    </a>
                    <?php else: ?>
                    <a href="#" class="action-card disabled" style="opacity: 0.55; cursor: not-allowed; pointer-events: none; background: #F3F4F6;" onclick="return false;" title="All dues are paid">
                        <div class="action-icon" style="background: #E5E7EB; color: #9CA3AF;"><i class='bx bx-check-shield'></i></div>
                        <h4>Pay Dues</h4>
                        <p style="color: #10B981; font-weight: 600;">All Paid</p>
                    </a>
                    <?php endif; ?>
                    <a href="payment-history.php" class="action-card">
                        <div class="action-icon"><i class='bx bx-history'></i></div>
                        <h4>Payment History</h4>
                        <p>View all transactions</p>
                    </a>
                    <a href="electricity-record.php" class="action-card">
                        <div class="action-icon"><i class='bx bx-bolt-circle'></i></div>
                        <h4>Electricity Record</h4>
                        <p>View meter readings</p>
                    </a>
                    <a href="queries.php" class="action-card">
                        <div class="action-icon"><i class='bx bx-message-square-dots'></i></div>
                        <h4>Raise Query</h4>
                        <p>Ask or report issue</p>
                    </a>
                </div>
            </div>

            <!-- Col 3: Recent Transactions -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-receipt'></i> Recent Transactions</h3>
                    <a href="payment-history.php" class="panel-link">View All</a>
                </div>
                <div class="transaction-list" style="overflow-y: auto; max-height: 250px;">
                    <?php if (empty($merged_rents) && empty($elecs)): ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-gray); font-size: 13px; margin: auto;">No recent transactions found.</div>
                    <?php else: ?>
                        <?php 
                        // Combine and filter to get only Paid transactions
                        $all_tx = array_filter(array_merge($merged_rents, $elecs), function($tx) {
                            return isset($tx['status']) && $tx['status'] === 'Paid';
                        });
                        
                        // Sort by payment_date descending, fallback to id descending
                        usort($all_tx, function($a, $b) {
                            $timeA = !empty($a['payment_date']) ? strtotime($a['payment_date']) : 0;
                            $timeB = !empty($b['payment_date']) ? strtotime($b['payment_date']) : 0;
                            if ($timeA == $timeB) {
                                return $b['id'] - $a['id'];
                            }
                            return $timeB - $timeA;
                        });
                        
                        $display_tx = array_slice($all_tx, 0, 5); 
                        foreach($display_tx as $tx):
                            $is_paid = ($tx['status'] == 'Paid');
                            $is_elec = (isset($tx['source']) && $tx['source'] == 'elec_table');
                            $is_adv = (isset($tx['source']) && $tx['source'] == 'advance');
                            
                            $icon_class = 'up';
                            $icon_bx = 'bx-up-arrow-alt';
                            if ($is_elec) { $icon_class = 'elec'; $icon_bx = 'bx-bolt-circle'; }
                            else if ($is_adv) { $icon_class = 'adv'; $icon_bx = 'bx-wallet'; }
                            else { $icon_class = 'up'; $icon_bx = 'bx-up-arrow-alt'; }
                            
                            $title = 'Rent Payment';
                            if ($is_elec) $title = 'Electricity Payment';
                            if ($is_adv) $title = 'Advance Payment';
                            if (!isset($tx['source'])) $title = 'Electricity Payment'; // from $elecs array
                        ?>
                        <div class="transaction-item">
                            <div class="tx-left">
                                <div class="tx-icon <?php echo $icon_class; ?>"><i class='bx <?php echo $icon_bx; ?>'></i></div>
                                <div class="tx-info">
                                    <h4><?php echo $title; ?></h4>
                                    <p>For <?php echo htmlspecialchars($tx['month']); ?></p>
                                </div>
                            </div>
                            <div class="tx-right">
                                <div class="tx-amount <?php echo $is_paid ? '' : 'pending'; ?>"><?php echo money($tx['amount']); ?></div>
                                <div class="tx-status <?php echo $is_paid ? 'paid' : 'pending'; ?>"><?php echo htmlspecialchars($tx['status']); ?></div>
                                <div class="tx-date"><?php echo !empty($tx['payment_date']) ? date('d M Y', strtotime($tx['payment_date'])) : '-'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Widgets -->
        <div class="footer-widgets animate-up">
            <div class="footer-widget">
                <div class="fw-left">
                    <div class="fw-icon help"><i class='bx bx-headphone'></i></div>
                    <div class="fw-info">
                        <h4>Need Help?</h4>
                        <p>Our support team is available 24/7 to assist you.</p>
                    </div>
                </div>
                <button class="btn-fw" onclick="window.location.href='queries.php'"><i class='bx bx-message-rounded-dots'></i> Contact Support</button>
            </div>
            
            <div class="footer-widget">
                <div class="fw-left">
                    <div class="fw-icon bell"><i class='bx bx-bell'></i></div>
                    <div class="fw-info">
                        <h4>Stay Updated</h4>
                        <p>Enable notifications to never miss any updates.</p>
                    </div>
                </div>
                <button class="btn-fw">Enable Notifications</button>
            </div>
        </div>

        <!-- App Footer -->
        <div class="app-footer">
            <p>© 2026 <?php echo htmlspecialchars(HOUSE_NAME); ?>. All rights reserved.</p>
            <p>Last updated: <?php echo date('d M Y, h:i A'); ?> <i class='bx bx-refresh' style="cursor:pointer;" onclick="location.reload()"></i></p>
        </div>