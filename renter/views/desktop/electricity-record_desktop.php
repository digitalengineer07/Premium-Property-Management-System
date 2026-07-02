<?php
// EXCLUSIVE DESKTOP VIEW FOR ELECTRICITY-RECORD.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->


        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-bolt-circle' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">Electricity Record</h1>
                    <p style="margin: 0;">Track your daily usage and readings.</p>
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
                <a href="#" class="btn-outline-support">
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
        
        <!-- KPI Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class='bx bx-credit-card'></i></div>
                <div class="kpi-info">
                    <h4>Total Units (This Year)</h4>
                    <h2><?php echo number_format($total_units); ?> Units</h2>
                    <p>Total electricity consumed</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon green"><i class='bx bx-money'></i></div>
                <div class="kpi-info">
                    <h4>Amount Paid (This Year)</h4>
                    <h2><?php echo money($amount_paid); ?></h2>
                    <p>Total paid for electricity</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class='bx bx-time-five'></i></div>
                <div class="kpi-info">
                    <h4>Pending Amount</h4>
                    <h2><?php echo money($pending_amount); ?></h2>
                    <?php if($pending_amount == 0): ?>
                        <p class="green">All payments cleared</p>
                    <?php else: ?>
                        <p style="color: #FF4B6B;">Outstanding dues</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class='bx bx-tachometer'></i></div>
                <div class="kpi-info">
                    <h4>Last Recorded Reading</h4>
                    <h2><?php echo number_format($last_reading); ?> Units</h2>
                    <p><?php echo $last_reading_date; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid (Chart + Current Details) -->
        <div class="dashboard-grid">
            <!-- Chart Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 id="chartTitleText">Usage Overview (Units)</h3>
                    <select class="filter-select" id="chartMetricSelect" style="cursor: pointer; border: 1.5px solid var(--border); border-radius: 8px; padding: 6px 12px; font-weight: 600; font-family: 'Outfit', sans-serif;">
                        <option value="units">Units</option>
                        <option value="amount">Amount</option>
                    </select>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>
            
            <!-- Current Month Details -->
            <div class="panel cmd-panel">
                <div class="panel-header" style="margin-bottom: 20px;">
                    <h3><i class='bx bx-bolt-circle'></i> Current Month Details</h3>
                </div>
                <?php if($latest_record): ?>
                <div class="cmd-list">
                    <div class="cmd-item">
                        <span class="cmd-label">Billing Month</span>
                        <span class="cmd-value"><?php echo htmlspecialchars($latest_record['month']); ?></span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Previous Reading</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['previous_reading']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Current Reading</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['current_reading']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Units Consumed</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['units_consumed']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Rate per Unit</span>
                        <span class="cmd-value">₹<?php echo number_format((float)$latest_record['rate_per_unit'], 2); ?></span>
                    </div>
                </div>
                <div class="cmd-total">
                    <span class="cmd-label">Amount Payable</span>
                    <span class="cmd-value"><?php echo money($latest_record['amount']); ?></span>
                </div>
                <?php else: ?>
                <div style="text-align: center; color: var(--text-gray); padding: 40px 0;">
                    <i class='bx bx-info-circle' style="font-size: 32px; opacity: 0.5; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px; margin: 0;">No records found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Electricity Record Table Panel -->
        <div class="panel">
            <div class="panel-header" style="margin-bottom: 16px;">
                <h3>Electricity Record</h3>
                <div style="display: flex; gap: 12px;">
                    <select class="filter-select">
                        <option>All Years</option>
                        <option><?php echo $current_year; ?></option>
                    </select>
                    <button class="btn-filter-small">
                        <i class='bx bx-filter-alt'></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="er-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Month / Year</th>
                            <th style="text-align: right;">Prev. Reading<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Curr. Reading<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Consumed<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Amount<br><span style="text-transform:none; font-weight: 500;">(₹)</span></th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Paid On</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recordTableBody">
                        <?php 
                        $counter = 1;
                        foreach($electricity_records as $idx => $rec): 
                            $is_current = ($idx === 0); // Assuming sorted DESC by ID
                            $status_class = strtolower($rec['status']);
                            if ($status_class == 'due') $status_class = 'unpaid';
                            $status_text = ucfirst($status_class);
                            if ($status_text == 'Due') $status_text = 'Unpaid';
                        ?>
                        <tr class="rec-row" data-index="<?php echo $idx; ?>">
                            <td style="text-align: center; color: var(--text-gray); font-weight: 500;"><?php echo $counter++; ?></td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <?php echo htmlspecialchars($rec['month']); ?>
                                </div>
                            </td>
                            <td style="text-align: right;"><?php echo number_format($rec['previous_reading']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($rec['current_reading']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($rec['units_consumed']); ?></td>
                            <td style="text-align: right; font-weight: 800;"><?php echo money($rec['amount']); ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td style="text-align: center; color: var(--text-gray); font-weight: 500;">
                                <?php echo ($rec['status'] == 'Paid' && !empty($rec['payment_date'])) ? date("d M Y", strtotime($rec['payment_date'])) : '&mdash;'; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="../admin/slip.php?elec_id=<?php echo $rec['id']; ?>" class="btn-table-action"><i class='bx bx-receipt'></i> View Bill</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="view-more-container" id="viewMoreContainer" style="<?php echo (count($electricity_records) > 5) ? '' : 'display:none;'; ?>">
                <button class="btn-view-more" onclick="showAllRecords()">
                    View More Records <i class='bx bx-chevron-down'></i>
                </button>
            </div>
        </div>