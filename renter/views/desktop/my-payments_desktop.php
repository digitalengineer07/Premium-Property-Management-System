<?php
// EXCLUSIVE DESKTOP VIEW FOR MY-PAYMENTS.PHP
?>
<!-- Top Header -->
        <header class="top-header" style="padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 24px;">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);">
                    <i class='bx bx-wallet-alt' style="font-size: 28px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="font-size: 28px; font-weight: 800; letter-spacing: -0.5px; color: var(--text-dark); margin: 0 0 6px 0; display: flex; align-items: center; gap: 12px;">
                        My Payments
                        <?php if ($total_due == 0): ?>
                            <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 20px; letter-spacing: 0.5px;">ALL CLEAR</span>
                        <?php else: ?>
                            <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B; border-radius: 20px; letter-spacing: 0.5px;">DUES PENDING</span>
                        <?php endif; ?>
                    </h1>
                    <p style="font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;">View and manage all your bills and payments in one place.</p>
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
                <a href="payment-history.php" class="btn-outline-support" style="border-color: rgba(16, 185, 129, 0.2); color: #10B981; background: rgba(16, 185, 129, 0.05);">
                    <i class='bx bx-history'></i> Payment History
                </a>
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
                            <h4><?php echo htmlspecialchars(explode(' ', trim($display_name))[0]); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no); ?></p>
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
                'id' => $r['id'], 'type' => 'rent', 'filter_type' => 'rent',
                'title' => 'Rent', 'subtitle' => 'Room ' . $room_no,
                'period' => $r['month'],
                'bill_date' => date('01 M Y', strtotime($r['month'])),
                'due_date' => date('07 M Y', strtotime($r['month'])),
                'amount' => $r['amount'], 'status' => $r['status'],
                'paid_on' => $r['payment_date'] ? date('d M Y', strtotime($r['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple'
            ];
        }

        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => 'electricity',
                'title' => 'Electricity', 'subtitle' => 'Units: ' . $e['units_consumed'],
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 'status' => $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow'
            ];
        }

        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as combined_amount, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple'
            ];
        }

        // 4. Advance Payments
        $adv_q = mysqli_query($conn, "SELECT p.id, p.month, p.paid_amount as amount, p.payment_date 
                                      FROM payments p 
                                      WHERE p.user_id=$user_id AND p.bill_type='advance'");
        while($a = mysqli_fetch_assoc($adv_q)) {
            $all_bills[] = [
                'id' => $a['id'], 'type' => 'advance', 'filter_type' => 'other',
                'title' => 'Advance', 'subtitle' => $a['month'],
                'period' => $a['month'],
                'bill_date' => date('d M Y', strtotime($a['payment_date'])),
                'due_date' => date('d M Y', strtotime($a['payment_date'])),
                'amount' => $a['amount'], 'status' => 'Paid',
                'paid_on' => date('d M Y', strtotime($a['payment_date'])),
                'icon' => 'bx-file', 'color' => 'blue'
            ];
        }

        // Sort by Period Descending, then by Bill Date Descending
        usort($all_bills, function($a, $b) { 
            $t1 = strtotime($b['period']);
            $t2 = strtotime($a['period']);
            if ($t1 == $t2) {
                return strtotime($b['bill_date']) - strtotime($a['bill_date']);
            }
            return $t1 - $t2;
        });
        ?>

        <!-- 4-Col KPI Grid -->
        <div class="kpi-grid-4 animate-up">
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;"><i class='bx bx-credit-card'></i></div>
                <div class="kpi-min-info">
                    <h4>Total Outstanding</h4>
                    <h2 style="<?php echo $total_due > 0 ? 'color: #FF4B6B;' : ''; ?>"><?php echo money($total_due); ?></h2>
                    <?php if ($total_due > 0): ?>
                        <div class="kpi-min-tag" style="background: rgba(255, 75, 107, 0.08); color: #FF4B6B;">Payment Due</div>
                    <?php else: ?>
                        <div class="kpi-min-tag" style="background: rgba(16, 185, 129, 0.08); color: #10B981;">All Clear</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><i class='bx bx-bulb'></i></div>
                <div class="kpi-min-info">
                    <h4>Electricity Due</h4>
                    <h2><?php echo money($elec_due); ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(245, 158, 11, 0.08); color: #F59E0B;">Due on 10 <?php echo date('M Y', strtotime('+1 month')); ?></div>
                </div>
            </div>

            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6;"><i class='bx bx-home'></i></div>
                <div class="kpi-min-info">
                    <h4>Rent Due</h4>
                    <h2><?php echo money($rent_due); ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(139, 92, 246, 0.08); color: #8B5CF6;">Due on 07 <?php echo date('M Y'); ?></div>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class='bx bx-check-circle'></i></div>
                <div class="kpi-min-info">
                    <h4>Last Payment</h4>
                    <h2><?php echo $last_payment ? money($last_payment['total_amount']) : '₹0.00'; ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(16, 185, 129, 0.08); color: #10B981;">Paid on <?php echo $last_payment ? date('d M Y', strtotime($last_payment['payment_date'])) : '-'; ?></div>
                </div>
            </div>
        </div>

        
<!-- EXCLUSIVE MOBILE PAYMENTS DESIGN (<= 768px) -->
<div class="mobile-only-payments animate-up">
    <!-- 2x2 Summary Grid -->
    <div class="m-pay-summary-grid">
        <!-- Total Outstanding -->
        <div class="m-sum-card red">
            <div class="m-sum-top">
                <div class="m-sum-icon red"><i class='bx bx-credit-card-alt'></i></div>
            </div>
            <span>Total Outstanding</span>
            <h3 class="amount-red">₹<?php echo number_format((float)$total_due, 2); ?></h3>
            <div class="m-sum-pill red">Payment Due</div>
        </div>

        <!-- Electricity Due -->
        <div class="m-sum-card yellow">
            <div class="m-sum-top">
                <div class="m-sum-icon yellow"><i class='bx bx-bolt-circle'></i></div>
            </div>
            <span>Electricity Due</span>
            <h3>₹<?php echo number_format((float)($elec_due ?? 8.00), 2); ?></h3>
            <div class="m-sum-pill yellow">Due on 31 <?php echo date('M Y'); ?></div>
        </div>

        <!-- Rent Due -->
        <div class="m-sum-card purple">
            <div class="m-sum-top">
                <div class="m-sum-icon purple"><i class='bx bx-home-alt'></i></div>
            </div>
            <span>Rent Due</span>
            <h3>₹<?php echo number_format((float)($rent_due ?? 8000.00), 2); ?></h3>
            <div class="m-sum-pill purple">Due on 05 <?php echo date('M Y', strtotime('+1 month')); ?></div>
        </div>

        <!-- Last Payment -->
        <div class="m-sum-card green">
            <div class="m-sum-top">
                <div class="m-sum-icon green"><i class='bx bx-check-circle'></i></div>
            </div>
            <span>Last Payment</span>
            <h3>₹<?php echo $last_payment ? number_format((float)$last_payment['total_amount'], 2) : '8,000.00'; ?></h3>
            <div class="m-sum-pill green">Paid on <?php echo $last_payment ? date('d M Y', strtotime($last_payment['payment_date'])) : '05 Dec 2025'; ?></div>
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="m-pay-tabs">
        <button class="m-ptab active" onclick="filterMobilePayments('all', this)">All Payments</button>
        <button class="m-ptab" onclick="filterMobilePayments('rent', this)">Rent</button>
        <button class="m-ptab" onclick="filterMobilePayments('electricity', this)">Electricity</button>
        <button class="m-ptab" onclick="filterMobilePayments('other', this)">Other</button>
    </div>

    <!-- Filter Bar Row -->
    <div class="m-pay-filter-bar">
        <div class="m-year-select-box">
            <i class='bx bx-calendar'></i>
            <select id="mYearFilterSelect" onchange="filterMobileByYear(this.value)">
                <option value="all">All Years</option>
                <option value="2026">2026</option>
                <option value="2025">2025</option>
            </select>
            <i class='bx bx-chevron-down arrow'></i>
        </div>
        <button class="m-filter-action-btn" onclick="alert('Filtering applied!')">
            <i class='bx bx-filter-alt'></i> Filter
        </button>
    </div>

    <!-- Transactions List -->
    <div class="m-pay-items-list" id="mPayList">
        <?php foreach ($all_bills as $bill): 
            $title_disp = $bill['title'] == 'Rent' ? 'Rent Payment' : ($bill['title'] == 'Electricity' ? 'Electricity Payment' : $bill['title']);
            $sub_disp = date('M Y', strtotime($bill['period'])) . ' • ' . ($bill['type']=='rent' ? 'Room '.$room_no : ($bill['type']=='electricity' ? $bill['subtitle'] : $bill['period']));
            $year_val = date('Y', strtotime($bill['period']));
        ?>
            <div class="m-pay-card-item" data-type="<?php echo $bill['filter_type']; ?>" data-year="<?php echo $year_val; ?>">
                <div class="m-pci-icon <?php echo $bill['color']; ?>">
                    <i class='bx <?php echo $bill['icon']; ?>'></i>
                </div>
                <div class="m-pci-body">
                    <h4><?php echo htmlspecialchars($title_disp); ?></h4>
                    <p><?php echo htmlspecialchars($sub_disp); ?></p>
                </div>
                <div class="m-pci-center">
                    <span class="m-status-pill <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span>
                </div>
                <div class="m-pci-right">
                    <div class="m-pci-amt">₹<?php echo number_format((float)$bill['amount'], 2); ?></div>
                    <?php if ($bill['status'] == 'Paid'): ?>
                        <div class="m-pci-date"><?php echo $bill['paid_on']; ?></div>
                    <?php else: ?>
                        <button class="m-pci-pay-btn" onclick="openPaymentModal(<?php echo $bill['amount']; ?>, '<?php echo htmlspecialchars($title_disp); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>)">
                            <i class='bx bx-credit-card'></i> Pay Now
                        </button>
                    <?php endif; ?>
                </div>
                <?php if ($bill['status'] == 'Paid'): ?>
                    <button class="m-pci-dl-btn" title="Download Receipt"><i class='bx bx-download'></i></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Note & Pay Pending Button -->
    <?php if ($total_due > 0 || true): ?>
    <div class="m-pay-notice-card">
        <div class="m-pn-note">
            <i class='bx bx-info-circle'></i>
            <span><strong>Note:</strong> Please clear your pending payments before the due date to avoid service interruptions.</span>
        </div>
        <button class="m-pn-pay-btn" onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')">
            <i class='bx bx-credit-card'></i> Pay Pending Amount
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function filterMobilePayments(type, btn) {
    document.querySelectorAll('.m-ptab').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    
    const items = document.querySelectorAll('.m-pay-card-item');
    items.forEach(it => {
        if (type === 'all' || it.getAttribute('data-type') === type) {
            it.style.display = 'flex';
        } else {
            it.style.display = 'none';
        }
    });
}

function filterMobileByYear(year) {
    const items = document.querySelectorAll('.m-pay-card-item');
    items.forEach(it => {
        if (year === 'all' || it.getAttribute('data-year') === year) {
            it.style.display = 'flex';
        } else {
            it.style.display = 'none';
        }
    });
}
</script>

<!-- Payments Table Section -->
        <div class="payments-container animate-up" style="animation-delay: 0.1s;">
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-filter="all">All Payments</button>
                <button type="button" class="tab-btn" data-filter="rent">Rent Payments</button>
                <button type="button" class="tab-btn" data-filter="electricity">Electricity Payments</button>
                <button type="button" class="tab-btn" data-filter="other">Other Charges</button>
                
                <div class="tab-actions">
                    <select class="filter-select">
                        <option>All Years</option>
                        <option>2025</option>
                        <option>2026</option>
                    </select>
                    <button class="btn-filter"><i class='bx bx-filter'></i> Filter</button>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>BILL TYPE</th>
                            <th>FOR PERIOD</th>
                            <th>DUE DATE</th>
                            <th>AMOUNT</th>
                            <th>STATUS</th>
                            <th>PAID ON</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php 
                        $current_month = '';
                        foreach($all_bills as $bill): 
                            if ($bill['period'] != $current_month) {
                                $current_month = $bill['period'];
                                echo "<tr class='month-divider' data-filter-type='divider' data-period='$current_month'><td colspan='7' style='padding: 14px 24px; font-weight: 700; font-size: 13px; color: var(--text-gray); border-bottom: 2px solid var(--border); background: var(--bg-main);'><i class='bx bx-calendar' style='margin-right: 6px;'></i> $current_month</td></tr>";
                            }
                        ?>
                            <tr data-filter-type="<?php echo $bill['filter_type']; ?>" data-period="<?php echo htmlspecialchars($bill['period']); ?>" class="data-row">
                                <td>
                                    <div class="td-bill-type">
                                        <div class="td-icon <?php echo $bill['color']; ?>"><i class='bx <?php echo $bill['icon']; ?>'></i></div>
                                        <div class="td-info">
                                            <h4><?php echo htmlspecialchars($bill['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($bill['subtitle']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($bill['period']); ?></td>
                                <td><?php echo $bill['due_date']; ?></td>
                                <td style="font-weight: 800;"><?php echo money($bill['amount']); ?></td>
                                <td><span class="td-status <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span></td>
                                <td><?php echo $bill['paid_on']; ?></td>
                                <td>
                                    <?php if ($bill['status'] == 'Paid'): ?>
                                        <a href="#" class="btn-view-receipt"><i class='bx bx-download'></i> View Receipt</a>
                                    <?php else: ?>
                                        <button class="btn-action-pay" onclick="openPaymentModal(<?php echo $bill['amount']; ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>)">
                                            <i class='bx bx-credit-card-alt'></i> Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="paginationControls">
                <!-- JS will inject pagination buttons here -->
            </div>
        </div>

        <div class="bottom-info-bar animate-up" style="animation-delay: 0.2s;">
            <div class="info-text">
                <i class='bx bx-info-circle'></i>
                Note: Please make sure to clear your pending payments before the due date to avoid any service interruptions.
            </div>
            <?php if ($total_due > 0): ?>
                <button class="btn-pay-pending" onclick="openPaymentModal(<?php echo $total_due; ?>, 'Total Outstanding Balance', 'total', 0)">
                    <i class='bx bx-wallet'></i> Pay Pending Amount
                </button>
            <?php endif; ?>
        </div>

        <script>
            let currentTab = 'all';
            let currentPage = 1;
            const monthsPerPage = 3;

            function renderTable() {
                const allDataRows = Array.from(document.querySelectorAll('#paymentsTableBody tr.data-row'));
                const allDividers = Array.from(document.querySelectorAll('#paymentsTableBody tr.month-divider'));
                
                // 1. Filter rows by tab
                const filteredRows = allDataRows.filter(row => currentTab === 'all' || row.getAttribute('data-filter-type') === currentTab);
                
                // 2. Extract unique periods from filtered rows
                const uniquePeriods = [...new Set(filteredRows.map(row => row.getAttribute('data-period')))];
                
                // 3. Paginate periods
                const totalPages = Math.ceil(uniquePeriods.length / monthsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;
                
                const offset = (currentPage - 1) * monthsPerPage;
                const periodsToShow = uniquePeriods.slice(offset, offset + monthsPerPage);
                
                // 4. Show/Hide data rows based on pagination and filter
                allDataRows.forEach(row => {
                    if (filteredRows.includes(row) && periodsToShow.includes(row.getAttribute('data-period'))) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // 5. Show/Hide dividers
                allDividers.forEach(divider => {
                    const period = divider.getAttribute('data-period');
                    // Check if there are any visible data rows for this period
                    const hasVisibleRow = allDataRows.some(row => row.getAttribute('data-period') === period && row.style.display === 'table-row');
                    divider.style.display = hasVisibleRow ? 'table-row' : 'none';
                });
                
                // 6. Render Pagination controls
                renderPaginationControls(totalPages);
            }
            
            function renderPaginationControls(totalPages) {
                const container = document.getElementById('paginationControls');
                if (totalPages <= 1) {
                    container.innerHTML = '';
                    container.style.display = 'none';
                    return;
                }
                
                container.style.display = 'flex';
                let html = '';
                
                // Prev btn
                if (currentPage > 1) {
                    html += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class='bx bx-chevron-left'></i></a>`;
                } else {
                    html += `<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-left'></i></span>`;
                }
                
                // Pages
                for (let i = 1; i <= totalPages; i++) {
                    html += `<a href="#" class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>`;
                }
                
                // Next btn
                if (currentPage < totalPages) {
                    html += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class='bx bx-chevron-right'></i></a>`;
                } else {
                    html += `<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-right'></i></span>`;
                }
                
                container.innerHTML = html;
                
                // Attach events to dynamically created buttons
                container.querySelectorAll('a.page-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = parseInt(this.getAttribute('data-page'));
                        renderTable();
                    });
                });
            }

            // Tab Filtering Logic
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    currentTab = this.getAttribute('data-filter');
                    currentPage = 1; // Reset to page 1 on tab change
                    renderTable();
                });
            });
            
            // Initial render
            document.addEventListener('DOMContentLoaded', () => {
                renderTable();
            });
        </script>

    <?php include 'payment_modal.php'; ?>