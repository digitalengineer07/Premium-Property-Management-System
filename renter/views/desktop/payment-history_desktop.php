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
                                <div class="notification-wrapper" style="position: relative; display: inline-block;">
                    <div class="icon-btn bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
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
        // Fetch all transactions from the payments table
        $all_bills = [];
        $payments_q = mysqli_query($conn, "
            SELECT p.id, p.bill_type, p.bill_id, p.month, p.total_amount as bill_amount, p.paid_amount, p.payment_date, p.payment_mode, p.transaction_id,
                   e.status as elec_status, e.rent_amount, e.maintenance, e.dues, e.amount as elec_usage, e.created_at
            FROM payments p
            LEFT JOIN electricity e ON p.bill_type IN ('electricity', 'total', 'elec_rent') AND p.bill_id = e.id
            WHERE p.user_id = $user_id
        ");

        while ($p = mysqli_fetch_assoc($payments_q)) {
            $month = $p['month'];
            $amt = (float)$p['paid_amount'];
            $pdate = !empty($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A';
            
            $ts = strtotime($month);
            $bill_date = 'N/A';
            $due_date = 'N/A';
            
            if ($p['bill_type'] == 'rent') {
                $bill_date = $ts ? date('01 M Y', $ts) : 'N/A';
                $due_date = $ts ? date('07 M Y', $ts) : 'N/A';
                $title = 'Rent Payment';
                $filter_type = 'rent';
                $icon = 'bx-home';
                $color = 'purple';
            } elseif ($p['bill_type'] == 'electricity' || $p['bill_type'] == 'total' || $p['bill_type'] == 'elec_rent') {
                $slip_dt = !empty($p['created_at']) ? $p['created_at'] : $p['payment_date'];
                if ($ts) {
                    $bill_date = date('d M Y', strtotime($slip_dt));
                    $due_date = date('d M Y', strtotime($slip_dt . ' + 6 days'));
                }
                
                $has_elec = (float)$p['elec_usage'] > 0;
                $has_rent = (float)$p['rent_amount'] > 0 || (float)$p['maintenance'] > 0;
                
                if ($has_elec && $has_rent) {
                    $title = 'Rent + Main. + Electricity';
                    $filter_type = 'other';
                    $icon = 'bx-credit-card';
                    $color = 'purple';
                } elseif ($has_rent) {
                    $title = 'Rent + Main.';
                    $filter_type = 'other';
                    $icon = 'bx-home';
                    $color = 'purple';
                } else {
                    $title = 'Electricity Payment';
                    $filter_type = 'electricity';
                    $icon = 'bx-bulb';
                    $color = 'yellow';
                }
            } elseif ($p['bill_type'] == 'advance') {
                $bill_date = $ts ? date('01 M Y', $ts) : $pdate;
                $due_date = $ts ? date('07 M Y', $ts) : $pdate;
                $title = 'Advance Payment';
                $filter_type = 'other';
                $icon = 'bx-file';
                $color = 'blue';
            }
            
            $all_bills[] = [
                'raw_id' => $p['id'],
                'id' => $p['bill_type'][0].'_'.$p['id'],
                'type' => $p['bill_type'],
                'filter_type' => $filter_type,
                'title' => $title,
                'subtitle' => !empty($p['transaction_id']) ? ('Ref: ' . $p['transaction_id']) : ('Room ' . $room_no),
                'period' => $month,
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'amount' => $amt,
                'status' => 'Paid',
                'paid_on' => $pdate,
                'payment_mode' => !empty($p['payment_mode']) ? $p['payment_mode'] : 'Cash/UPI',
                'icon' => $icon,
                'color' => $color
            ];
        }

        // 2. Removed fallback loop that caused duplicate/inflated 'extra paid amount' KPIs
        
        $total_successful_amount = array_sum(array_column($all_bills, 'amount'));
        $total_successful_count = count($all_bills);
        $avg_payment = $total_successful_count > 0 ? $total_successful_amount / $total_successful_count : 0;
        
        $pending_q = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total_pending, COUNT(id) as count_pending FROM payment_notifications WHERE user_id=$user_id AND status='Pending'");
        $pending_row = mysqli_fetch_assoc($pending_q);
        $total_pending_amount = $pending_row['total_pending'];
        $total_pending_count = $pending_row['count_pending'];
        
        $total_all_amount = $total_successful_amount + $total_pending_amount;

        // Sort by Period Descending, then by Bill Date Descending, then by Payment ID Descending
        usort($all_bills, function($a, $b) { 
            $t1 = strtotime($b['period']);
            $t2 = strtotime($a['period']);
            if ($t1 == $t2) {
                $bd1 = strtotime($b['bill_date']);
                $bd2 = strtotime($a['bill_date']);
                if ($bd1 == $bd2) {
                    return $b['raw_id'] - $a['raw_id'];
                }
                return $bd1 - $bd2;
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
                    <h4>Latest Payment</h4>
                    <h2><?php echo count($all_bills) > 0 ? money($all_bills[0]['amount']) : money(0); ?></h2>
                    <div class="kpi-min-tag" style="background: transparent; color: var(--text-gray); padding: 0;"><?php echo count($all_bills) > 0 ? 'Paid on ' . $all_bills[0]['paid_on'] : 'No Transactions'; ?></div>
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
                    <select class="filter-select type-filter" style="width: 150px;">
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
                    Showing <span class="showing-start">1</span> to <span class="showing-end">5</span> of <span class="total-records">14</span> transactions
                </div>
                <div class="pagination pagination-controls" style="margin-top: 0; padding: 0; border: none;">
                    <!-- JS will inject pagination buttons here -->
                </div>
            </div>
        </div>

        <script>
            (function() {
                // Get the container for THIS specific instance
                const containers = document.querySelectorAll('.payments-container');
                const container = containers[containers.length - 1];
                
                let currentTab = 'all';
                let currentPage = 1;
                const recordsPerPage = 5;

                const typeFilter = container.querySelector('.type-filter');
                if (typeFilter) {
                    typeFilter.addEventListener('change', function() {
                        currentTab = this.value;
                        currentPage = 1;
                        renderTable();
                    });
                }

                function renderTable() {
                    const allDataRows = Array.from(container.querySelectorAll('tbody tr.data-row'));
                    
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
                    const startEl = container.querySelector('.showing-start');
                    if(startEl) startEl.textContent = totalRecords === 0 ? 0 : startIndex + 1;
                    
                    const endEl = container.querySelector('.showing-end');
                    if(endEl) endEl.textContent = endIndex;
                    
                    const totalEl = container.querySelector('.total-records');
                    if(totalEl) totalEl.textContent = totalRecords;
                    
                    // 5. Render Pagination controls
                    renderPaginationControls(totalPages);
                }
                
                function renderPaginationControls(totalPages) {
                    const pagContainer = container.querySelector('.pagination-controls');
                    if (!pagContainer) return;
                    
                    if (totalPages <= 1) {
                        pagContainer.innerHTML = '';
                        pagContainer.style.display = 'none';
                        return;
                    }
                    
                    pagContainer.style.display = 'flex';
                    let html = '';
                    
                    // Prev Button
                    if (currentPage > 1) {
                        html += `<a href="#" class="page-btn" data-page="${currentPage - 1}"><i class='bx bx-chevron-left'></i></a>`;
                    } else {
                        html += `<span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>`;
                    }
                    
                    // Page Numbers
                    for (let i = 1; i <= totalPages; i++) {
                        if (i === currentPage) {
                            html += `<a href="#" class="page-btn active" data-page="${i}">${i}</a>`;
                        } else {
                            html += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
                        }
                    }
                    
                    // Next Button
                    if (currentPage < totalPages) {
                        html += `<a href="#" class="page-btn" data-page="${currentPage + 1}"><i class='bx bx-chevron-right'></i></a>`;
                    } else {
                        html += `<span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>`;
                    }
                    
                    pagContainer.innerHTML = html;
                    
                    // Attach events to dynamically created buttons
                    pagContainer.querySelectorAll('a.page-btn').forEach(btn => {
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
            })();
        </script>
