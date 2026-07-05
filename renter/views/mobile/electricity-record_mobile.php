<?php
// EXCLUSIVE MOBILE VIEW FOR ELECTRICITY-RECORD
?>

<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-plug' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            Electricity
        </h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
        <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
            <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../../' . $user['profile_pic'])): ?>
                <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: #624BFF; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: 800;">
                    <?php echo strtoupper(substr(trim($user['name'] ?? ($user['username'] ?? 'U')), 0, 1)); ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</header>
<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>


<div class="m-dashboard-content" style="padding: 16px 16px 85px 16px; background: var(--bg-main);">
    
    <!-- KPI Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
        <!-- Total Units -->
        <div style="background: var(--white); border-radius: 16px; padding: 14px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(98, 75, 255, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-credit-card-front' style="color: var(--primary-purple); font-size: 18px;"></i>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 10px; font-weight: 600; color: var(--text-gray); line-height: 1.2;">Total Units (This Year)</p>
                </div>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: var(--text-dark);"><?php echo number_format($total_units); ?> Units</h4>
                <p style="margin: 0; font-size: 9px; font-weight: 500; color: var(--text-gray);">Total electricity consumed</p>
            </div>
        </div>
        
        <!-- Amount Paid -->
        <div style="background: var(--white); border-radius: 16px; padding: 14px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(16, 185, 129, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#10B981" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22v-9" />
                        <path d="M12 13l-4 4" />
                        <path d="M12 13l4 4" />
                        <path d="M8 9l4-4 4 4" />
                        <path d="M12 5v4" />
                    </svg>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 10px; font-weight: 600; color: var(--text-gray); line-height: 1.2;">Amount Paid (This Year)</p>
                </div>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: var(--text-dark);"><?php echo money($amount_paid); ?></h4>
                <p style="margin: 0; font-size: 9px; font-weight: 500; color: var(--text-gray);">Total paid for electricity</p>
            </div>
        </div>
        
        <!-- Pending Amount -->
        <div style="background: var(--white); border-radius: 16px; padding: 14px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(245, 158, 11, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-time-five' style="color: #F59E0B; font-size: 18px;"></i>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 10px; font-weight: 600; color: var(--text-gray); line-height: 1.2;">Pending Amount</p>
                </div>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: var(--text-dark);"><?php echo money($pending_amount); ?></h4>
                <?php if($pending_amount > 0): ?>
                    <p style="margin: 0; font-size: 9px; font-weight: 700; color: #FF4B6B;">Outstanding dues</p>
                <?php else: ?>
                    <p style="margin: 0; font-size: 9px; font-weight: 700; color: #10B981;">All payments cleared</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Last Recorded Reading -->
        <div style="background: var(--white); border-radius: 16px; padding: 14px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(59, 130, 246, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-bolt-circle' style="color: #3B82F6; font-size: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 10px; font-weight: 600; color: var(--text-gray); line-height: 1.2;">Last Recorded Reading</p>
                </div>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: var(--text-dark);"><?php echo number_format($last_reading); ?> Units</h4>
                <p style="margin: 0; font-size: 9px; font-weight: 500; color: var(--text-gray);"><?php echo $last_reading_date; ?></p>
            </div>
        </div>
    </div>

    <!-- Usage Overview Chart Panel -->
    <div style="background: var(--white); border-radius: 16px; border: 1px solid var(--border); padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 id="chartTitleText" style="margin: 0; font-size: 14px; font-weight: 800; color: var(--text-dark);">Usage Overview (Units)</h3>
            <div style="position: relative;">
                <select id="chartMetricSelect" style="background: var(--bg-main); border: 1px solid rgba(98, 75, 255, 0.15); border-radius: 6px; color: var(--primary-purple); font-weight: 700; font-size: 11px; padding: 4px 24px 4px 10px; appearance: none; outline: none; cursor: pointer;">
                    <option value="units">Units</option>
                    <option value="amount">Amount</option>
                </select>
                <i class='bx bx-chevron-down' style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); color: var(--primary-purple); pointer-events: none; font-size: 14px;"></i>
            </div>
        </div>
        <div style="height: 180px; width: 100%; position: relative;">
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    <!-- Current Month Details -->
    <div style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
            <i class='bx bx-bolt-circle' style="color: var(--primary-purple); font-size: 18px;"></i>
            <h3 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--primary-purple);">Current Month Details</h3>
        </div>
        <?php if($latest_record): ?>
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Billing Month</span>
                <span style="font-size: 11px; color: var(--text-dark); font-weight: 600;"><?php echo htmlspecialchars($latest_record['month']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Previous Reading</span>
                <span style="font-size: 11px; color: var(--text-dark); font-weight: 600;"><?php echo number_format($latest_record['previous_reading']); ?> Units</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Current Reading</span>
                <span style="font-size: 11px; color: var(--text-dark); font-weight: 600;"><?php echo number_format($latest_record['current_reading']); ?> Units</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Units Consumed</span>
                <span style="font-size: 11px; color: var(--text-dark); font-weight: 600;"><?php echo number_format($latest_record['units_consumed']); ?> Units</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Rate per Unit</span>
                <span style="font-size: 11px; color: var(--text-dark); font-weight: 600;">₹<?php echo number_format((float)$latest_record['rate_per_unit'], 2); ?></span>
            </div>
        </div>
        <div style="background: rgba(98, 75, 255, 0.05); border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin: 0 -4px;">
            <span style="font-size: 13px; font-weight: 800; color: var(--primary-purple);">Amount Payable</span>
            <span style="font-size: 15px; font-weight: 800; color: var(--primary-purple);"><?php echo money($latest_record['amount']); ?></span>
        </div>
        <?php else: ?>
        <p style="margin: 0; font-size: 12px; color: var(--text-gray);">No records found.</p>
        <?php endif; ?>
    </div>

    <!-- Electricity Record Table -->
    <div style="background: var(--white); border-radius: 16px; border: 1px solid var(--border); padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--text-dark);">Electricity Record</h3>
            <div style="display: flex; gap: 8px;">
                <div style="position: relative;">
                    <select style="background: var(--bg-main); border: 1px solid var(--border); border-radius: 6px; color: var(--text-dark); font-weight: 700; font-size: 10px; padding: 4px 22px 4px 22px; appearance: none; outline: none;">
                        <option>All Years</option>
                        <option><?php echo date("Y"); ?></option>
                    </select>
                    <i class='bx bx-calendar' style="position: absolute; left: 6px; top: 50%; transform: translateY(-50%); color: var(--primary-purple); font-size: 12px; pointer-events: none;"></i>
                    <i class='bx bx-chevron-down' style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 14px; pointer-events: none;"></i>
                </div>
                <button style="background: var(--bg-main); border: 1px solid var(--border); border-radius: 6px; color: var(--primary-purple); font-weight: 700; font-size: 10px; padding: 4px 10px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-filter-alt'></i> Filter</button>
            </div>
        </div>

        <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 8px;">
            <table style="width: 100%; min-width: 420px; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="text-align: left; padding: 0 0 12px 0; font-size: 10px; font-weight: 600; color: var(--text-gray); white-space: nowrap;">Month / Year</th>
                        <th style="text-align: center; padding: 0 12px 12px 12px; font-size: 10px; font-weight: 600; color: var(--text-gray);">Units</th>
                        <th style="text-align: right; padding: 0 12px 12px 12px; font-size: 10px; font-weight: 600; color: var(--text-gray);">Amount</th>
                        <th style="text-align: center; padding: 0 12px 12px 12px; font-size: 10px; font-weight: 600; color: var(--text-gray);">Status</th>
                        <th style="text-align: right; padding: 0 0 12px 12px; font-size: 10px; font-weight: 600; color: var(--text-gray);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach($electricity_records as $idx => $rec): 
                        if ($idx >= 5) break;
                        $is_current = ($idx === 0);
                        $status_class = strtolower($rec['status']);
                        if ($status_class == 'due') $status_class = 'unpaid';
                        $status_text = ucfirst($status_class);
                        if ($status_text == 'Due') $status_text = 'Unpaid';
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 0;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 11px; font-weight: <?php echo $is_current ? '800' : '700'; ?>; color: var(--text-dark); white-space: nowrap;"><?php echo htmlspecialchars($rec['month']); ?></span>
                                <?php if($is_current): ?>
                                    <span style="background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 12px;">Current</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 14px 12px; text-align: center; font-size: 11px; font-weight: <?php echo $is_current ? '800' : '600'; ?>; color: var(--text-dark);"><?php echo number_format($rec['units_consumed']); ?></td>
                        <td style="padding: 14px 12px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-dark);"><?php echo money($rec['amount']); ?></td>
                        <td style="padding: 14px 12px; text-align: center;">
                            <?php if($status_text === 'Unpaid'): ?>
                                <span style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 12px;">Unpaid</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: #10B981; font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 12px;">Paid</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 0 14px 12px; text-align: right;">
                            <?php if($status_text === 'Unpaid'): ?>
                                <button style="background: var(--bg-main); border: 1px solid rgba(98, 75, 255, 0.2); color: var(--primary-purple); font-size: 9px; font-weight: 700; padding: 5px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i class='bx bx-credit-card' style="font-size: 12px;"></i> Pay Now
                                </button>
                            <?php else: ?>
                                <button style="background: var(--bg-main); border: 1px solid rgba(98, 75, 255, 0.2); color: var(--primary-purple); font-size: 9px; font-weight: 700; padding: 5px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i class='bx bx-receipt' style="font-size: 12px;"></i> View Bill
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 12px;">
            <button style="background: none; border: none; color: var(--primary-purple); font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                View More Records <i class='bx bx-chevron-down' style="font-size: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- Tips to Save Electricity -->
    <div style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <div style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg viewBox="0 0 48 48" width="40" height="40" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 8a12 12 0 0 0-12 12c0 4.1 2 7.7 5 10v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4c3-2.3 5-5.9 5-10A12 12 0 0 0 24 8zm0 2a10 10 0 0 1 10 10c0 3.5-1.9 6.7-4.7 8.5a1 1 0 0 0-.3.7v3.8H19v-3.8a1 1 0 0 0-.3-.7C15.9 26.7 14 23.5 14 20a10 10 0 0 1 10-10zm-3 27v1a3 3 0 0 0 6 0v-1h-6z" fill="#F59E0B"/>
                <path d="M24 16v4m0 0l-2-2m2 2l2-2" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20 40a4 4 0 0 0-4-4c-2 0-3-2-3-2s2 4 5 4-2-2-5-2" stroke="#10B981" fill="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M28 40a4 4 0 0 1 4-4c2 0 3-2 3-2s-2 4-5 4 2-2 5-2" stroke="#10B981" fill="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 4px 0; font-size: 13px; font-weight: 800; color: var(--text-dark);">Tips to Save Electricity</h4>
            <p style="margin: 0; font-size: 11px; font-weight: 500; color: var(--text-gray); line-height: 1.4;">Use energy efficient appliances and switch off when not in use.</p>
        </div>
        <i class='bx bx-chevron-right' style="color: var(--text-gray); font-size: 20px;"></i>
    </div>
</div>
