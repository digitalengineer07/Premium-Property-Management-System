<?php
// EXCLUSIVE DESKTOP VIEW FOR PAYMENT-HISTORY.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header" style="padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 24px;">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-history' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">Payment History</h1>
                    <p style="margin: 0;">Review your previous transactions.</p>
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
        $recorded_tx = [];
        $processed_notif_ids = [];

        // Preload approved notifications for deduplication and UTR enrichment
        $notifs_list = [];
        $nq = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id=$user_id AND status='Approved'");
        while ($nr = mysqli_fetch_assoc($nq)) {
            $notifs_list[] = $nr;
        }

        // 1. Fetch all recorded payments directly
        $pay_q = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=$user_id ORDER BY payment_date DESC, id DESC");
        while ($p = mysqli_fetch_assoc($pay_q)) {
            $amt = (float)($p['paid_amount'] > 0 ? $p['paid_amount'] : $p['total_amount']);
            $type = trim($p['bill_type']);
            $month = $p['month'];
            $slip_dt = null;
            $has_elec_in_total = false;
            if ($type == 'total' || empty($type) || (int)$p['bill_id'] == 0) {
                $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month, payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE user_id=$user_id AND (total_amount=$amt OR (rent_amount+maintenance+dues)=$amt OR amount=$amt OR status='Paid') ORDER BY id DESC LIMIT 1"));
                if (!$bm) $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM rent WHERE user_id=$user_id AND (rent_amount=$amt OR status='Paid') ORDER BY id DESC LIMIT 1"));
                if ($bm) {
                    if (!empty($bm['month'])) $month = $bm['month'];
                    if (!empty($bm['payment_date'])) $slip_dt = $bm['payment_date'];
                    elseif (!empty($bm['created_at'])) $slip_dt = $bm['created_at'];
                    if (isset($bm['amount']) && (float)$bm['amount'] > 0 && $amt > ((float)$bm['rent_amount'] + (float)$bm['maintenance'] + (float)$bm['dues']) + 0.5) {
                        $has_elec_in_total = true;
                    }
                }
            } else {
                if ($type == 'electricity' || $type == 'elec_rent') {
                    $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE id=" . (int)$p['bill_id']));
                    if ($bm) {
                        $slip_dt = !empty($bm['payment_date']) ? $bm['payment_date'] : $bm['created_at'];
                        if (isset($bm['amount']) && (float)$bm['amount'] > 0 && $amt > ((float)$bm['rent_amount'] + (float)$bm['maintenance'] + (float)$bm['dues']) + 0.5) {
                            $has_elec_in_total = true;
                        }
                    }
                }
            }
            if (!$slip_dt && !empty($month)) {
                $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE user_id=$user_id AND month='" . mysqli_real_escape_string($conn, $month) . "' ORDER BY id DESC LIMIT 1"));
                if ($bm) {
                    $slip_dt = !empty($bm['payment_date']) ? $bm['payment_date'] : $bm['created_at'];
                    if (isset($bm['amount']) && (float)$bm['amount'] > 0 && $amt > ((float)$bm['rent_amount'] + (float)$bm['maintenance'] + (float)$bm['dues']) + 0.5) {
                        $has_elec_in_total = true;
                    }
                }
            }
            $pmode = !empty($p['payment_mode']) ? $p['payment_mode'] : 'UPI';
            $pdate = !empty($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A';
            
            $title = ucfirst($type) . ' Payment';
            $subtitle = !empty($p['transaction_id']) ? ('Ref: ' . $p['transaction_id']) : ('Room ' . $room_no);
            $icon = 'bx-credit-card';
            $color = 'purple';
            $filter_type = 'other';
            if ($slip_dt) {
                $bill_date = date('d M Y', strtotime($slip_dt));
                $due_date = date('d M Y', strtotime($slip_dt . ' + 6 days'));
            } else {
                $ts = strtotime($month);
                $bill_date = $ts ? date('01 M Y', $ts) : $pdate;
                $due_date = $ts ? date('07 M Y', $ts) : $pdate;
            }
            
            if ($type == 'rent') {
                $filter_type = 'rent';
                $title = 'Rent Payment';
                $icon = 'bx-home';
                $color = 'purple';
            } elseif ($type == 'electricity') {
                $filter_type = 'electricity';
                $title = 'Electricity Payment';
                $icon = 'bx-bulb';
                $color = 'yellow';
            } elseif ($type == 'advance') {
                $title = 'Advance Payment';
                $icon = 'bx-file';
                $color = 'blue';
            } elseif ($type == 'maintenance') {
                $title = 'Maintenance Payment';
                $icon = 'bx-wrench';
                $color = 'red';
            } elseif ($type == 'total' || empty($type)) {
                $title = $has_elec_in_total ? 'Rent + Main. + Electricity' : 'Rent + Main.';
                $icon = 'bx-credit-card';
                $color = 'purple';
            }
            
            // Match against approved notification to enrich subtitle with Ref ID and prevent duplicate
            foreach ($notifs_list as $nr) {
                if (empty($processed_notif_ids[$nr['id']]) && abs((float)$nr['amount'] - $amt) < 0.01) {
                    if ($nr['bill_type'] == $type || $nr['bill_type'] == 'total' || $type == 'total' || empty($type) || (int)$nr['bill_id'] == (int)$p['bill_id']) {
                        $processed_notif_ids[$nr['id']] = true;
                        $subtitle = 'Ref: ' . $nr['transaction_id'];
                        break;
                    }
                }
            }

            $recorded_tx[$type . '_' . $p['bill_id'] . '_' . $amt] = true;
            if (!empty($p['transaction_id'])) {
                $recorded_tx['tx_' . $p['transaction_id']] = true;
            }

            $all_bills[] = [
                'id' => $p['id'],
                'type' => $type,
                'filter_type' => $filter_type,
                'title' => $title,
                'subtitle' => $subtitle,
                'period' => $month,
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'amount' => $amt,
                'status' => 'Paid',
                'paid_on' => $pdate,
                'payment_mode' => $pmode,
                'icon' => $icon,
                'color' => $color
            ];
        }

        // 2. Fallback: Include approved payment notifications not already processed
        foreach ($notifs_list as $pn) {
            if (!empty($processed_notif_ids[$pn['id']])) continue;
            
            $amt = (float)$pn['amount'];
            $type = trim($pn['bill_type']);
            $bid = (int)$pn['bill_id'];
            if (isset($recorded_tx[$type . '_' . $bid . '_' . $amt]) || isset($recorded_tx['tx_' . $pn['transaction_id']])) continue;
            
            $pmode = !empty($pn['payment_method']) ? $pn['payment_method'] : 'UPI';
            $pdate = date('d M Y', strtotime($pn['verified_at'] ? $pn['verified_at'] : $pn['created_at']));
            $month = date('F Y', strtotime($pdate));
            $slip_dt = null;
            $has_elec_in_notif = false;
            if ($bid > 0) {
                if ($type == 'rent') {
                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM rent WHERE id=$bid"));
                    if ($mr) $month = $mr['month'];
                } elseif ($type == 'electricity' || $type == 'elec_rent' || $type == 'total') {
                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month, payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE id=$bid"));
                    if ($mr) {
                        if (!empty($mr['month'])) $month = $mr['month'];
                        $slip_dt = !empty($mr['payment_date']) ? $mr['payment_date'] : $mr['created_at'];
                        if (isset($mr['amount']) && (float)$mr['amount'] > 0 && $amt > ((float)$mr['rent_amount'] + (float)$mr['maintenance'] + (float)$mr['dues']) + 0.5) {
                            $has_elec_in_notif = true;
                        }
                    }
                }
            } else {
                $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month, payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE user_id=$user_id AND (total_amount=$amt OR (rent_amount+maintenance+dues)=$amt OR amount=$amt OR status='Paid') ORDER BY id DESC LIMIT 1"));
                if (!$bm) $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM rent WHERE user_id=$user_id AND (rent_amount=$amt OR status='Paid') ORDER BY id DESC LIMIT 1"));
                if ($bm) {
                    if (!empty($bm['month'])) $month = $bm['month'];
                    if (!empty($bm['payment_date'])) $slip_dt = $bm['payment_date'];
                    elseif (!empty($bm['created_at'])) $slip_dt = $bm['created_at'];
                    if (isset($bm['amount']) && (float)$bm['amount'] > 0 && $amt > ((float)$bm['rent_amount'] + (float)$bm['maintenance'] + (float)$bm['dues']) + 0.5) {
                        $has_elec_in_notif = true;
                    }
                }
            }
            if (!$slip_dt && !empty($month)) {
                $bm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_date, created_at, amount, rent_amount, maintenance, dues, total_amount FROM electricity WHERE user_id=$user_id AND month='" . mysqli_real_escape_string($conn, $month) . "' ORDER BY id DESC LIMIT 1"));
                if ($bm) {
                    $slip_dt = !empty($bm['payment_date']) ? $bm['payment_date'] : $bm['created_at'];
                    if (isset($bm['amount']) && (float)$bm['amount'] > 0 && $amt > ((float)$bm['rent_amount'] + (float)$bm['maintenance'] + (float)$bm['dues']) + 0.5) {
                        $has_elec_in_notif = true;
                    }
                }
            }
            
            $filter_type = ($type == 'rent') ? 'rent' : (($type == 'electricity') ? 'electricity' : 'other');
            $title = ($type == 'total' || empty($type)) ? ($has_elec_in_notif ? 'Rent + Main. + Electricity' : 'Rent + Main.') : (ucfirst($type) . ' Payment');
            $subtitle = 'Ref: ' . $pn['transaction_id'];

            $icon = ($type == 'rent') ? 'bx-home' : (($type == 'electricity') ? 'bx-bulb' : 'bx-credit-card');
            $color = ($type == 'rent') ? 'purple' : (($type == 'electricity') ? 'yellow' : 'blue');
            if ($slip_dt) {
                $bill_date = date('d M Y', strtotime($slip_dt));
                $due_date = date('d M Y', strtotime($slip_dt . ' + 6 days'));
            } else {
                $ts = strtotime($month);
                $bill_date = $ts ? date('01 M Y', $ts) : $pdate;
                $due_date = $ts ? date('07 M Y', $ts) : $pdate;
            }

            $all_bills[] = [
                'id' => $pn['id'],
                'type' => $type,
                'filter_type' => $filter_type,
                'title' => $title,
                'subtitle' => $subtitle,
                'period' => $month,
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'amount' => $amt,
                'status' => 'Paid',
                'paid_on' => $pdate,
                'payment_mode' => $pmode,
                'icon' => $icon,
                'color' => $color
            ];
        }
        
        $total_successful_amount = array_sum(array_column($all_bills, 'amount'));
        $total_successful_count = count($all_bills);
        $avg_payment = $total_successful_count > 0 ? $total_successful_amount / $total_successful_count : 0;
        
        $pending_q = mysqli_query($conn, "
            SELECT 
                (SELECT COALESCE(SUM(rent_amount),0) FROM rent WHERE user_id=$user_id AND status='Pending') +
                (SELECT COALESCE(SUM(amount + maintenance),0) FROM electricity WHERE user_id=$user_id AND status='Pending') as total_pending,
                (SELECT COUNT(id) FROM rent WHERE user_id=$user_id AND status='Pending') +
                (SELECT COUNT(id) FROM electricity WHERE user_id=$user_id AND status='Pending') as count_pending
        ");
        $pending_row = mysqli_fetch_assoc($pending_q);
        $total_pending_amount = $pending_row['total_pending'];
        $total_pending_count = $pending_row['count_pending'];
        
        $total_all_amount = $total_successful_amount + $total_pending_amount;

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
                <div class="kpi-min-icon" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;"><i class='bx bx-credit-card-alt'></i></div>
                <div class="kpi-min-info">
                    <h4>Total Payments</h4>
                    <h2><?php echo money($total_all_amount); ?></h2>
                    <div class="kpi-min-tag" style="background: transparent; color: var(--text-gray); padding: 0;">All time payments</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class='bx bx-check-circle'></i></div>
                <div class="kpi-min-info">
                    <h4>Successful Payments</h4>
                    <h2><?php echo money($total_successful_amount); ?></h2>
                    <div class="kpi-min-tag" style="background: transparent; color: var(--text-gray); padding: 0;"><?php echo $total_successful_count; ?> Transactions</div>
                </div>
            </div>

            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><i class='bx bx-time'></i></div>
                <div class="kpi-min-info">
                    <h4>Pending Payments</h4>
                    <h2><?php echo money($total_pending_amount); ?></h2>
                    <div class="kpi-min-tag" style="background: transparent; color: var(--text-gray); padding: 0;"><?php echo $total_pending_count; ?> Transactions</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;"><i class='bx bx-receipt'></i></div>
                <div class="kpi-min-info">
                    <h4>Avg. Payment</h4>
                    <h2><?php echo money($avg_payment); ?></h2>
                    <div class="kpi-min-tag" style="background: transparent; color: var(--text-gray); padding: 0;">Per Transaction</div>
                </div>
            </div>
        </div>

        <!-- Payments Table Section -->
        <div class="payments-container animate-up" style="animation-delay: 0.1s;">
            <div class="tabs-header" style="flex-wrap: wrap; gap: 16px; padding: 24px;">
                <div class="filter-group">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-gray); margin-bottom: 6px;">Date Range</label>
                    <div style="display: flex; align-items: center; border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; background: var(--white); min-width: 200px;">
                        <i class='bx bx-calendar' style="color: var(--text-gray); margin-right: 8px;"></i>
                        <span style="font-size: 13px; font-weight: 500;">All Time</span>
                        <i class='bx bx-chevron-down' style="margin-left: auto; color: var(--text-gray);"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-gray); margin-bottom: 6px;">Bill Type</label>
                    <select class="filter-select" style="width: 150px;" onchange="currentTab = this.value; currentPage = 1; renderTable();">
                        <option value="all">All Types</option>
                        <option value="rent">Rent</option>
                        <option value="electricity">Electricity</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-gray); margin-bottom: 6px;">Payment Status</label>
                    <select class="filter-select" style="width: 150px;">
                        <option>All Status</option>
                        <option>Paid</option>
                    </select>
                </div>

                <div class="filter-group" style="flex: 1;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-gray); margin-bottom: 6px;">Search</label>
                    <div style="position: relative;">
                        <i class='bx bx-search' style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-gray);"></i>
                        <input type="text" placeholder="Search by bill type or month..." style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px 8px 32px; font-size: 13px; font-family: inherit; outline: none;">
                    </div>
                </div>

                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button class="btn-outline-support" style="height: 38px; color: var(--primary-purple); border-color: rgba(98, 75, 255, 0.2);">
                        <i class='bx bx-reset'></i> Reset Filters
                    </button>
                </div>
            </div>
            
            <h4 style="margin-top: 24px; margin-bottom: 16px; margin-left: 24px; font-size: 15px; color: var(--text-dark);">Transaction History</h4>
            
            <div style="overflow-x: auto;">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>BILL TYPE</th>
                            <th>FOR PERIOD</th>
                            <th>BILL DATE</th>
                            <th>DUE DATE</th>
                            <th>AMOUNT</th>
                            <th>STATUS</th>
                            <th>PAID ON</th>
                            <th style="text-align: center;">PAYMENT MODE</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php 
                        $counter = 1;
                        foreach($all_bills as $bill): 
                        ?>
                            <tr data-filter-type="<?php echo $bill['filter_type']; ?>" class="data-row">
                                <td style="text-align: center; color: var(--text-gray); font-weight: 500;"><?php echo $counter++; ?></td>
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
                                <td><?php echo $bill['bill_date']; ?></td>
                                <td><?php echo $bill['due_date']; ?></td>
                                <td style="font-weight: 800;"><?php echo money($bill['amount']); ?></td>
                                <td><span class="td-status <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span></td>
                                <td><?php echo $bill['paid_on']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; font-size: 13px; color: var(--text-dark);">
                                        <?php if(strpos(strtolower($bill['payment_mode']), 'upi') !== false): ?>
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px;">
                                            UPI
                                        <?php elseif(strpos(strtolower($bill['payment_mode']), 'net banking') !== false): ?>
                                            <i class='bx bxs-bank' style="color: #624BFF; font-size: 16px;"></i>
                                            <?php echo $bill['payment_mode']; ?>
                                        <?php else: ?>
                                            <i class='bx bx-wallet' style="color: #F59E0B; font-size: 16px;"></i>
                                            <?php echo $bill['payment_mode']; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 24px; padding: 0 12px;">
                <div style="font-size: 13px; color: var(--text-gray); font-weight: 500;">
                    Showing <span id="showingStart">1</span> to <span id="showingEnd">5</span> of <span id="totalRecords">14</span> transactions
                </div>
                <div class="pagination" id="paginationControls" style="margin-top: 0; padding: 0; border: none;">
                    <!-- JS will inject pagination buttons here -->
                </div>
            </div>
        </div>

        <script>
            let currentTab = 'all';
            let currentPage = 1;
            const recordsPerPage = 5;

            function renderTable() {
                const allDataRows = Array.from(document.querySelectorAll('#paymentsTableBody tr.data-row'));
                
                // 1. Filter rows by tab
                const filteredRows = allDataRows.filter(row => currentTab === 'all' || row.getAttribute('data-filter-type') === currentTab);
                
                // 2. Paginate rows
                const totalRecords = filteredRows.length;
                const totalPages = Math.ceil(totalRecords / recordsPerPage) || 1;
                
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;
                
                const startIndex = (currentPage - 1) * recordsPerPage;
                const endIndex = Math.min(startIndex + recordsPerPage, totalRecords);
                
                // 3. Show/Hide data rows based on pagination
                allDataRows.forEach(row => {
                    row.style.display = 'none';
                });
                
                for(let i = startIndex; i < endIndex; i++) {
                    filteredRows[i].style.display = 'table-row';
                    // Update counter dynamically for the filtered set
                    filteredRows[i].querySelector('td:first-child').textContent = i + 1;
                }
                
                // 4. Update showing text
                document.getElementById('showingStart').textContent = totalRecords === 0 ? 0 : startIndex + 1;
                document.getElementById('showingEnd').textContent = endIndex;
                document.getElementById('totalRecords').textContent = totalRecords;
                
                // 5. Render Pagination controls
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

            // Initial render
            document.addEventListener('DOMContentLoaded', () => {
                renderTable();
            });
        </script>