<?php
// EXCLUSIVE DESKTOP VIEW FOR MY-BILLS.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-receipt' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">My Bills</h1>
                    <p style="margin: 0;">View your upcoming and past bills.</p>
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

        <!-- Alerts -->
        <?php if (!empty($payment_success)): ?>
            <div id="paymentStatusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-top: 20px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
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
            <div id="paymentErrorAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-top: 20px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
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

        <?php
        // Prepare all bills data
        $all_bills = [];

        // 1. Pure Rent
        $rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status, COALESCE(p.payment_date, r.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = r.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM rent r LEFT JOIN payments p ON p.bill_type='rent' AND p.bill_id=r.id 
                                       WHERE r.user_id=$user_id");
        while($r = mysqli_fetch_assoc($rent_q)) {
            $all_bills[] = [
                'id' => $r['id'], 'type' => 'rent', 'filter_type' => ($r['status'] == 'Paid' ? 'paid' : ($r['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $r['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $r['month'],
                'bill_date' => date('01 M Y', strtotime($r['month'])),
                'due_date' => date('07 M Y', strtotime($r['month'])),
                'amount' => $r['amount'], 'status' => $r['status'] == 'Due' ? 'Unpaid' : $r['status'],
                'paid_on' => $r['payment_date'] ? date('d M Y', strtotime($r['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $r['amount'],
                    'Maintenance Charge' => 0,
                    'Other Charges' => 0
                ]
            ];
        }

        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => ($e['status'] == 'Paid' ? 'paid' : ($e['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Electricity for ' . $e['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 'status' => $e['status'] == 'Due' ? 'Unpaid' : $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow',
                'summary' => [
                    'Electricity Usage' => $e['amount'],
                    'Maintenance Charge' => 0,
                    'Other Charges' => 0
                ]
            ];
        }

        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, (e.rent_amount + e.maintenance + e.dues) as combined_amount, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : ($m['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $m['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $m['rent_amount'],
                    'Maintenance Charge' => $m['maintenance'],
                    'Other Charges' => $m['dues']
                ]
            ];
        }
        
        // Sort by Period Descending
        usort($all_bills, function($a, $b) { 
            return strtotime($b['bill_date']) - strtotime($a['bill_date']);
        });
        
        // Compute KPIs
        $paid_this_year = 0;
        $bills_paid_count = 0;
        foreach($all_bills as $b) {
            if ($b['status'] == 'Paid') {
                $paid_this_year += $b['amount'];
                $bills_paid_count++;
            }
        }
        $due_this_month = $total_due; 
        ?>

        <!-- 4-Col KPI Grid -->
        <div class="kpi-grid-4 animate-up" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
            <div class="kpi-card-minimal" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-receipt'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Total Outstanding</h4>
                    <h2 style="font-size: 24px; color: #FF4B6B; margin: 0 0 6px 0; font-weight: 800;"><?php echo money($total_due); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #FF4B6B; background: rgba(255,75,107,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block; white-space: nowrap;">Payment Due</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-calendar-event'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Due This Month</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo money($due_this_month); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #F59E0B; background: rgba(245,158,11,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block; white-space: nowrap;">Due on 05 <?php echo date('M Y'); ?></div>
                </div>
            </div>

            <div class="kpi-card-minimal" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-check-circle'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Paid This Year</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo money($paid_this_year); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #10B981; background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block;"><?php echo $bills_paid_count; ?> Bills Paid</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-receipt'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Total Bills</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo count($all_bills); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #8B5CF6; background: rgba(139,92,246,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block;">All Time</div>
                </div>
            </div>
        </div>

        <div id="all-bills-container" class="my-bills-container animate-up" style="animation-delay: 0.1s; display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(290px, 1fr); gap: 24px; align-items: stretch;">
            <!-- Left Column: Bills List -->
            <div class="bills-list-panel" style="display: flex; flex-direction: column; gap: 0; background: var(--white); border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="tabs-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: transparent; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; gap: 24px;">
                        <button type="button" class="tab-btn active" data-filter="all" style="background: none; border: none; border-bottom: 2px solid var(--primary-purple); color: var(--primary-purple); font-weight: 700; padding-bottom: 8px; cursor: pointer; font-size: 14px;">All Bills</button>
                        <button type="button" class="tab-btn" data-filter="unpaid" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Unpaid</button>
                        <button type="button" class="tab-btn" data-filter="paid" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Paid</button>
                        <button type="button" class="tab-btn" data-filter="overdue" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Overdue</button>
                    </div>
                    <div class="tab-actions" style="display: flex; gap: 12px;">
                        <select class="filter-select" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; color: var(--text-dark); outline: none;">
                            <option>All Years</option>
                            <option>2025</option>
                            <option>2026</option>
                        </select>
                        <button class="btn-filter" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; color: var(--primary-purple); background: var(--white); cursor: pointer; display: flex; align-items: center; gap: 6px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>
                
                <div style="padding: 0 12px 12px; overflow-x: hidden;"><table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0 10px; margin-top: -10px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 12px 8px 12px 14px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; border-top-left-radius: 12px; border-bottom-left-radius: 12px; width: 25%;">BILL FOR</th>
                            <th style="text-align: left; padding: 12px 6px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; width: 16%;">BILL TYPE</th>
                            <th style="text-align: left; padding: 12px 6px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; width: 15%;">DUE DATE</th>
                            <th style="text-align: right; padding: 12px 8px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; width: 15%;">AMOUNT</th>
                            <th style="text-align: center; padding: 12px 6px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; width: 14%;">STATUS</th>
                            <th style="text-align: center; padding: 12px 14px 12px 6px; font-size: 10.5px; color: var(--text-gray); text-transform: uppercase; font-weight: 700; white-space: nowrap; border-top-right-radius: 12px; border-bottom-right-radius: 12px; width: 15%;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="billsTableBody">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
                </div><div style="margin-top: auto; padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px;">
                    <span id="showingText">Showing 1 to 6 of 14 bills</span>
                    <div id="paginationControls" style="display: flex; gap: 4px;"></div>
                </div>
            </div>

            <!-- Right Column: Bill Details -->
            <div class="bill-details-panel" style="background: var(--white); border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(0,0,0,0.04); padding: 32px; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--text-dark);">Bill Details</h3>
                    <span id="bdStatus" style="font-size: 11px; font-weight: 700; padding: 6px 16px; border-radius: 20px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Unpaid</span>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div id="bdIcon" style="width: 40px; height: 40px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class='bx bx-home'></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 id="bdTitle" style="margin: 0 0 2px 0; font-size: 12px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Rent for February 2026</h4>
                        <p id="bdSubtitle" style="margin: 0; font-size: 11px; color: var(--text-gray); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Room 201</p>
                    </div>
                    <div style="margin-left: auto; text-align: right; flex-shrink: 0;">
                        <p style="margin: 0 0 2px 0; font-size: 10px; color: var(--text-gray);">Due Date</p>
                        <h4 id="bdDueDate" style="margin: 0; font-size: 12px; font-weight: 700; color: #FF4B6B;">05 Feb 2026</h4>
                    </div>
                </div>

                <div class="bd-total-box" style="background: var(--bg-main); border-radius: 16px; padding: 16px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; gap: 12px; border: 1px solid var(--border);">
                    <div style="min-width: 0;">
                        <p style="margin: 0 0 4px 0; font-size: 12px; color: var(--text-gray); font-weight: 500;">Total Amount</p>
                        <h2 id="bdAmount" style="margin: 0; font-size: 20px; font-weight: 800; color: #FF4B6B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">₹8,000.00</h2>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;">
                        <button id="bdBtnPay" onclick="" style="background: var(--primary-purple); color: white; border: none; padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2); white-space: nowrap;"><i class='bx bx-credit-card'></i> Pay Now</button>
                        <button id="bdBtnDownload" style="background: var(--white); color: var(--primary-purple); border: 1px solid rgba(98, 75, 255, 0.2); padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap;"><i class='bx bx-download'></i> Download Bill</button>
                    </div>
                </div>

                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 700; color: var(--text-dark);">Bill Summary</h4>
                <div id="bdSummaryList" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                    <!-- Rendered by JS -->
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px dashed var(--border); margin-bottom: 24px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--text-dark);">Total Amount</h4>
                    <h4 id="bdTotalAmount2" style="margin: 0; font-size: 15px; font-weight: 800; color: #FF4B6B;">₹8,000.00</h4>
                </div>

                <div id="bdWarning" style="background: #FFF7ED; border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 16px; display: flex; gap: 12px; align-items: center; margin-top: auto;">
                    <i class='bx bx-error-circle' style="color: #F59E0B; font-size: 20px;"></i>
                    <p style="margin: 0; font-size: 12.5px; color: #B45309; line-height: 1.6; font-weight: 600;">Please clear your dues before the due date to avoid late fees.</p>
                </div>
            </div>
        </div>

        <script>
            const allBills = <?php echo json_encode($all_bills); ?>;
            let currentFilter = 'all';
            let currentPage = 1;
            const itemsPerPage = 5;
            let activeBillId = null;

            function formatMoney(amount) {
                return '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            function selectBill(index) {
                const bill = allBills[index];
                if (!bill) return;
                activeBillId = index;

                // Update UI rows
                document.querySelectorAll('.bill-row').forEach(row => {
                    row.classList.remove('active');
                });
                const activeRow = document.getElementById('bill-row-' + index);
                if (activeRow) {
                    activeRow.classList.add('active');
                }

                // Update Bill Details Panel
                const statusColor = bill.status === 'Unpaid' ? '#FF4B6B' : '#10B981';
                const statusBg = bill.status === 'Unpaid' ? 'rgba(255, 75, 107, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                
                document.querySelectorAll('#bdStatus').forEach(el => el.textContent = bill.status);
                document.querySelectorAll('#bdStatus').forEach(el => el.style.color = statusColor);
                document.querySelectorAll('#bdStatus').forEach(el => el.style.background = statusBg);

                document.querySelectorAll('#bdTitle').forEach(el => el.textContent = bill.title);
                document.querySelectorAll('#bdSubtitle').forEach(el => el.textContent = bill.subtitle);
                document.querySelectorAll('#bdDueDate').forEach(el => el.textContent = bill.due_date);
                document.querySelectorAll('#bdDueDate').forEach(el => el.style.color = bill.status === 'Unpaid' ? '#FF4B6B' : 'var(--text-gray)');

                const iconMap = {'rent': 'bx-home', 'elec_rent': 'bx-home', 'electricity': 'bx-bulb', 'maintenance': 'bx-wrench'};
                const colorMap = {'rent': ['rgba(255, 75, 107, 0.1)', '#FF4B6B'], 'elec_rent': ['rgba(98, 75, 255, 0.1)', 'var(--primary-purple)'], 'electricity': ['rgba(245, 158, 11, 0.1)', '#F59E0B']};
                
                let iconClass = iconMap[bill.type] || 'bx-receipt';
                let colors = colorMap[bill.type] || ['rgba(98, 75, 255, 0.1)', 'var(--primary-purple)'];
                
                document.getElementById('bdIcon').innerHTML = `<i class='bx ${iconClass}'></i>`;
                document.getElementById('bdIcon').style.background = colors[0];
                document.getElementById('bdIcon').style.color = colors[1];

                document.querySelectorAll('#bdAmount').forEach(el => el.textContent = formatMoney(bill.amount));
                document.getElementById('bdAmount').style.color = statusColor;
                document.querySelectorAll('#bdTotalAmount2').forEach(el => el.textContent = formatMoney(bill.amount));
                document.getElementById('bdTotalAmount2').style.color = statusColor;

                // Summary List
                let summaryHtml = '';
                for (const [key, val] of Object.entries(bill.summary)) {
                    summaryHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; color: var(--text-gray); font-weight: 500;">${key}</span>
                            <span style="font-size: 13px; color: var(--text-dark); font-weight: 700;">${formatMoney(val)}</span>
                        </div>
                    `;
                }
                document.querySelectorAll('#bdSummaryList').forEach(el => el.innerHTML = summaryHtml);

                // Buttons
                const btnPay = document.getElementById('bdBtnPay');
                if (bill.status === 'Unpaid') {
                    btnPay.style.display = 'flex';
                    btnPay.onclick = () => openPaymentModal(bill.amount, bill.title, bill.type, bill.id);
                } else {
                    btnPay.style.display = 'none';
                }
            }

            function goToPage(page, e) {
                if(e) e.preventDefault();
                currentPage = page;
                renderTable();
            }

            function renderTable() {
                const tbodies = document.querySelectorAll('#billsTableBody');
                tbody.innerHTML = '';
                
                // Filter bills
                const filteredBills = allBills.filter(bill => currentFilter === 'all' || bill.filter_type === currentFilter);
                const totalItems = filteredBills.length;
                const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
                
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;
                
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
                const currentBills = filteredBills.slice(startIndex, endIndex);
                
                let count = totalItems;
                
                currentBills.forEach((bill) => {
                    const idx = allBills.indexOf(bill);
                    
                    const statusColor = bill.status === 'Unpaid' ? '#FF4B6B' : '#10B981';
                    const statusBg = bill.status === 'Unpaid' ? 'rgba(255, 75, 107, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                    
                    const typeColor = (bill.type === 'rent' || bill.type === 'elec_rent') ? 'var(--primary-purple)' : (bill.type === 'electricity' ? '#F59E0B' : '#3B82F6');
                    const typeBg = (bill.type === 'rent' || bill.type === 'elec_rent') ? 'rgba(98, 75, 255, 0.1)' : (bill.type === 'electricity' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(59, 130, 246, 0.1)');
                    
                    let iconHtml = '';
                    if (bill.type === 'rent' || bill.type === 'elec_rent') iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(98, 75, 255, 0.1);color:var(--primary-purple);display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-home'></i></div>`;
                    else if (bill.type === 'electricity') iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,0.1);color:#F59E0B;display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-bulb'></i></div>`;
                    else iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(59,130,246,0.1);color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-wrench'></i></div>`;

                    
                    let actionBtn = '';
                    if (bill.status === 'Unpaid') {
                        actionBtn = `<button style="background:var(--white); border:1px solid rgba(98,75,255,0.2); color:var(--primary-purple); font-weight:700; font-size:11px; padding:6px 12px; border-radius:8px; cursor:pointer; transition:0.2s;">View Bill</button>`;
                    } else {
                        actionBtn = `<button style="background:var(--white); border:1px solid rgba(98,75,255,0.2); color:var(--primary-purple); font-weight:700; font-size:14px; width: 28px; height: 28px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; cursor:pointer; transition:0.2s;"><i class='bx bx-download'></i></button>`;
                    }

                    const displayTypeLabel = bill.type === 'elec_rent' ? 'Rent + Main.' : bill.type;

                    const rowHtml = `
                        <tr id="bill-row-${idx}" class="bill-row" onclick="selectBill(${idx})">
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    ${iconHtml}
                                    <div>
                                        <h4 style="margin:0 0 2px 0; font-size:12px; font-weight:700; color:var(--text-dark);">${bill.period}</h4>
                                        <p style="margin:0; font-size:10px; color:var(--text-gray); font-weight:500;">${bill.subtitle}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:10px; font-weight:700; color:${typeColor}; background:${typeBg}; padding:4px 8px; border-radius:20px; ${bill.type === 'elec_rent' ? '' : 'text-transform:capitalize;'}">${displayTypeLabel}</span>
                            </td>
                            <td>
                                <p style="margin:0; font-size:11px; font-weight:600; color:var(--text-dark);">${bill.due_date}</p>
                                ${bill.status === 'Unpaid' ? `<p style="margin:2px 0 0 0; font-size:9px; font-weight:700; color:#FF4B6B;">Due Today</p>` : ''}
                            </td>
                            <td style="text-align:right;">
                                <span style="font-size:12px; font-weight:800; color:var(--text-dark);">${formatMoney(bill.amount)}</span>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-size:10px; font-weight:700; color:${statusColor}; background:${statusBg}; padding:4px 10px; border-radius:20px; display:inline-block; min-width: 50px;">${bill.status}</span>
                            </td>
                            <td style="text-align:center;">
                                ${actionBtn}
                            </td>
                        </tr>
                    `;
                    tbodies.forEach(tb => tb.innerHTML += rowHtml);
                });
                
                document.querySelectorAll('#showingText').forEach(el => el.textContent = totalItems > 0 ? `Showing ${startIndex + 1} to ${endIndex} of ${totalItems} bills` : `Showing 0 bills`);
                
                let pagHtml = '';
                if (totalPages > 1) {
                    pagHtml += `<a href="#" onclick="goToPage(${currentPage > 1 ? currentPage - 1 : 1}, event)" class="pagination-purple"><i class='bx bx-chevron-left'></i></a>`;
                    for (let i = 1; i <= totalPages; i++) {
                        pagHtml += `<a href="#" onclick="goToPage(${i}, event)" class="pagination-purple ${i === currentPage ? 'active' : ''}">${i}</a>`;
                    }
                    pagHtml += `<a href="#" onclick="goToPage(${currentPage < totalPages ? currentPage + 1 : totalPages}, event)" class="pagination-purple"><i class='bx bx-chevron-right'></i></a>`;
                }
                document.querySelectorAll('#paginationControls').forEach(el => el.innerHTML = pagHtml);
                
                if (totalItems > 0 && activeBillId === null) {
                    selectBill(allBills.indexOf(currentBills[0]));
                } else if (totalItems > 0) {
                     selectBill(activeBillId); 
                }
            }

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.onclick = (e) => {
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.style.borderBottom = 'none';
                        b.style.color = 'var(--text-gray)';
                    });
                    e.target.style.borderBottom = '2px solid var(--primary-purple)';
                    e.target.style.color = 'var(--primary-purple)';
                    currentFilter = e.target.getAttribute('data-filter');
                    activeBillId = null; 
                    currentPage = 1;
                    renderTable();
                };
            });

            // Initial render
            document.addEventListener('DOMContentLoaded', () => {
                renderTable();
            });
        </script>
        
      <?php include 'payment_modal.php'; ?>