<?php
// EXCLUSIVE DESKTOP VIEW FOR DASHBOARD.PHP
?>
<!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting">
                <h1>Hello, <?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?> 👋</h1>
                <p>Welcome back! You're assigned to <span>Room <?php echo htmlspecialchars($room_no); ?></span></p>
            </div>
            <div class="header-actions">
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

        <?php if ($onboarding_due > 0): ?>
        <div class="reminder-banner animate-down" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); margin-bottom: 24px;">
            <div class="reminder-content">
                <div class="reminder-icon">
                    <i class='bx bx-user-plus bx-tada'></i>
                </div>
                <div class="reminder-text">
                    <h3>Welcome! Initial Onboarding Dues</h3>
                    <p>Please clear your initial Security Deposit and/or Advance Rent to complete your onboarding process.</p>
                </div>
            </div>
            <button onclick="openPaymentModal(<?php echo (float)$onboarding_due; ?>, 'Onboarding Security & Advance', 'onboarding')" class="btn-pay-now">
                Pay ₹<?php echo number_format($onboarding_due); ?> <i class='bx bx-right-arrow-alt'></i>
            </button>
            <i class='bx bx-shield-quarter reminder-bg-art'></i>
        </div>
        <?php endif; ?>

        <!-- 3/4-Col KPI Cards -->
        <div class="kpi-grid animate-up" style="display: grid; grid-template-columns: repeat(<?php echo ($user['advance_payment'] ?? 0) > 0 ? 4 : 3; ?>, 1fr); gap: 24px;">
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
            
            <?php if (($user['advance_payment'] ?? 0) > 0): ?>
            <!-- Advance Wallet -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box green" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-wallet'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Advance Wallet</div>
                        <div class="kpi-amount" style="margin-bottom: 0; color: #10B981;"><?php echo money($user['advance_payment']); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <div class="kpi-due-date"><i class='bx bx-check-shield'></i> Safe & Available</div>
                </div>
                <svg class="kpi-sparkline green" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradGreen2" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#10B981;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#10B981;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5 L100,40 L0,40 Z" fill="url(#gradGreen2)" />
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <?php endif; ?>
        </div>

        <!-- 2-Col Main Dashboard Grid -->
        <div class="dashboard-2col animate-up">
            <!-- Col 1: Upcoming Bills -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-calendar-event'></i> Upcoming Bills</h3>
                    <a href="my-bills.php#all-bills-container" class="panel-link">View All</a>
                </div>
                
                <div style="display: flex; flex-direction: column; flex: 1">
                    <?php 
                    $pb_raw = [];
                    // Fetch accurate pending dues from rent
                    $qR = mysqli_query($conn, "SELECT id, month, due_date, rent_amount as total_amount FROM rent WHERE user_id=$user_id AND status IN ('Due', 'Partial')");
                    while ($r = mysqli_fetch_assoc($qR)) {
                        $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='rent' AND bill_id={$r['id']}");
                        $paid = (float)(mysqli_fetch_assoc($qPaid)['tp'] ?? 0);
                        $due = max(0, (float)$r['total_amount'] - $paid);
                        if ($due > 0) $pb_raw[] = ['month' => $r['month'], 'due_date' => $r['due_date'], 'due' => $due];
                    }
                    // Fetch accurate pending dues from electricity (unified)
                    $qE = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance + dues + extra_charges) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");
                    while ($r = mysqli_fetch_assoc($qE)) {
                        $qEPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='electricity' AND bill_id={$r['id']}");
                        $epaid = (float)(mysqli_fetch_assoc($qEPaid)['tp'] ?? 0);
                        $edue = max(0, (float)$r['elec_part'] - $epaid);
                        if ($edue > 0) $pb_raw[] = ['month' => $r['month'], 'due_date' => $r['due_date'], 'due' => $edue];
                        
                        $qRPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='elec_rent' AND bill_id={$r['id']}");
                        $rpaid = (float)(mysqli_fetch_assoc($qRPaid)['tp'] ?? 0);
                        $rdue = max(0, (float)$r['rent_part'] - $rpaid);
                        if ($rdue > 0) $pb_raw[] = ['month' => $r['month'], 'due_date' => $r['due_date'], 'due' => $rdue];
                    }
                    
                    // Group by month
                    $grouped_bills = [];
                    foreach ($pb_raw as $pb) {
                        if (!isset($grouped_bills[$pb['month']])) {
                            $grouped_bills[$pb['month']] = ['month' => $pb['month'], 'due' => 0, 'due_date' => $pb['due_date']];
                        }
                        $grouped_bills[$pb['month']]['due'] += $pb['due'];
                    }
                    
                    // Sort chronologically
                    usort($grouped_bills, function($a, $b) {
                        return strtotime($a['month'].'-01') - strtotime($b['month'].'-01');
                    });
                    
                    $pending_bills_display = array_slice($grouped_bills, 0, 3);
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
                                <div class="bill-icon"><i class='bx bx-receipt'></i></div>
                                <div class="bill-info">
                                    <h4>Total Bill for <?php echo htmlspecialchars($pb['month']); ?></h4>
                                    <p>Due Date: <?php echo date('d M Y', strtotime($pb['due_date'])); ?></p>
                                </div>
                            </div>
                            <div class="bill-right">
                                <h4><?php echo money($pb['due']); ?></h4>
                                <p>Pending</p>
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
        </div>

        <!-- Full Width: Recent Transactions -->
        <div class="dash-panel animate-up" style="margin-bottom: 32px;">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-receipt'></i> Recent Transactions</h3>
                    <a href="payment-history.php" class="panel-link">View All</a>
                </div>
                <div class="transaction-list" style="overflow-y: auto; max-height: 250px;">
                    <?php 
                    $payments_recent_q = mysqli_query($conn, "SELECT id, bill_type, month, paid_amount as amount, payment_date, 'Paid' as status, sys_tx_id, verification_hash FROM payments WHERE user_id = $user_id ORDER BY id DESC LIMIT 5");
                    $display_tx = [];
                    while($pt = mysqli_fetch_assoc($payments_recent_q)) {
                        $display_tx[] = $pt;
                    }
                    if (empty($display_tx)): ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-gray); font-size: 13px; margin: auto;">No recent transactions found.</div>
                    <?php else: ?>
                        <?php foreach($display_tx as $tx):
                            $is_paid = true;
                            $is_elec = ($tx['bill_type'] == 'electricity' || $tx['bill_type'] == 'total' || $tx['bill_type'] == 'elec_rent');
                            $is_adv = ($tx['bill_type'] == 'advance');
                            
                            $icon_class = 'up';
                            $icon_bx = 'bx-up-arrow-alt';
                            if ($is_elec) { $icon_class = 'elec'; $icon_bx = 'bx-bolt-circle'; }
                            else if ($is_adv) { $icon_class = 'adv'; $icon_bx = 'bx-wallet'; }
                            
                            $title = 'Rent Payment';
                            if ($is_elec) $title = 'Electricity Payment';
                            if ($is_adv) $title = 'Advance Payment';
                            
                            $is_corrupted = false;
                            if (!empty($tx['verification_hash'])) {
                                $expected_hash = generate_payment_hash($user_id, $tx['amount'], $tx['sys_tx_id']);
                                if ($tx['verification_hash'] !== $expected_hash) {
                                    $is_corrupted = true;
                                    $title .= ' <span style="color:var(--danger); font-size: 11px;"><i class="bx bx-error-circle"></i> CORRUPTED</span>';
                                }
                            }
                        ?>
                        <div class="transaction-item <?php echo $is_corrupted ? 'corrupted' : ''; ?>">
                            <div class="tx-left">
                                <div class="tx-icon <?php echo $icon_class; ?>"><i class='bx <?php echo $icon_bx; ?>'></i></div>
                                <div class="tx-info">
                                    <h4><?php echo $title; ?></h4>
                                    <p>For <?php echo htmlspecialchars($tx['month']); ?></p>
                                </div>
                            </div>
                            <div class="tx-right">
                                <div class="tx-amount"><?php echo $is_corrupted ? '<s>' . money($tx['amount']) . '</s>' : money($tx['amount']); ?></div>
                                <div class="tx-status paid"><?php echo htmlspecialchars($tx['status']); ?></div>
                                <div class="tx-date"><?php echo !empty($tx['payment_date']) ? date('d M Y', strtotime($tx['payment_date'])) : '-'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
        <div class="app-footer" style="display: flex !important; flex-direction: row !important; justify-content: space-between !important; margin-top: auto !important; padding-top: 20px !important; padding-bottom: 0px !important;">
            <p style="text-align: left; margin: 0;">© 2026 <?php echo htmlspecialchars(HOUSE_NAME); ?>. All rights reserved.</p>
            <p>Last updated: <?php echo date('d M Y, h:i A'); ?> <i class='bx bx-refresh' style="cursor:pointer;" onclick="location.reload()"></i></p>
        </div>
