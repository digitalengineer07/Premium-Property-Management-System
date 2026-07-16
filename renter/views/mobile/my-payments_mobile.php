<?php
// EXCLUSIVE MOBILE VIEW FOR MY-PAYMENTS
?>
<!-- Mobile Header -->
<header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-credit-card' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            Payments
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


<div class="animate-up" style="padding: 0 16px 85px 16px;">
    
    <!-- KPI Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; margin-top: 8px;">
        <!-- Card 1 -->
        <div style="background: var(--white); border-radius: 16px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,75,107,0.1); color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class='bx bx-credit-card-alt'></i>
            </div>
            <p style="font-size: 11px; color: var(--text-dark); font-weight: 700; margin: 0 0 4px 0;">Total Outstanding</p>
            <h2 style="font-size: 20px; font-weight: 800; color: #FF4B6B; margin: 0 0 12px 0;">₹<?php echo number_format((float)$total_due, 2); ?></h2>
            <span style="background: rgba(255,75,107,0.1); color: #FF4B6B; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 12px; width: 100%; box-sizing: border-box;"><?php echo $total_due > 0 ? 'Payment Due' : 'All Clear'; ?></span>
        </div>
        <!-- Card 2 -->
        <div style="background: var(--white); border-radius: 16px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245,158,11,0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class='bx bx-bolt-circle'></i>
            </div>
            <p style="font-size: 11px; color: var(--text-dark); font-weight: 700; margin: 0 0 4px 0;">Electricity Due</p>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0 0 12px 0;">₹<?php echo number_format((float)$elec_due, 2); ?></h2>
            <span style="background: rgba(245,158,11,0.1); color: #D97706; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 12px; width: 100%; box-sizing: border-box;">Due on <?php echo date('31 M Y'); ?></span>
        </div>
        <!-- Card 3 -->
        <div style="background: var(--white); border-radius: 16px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(98,75,255,0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class='bx bx-home-alt'></i>
            </div>
            <p style="font-size: 11px; color: var(--text-dark); font-weight: 700; margin: 0 0 4px 0;">Rent Due</p>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0 0 12px 0;">₹<?php echo number_format((float)$rent_due, 2); ?></h2>
            <span style="background: rgba(98,75,255,0.1); color: #624BFF; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 12px; width: 100%; box-sizing: border-box;">Due on <?php echo date('05 M Y', strtotime('+1 month')); ?></span>
        </div>
        <!-- Card 4 -->
        <div style="background: var(--white); border-radius: 16px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(16,185,129,0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class='bx bx-check-circle'></i>
            </div>
            <p style="font-size: 11px; color: var(--text-dark); font-weight: 700; margin: 0 0 4px 0;">Last Payment</p>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0 0 12px 0;">₹<?php echo number_format((float)($last_payment['total_amount'] ?? 0), 2); ?></h2>
            <span style="background: rgba(16,185,129,0.1); color: #10B981; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 12px; width: 100%; box-sizing: border-box;">Paid on <?php echo isset($last_payment['payment_date']) ? date('d M Y', strtotime($last_payment['payment_date'])) : 'N/A'; ?></span>
        </div>
    </div>

    <!-- Tabs -->
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--border); margin-bottom: 16px;">
        <button class="m-ptab active" onclick="filterMobilePayments('all', this)">All Payments</button>
        <button class="m-ptab" onclick="filterMobilePayments('rent', this)">Rent</button>
        <button class="m-ptab" onclick="filterMobilePayments('electricity', this)">Electricity</button>
        <button class="m-ptab" onclick="filterMobilePayments('other', this)">Other</button>
    </div>

    <!-- Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--border); padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-dark); background: var(--white);">
            <i class='bx bx-calendar'></i>
            <select style="border: none; background: transparent; font-weight: 600; color: var(--text-dark); outline: none;" onchange="filterMobileByYear(this.value)">
                <option value="all">All Years</option>
                <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?></option>
                <option value="<?php echo date('Y') - 1; ?>"><?php echo date('Y') - 1; ?></option>
            </select>
        </div>
        <button style="border: 1px solid var(--border); background: var(--white); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <i class='bx bx-filter-alt'></i> Filter
        </button>
    </div>

    <!-- Transactions List -->
    <div style="display: flex; flex-direction: column; background: var(--white); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 24px;">
        <?php foreach ($merged_rents as $idx => $t): ?>
            <?php 
                $isLast = ($idx === count($merged_rents) - 1);
                $isPending = ($t['status'] === 'Due');
                $amount = (float)$t['amount'];
                
                // Determine icons based on source
                if ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) {
                    $icon = "<i class='bx bx-home-alt'></i>";
                    $iconStyle = "background: rgba(98, 75, 255, 0.1); color: #624BFF;";
                    $title = "Rent Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else if ($t['source'] === 'advance') {
                    $icon = "<i class='bx bx-receipt'></i>";
                    $iconStyle = "background: rgba(59, 130, 246, 0.1); color: #3B82F6;";
                    $title = "Advance Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else {
                    $icon = "<i class='bx bx-wrench'></i>";
                    $iconStyle = "background: rgba(255, 75, 107, 0.1); color: #FF4B6B;";
                    $title = "Maintenance Charge";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                }
            ?>
            <?php
                $dataType = ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) ? 'rent' : (($t['source'] === 'advance') ? 'other' : 'other');
                $dataYear = date('Y', strtotime($t['month'] . '-01'));
            ?>
            <div class="m-pay-card-item" data-type="<?php echo $dataType; ?>" data-year="<?php echo $dataYear; ?>" style="display: flex; align-items: center; padding: 16px; border-bottom: <?php echo $isLast ? 'none' : '1px solid var(--border)'; ?>; position: relative; overflow: hidden;">
                <?php if ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Pending</span>
                <?php else: ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Paid</span>
                <?php endif; ?>
                <!-- Icon -->
                <div style="<?php echo $iconStyle; ?> width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <?php echo $icon; ?>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($amount, 2); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <?php echo $subtitle; ?>
                        </p>
                        <?php if ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo $amount; ?>, '<?php echo addslashes($title); ?>', '<?php echo $t['source'] === 'advance' ? 'advance' : 'rent'; ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                <i class='bx bx-revision'></i> Pay Now
                            </button>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 10px; color: var(--text-gray);"><?php echo date('d M Y', strtotime($t['month']. '-05')); ?></span>
                                <button style="background: none; border: 1px solid var(--border); border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #624BFF; cursor: pointer;"><i class='bx bx-download'></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php foreach (array_slice($elecs, 0, 3) as $idx => $t): ?>
            <?php 
                $isPending = ($t['status'] === 'Due');
                $amount = (float)$t['amount'];
                $dataYear = date('Y', strtotime($t['month'] . '-01'));
            ?>
            <div class="m-pay-card-item" data-type="electricity" data-year="<?php echo $dataYear; ?>" style="display: flex; align-items: center; padding: 16px; border-top: 1px solid var(--border); position: relative; overflow: hidden;">
                <?php if ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Pending</span>
                <?php else: ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Paid</span>
                <?php endif; ?>
                <!-- Icon -->
                <div style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <i class='bx bx-bolt-circle'></i>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            Electricity Payment
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($amount, 2); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <?php echo date('M Y', strtotime($t['month'] . '-01')); ?> • Units: <?php echo htmlspecialchars($t['units_consumed']); ?>
                        </p>
                        <?php if ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo $amount; ?>, 'Electricity Bill', 'electricity')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                <i class='bx bx-revision'></i> Pay Now
                            </button>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 10px; color: var(--text-gray);"><?php echo date('d M Y', strtotime($t['month']. '-03')); ?></span>
                                <button style="background: none; border: 1px solid var(--border); border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #624BFF; cursor: pointer;"><i class='bx bx-download'></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Notice & Pay All Button -->
    <?php if ($total_due > 0): ?>
    <div style="background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); border: 1px solid rgba(98, 75, 255, 0.15); border-radius: 20px; padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 11px; color: var(--text-dark); line-height: 1.4; margin-bottom: 14px;">
            <i class='bx bx-info-circle' style="font-size: 18px; color: #624BFF; flex-shrink: 0;"></i>
            <div>
                <strong>Note:</strong> Please clear your pending payments before the due date to avoid service interruptions.
            </div>
        </div>
        <button onclick="openPaymentModal(<?php echo $total_due; ?>, 'Total Outstanding Balance', 'total')" style="width: 100%; background: #624BFF; color: white; border: none; border-radius: 10px; padding: 12px; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
            <i class='bx bx-credit-card-alt'></i> Pay Pending Amount
        </button>
    </div>
    <?php endif; ?>

</div>
