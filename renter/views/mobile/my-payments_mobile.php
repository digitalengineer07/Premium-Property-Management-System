<?php
// EXCLUSIVE MOBILE VIEW FOR MY-PAYMENTS
?>
<!-- Mobile Header -->
<header class="m-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: transparent;">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu' style="font-size: 28px; color: var(--text-dark); cursor: pointer;"></i>
    </div>
    <div class="m-header-center" style="text-align: left; flex: 1; margin-left: 12px;">
        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0;">My Payments</h2>
        <p style="font-size: 11px; color: var(--text-gray); margin: 2px 0 0 0;">View and manage all your bills & payments</p>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 10px;">
        <div class="icon-btn m-bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';" style="position: relative; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-dark); cursor: pointer;">
            <i class='bx bx-bell'></i>
            <?php if ($unread_count > 0): ?>
                <span class="m-notif-badge" style="position: absolute; top: 0px; right: 2px; width: 8px; height: 8px; background: #FF4B6B; border-radius: 50%; border: 2px solid var(--bg-main);"></span>
            <?php endif; ?>
        </div>
        <div class="user-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: #624BFF; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700;">
            <?php echo strtoupper(substr($display_name ?? 'U', 0, 2)); ?>
        </div>
    </div>
</header>

<div class="animate-up" style="padding: 0 16px 90px 16px;">
    
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
    <div style="display: flex; gap: 24px; border-bottom: 1px solid var(--border); margin-bottom: 16px; overflow-x: auto; padding-bottom: 8px;">
        <div style="font-size: 13px; font-weight: 700; color: #624BFF; border-bottom: 2px solid #624BFF; padding-bottom: 4px; white-space: nowrap;">All Payments</div>
        <div style="font-size: 13px; font-weight: 600; color: var(--text-gray); white-space: nowrap;">Rent</div>
        <div style="font-size: 13px; font-weight: 600; color: var(--text-gray); white-space: nowrap;">Electricity</div>
        <div style="font-size: 13px; font-weight: 600; color: var(--text-gray); white-space: nowrap;">Other</div>
    </div>

    <!-- Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-dark); background: var(--white);">
            <i class='bx bx-calendar'></i> All Years <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-dark); background: var(--white);">
            <i class='bx bx-filter-alt'></i> Filter
        </div>
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
                    $subtitle = date('M Y', strtotime($t['month'] . '-01')) . " • Room " . htmlspecialchars($room_no);
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
            <div style="display: flex; align-items: center; padding: 16px; border-bottom: <?php echo $isLast ? 'none' : '1px solid var(--border)'; ?>;">
                <!-- Icon -->
                <div style="<?php echo $iconStyle; ?> width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <?php echo $icon; ?>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            <?php echo $title; ?>
                            <?php if ($isPending): ?>
                                <span style="background: rgba(245,158,11,0.1); color: #D97706; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700;">Pending</span>
                            <?php else: ?>
                                <span style="background: rgba(16,185,129,0.1); color: #10B981; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700;">Paid</span>
                            <?php endif; ?>
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
            ?>
            <div style="display: flex; align-items: center; padding: 16px; border-top: 1px solid var(--border);">
                <!-- Icon -->
                <div style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <i class='bx bx-bolt-circle'></i>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            Electricity Payment
                            <?php if ($isPending): ?>
                                <span style="background: rgba(245,158,11,0.1); color: #D97706; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700;">Pending</span>
                            <?php else: ?>
                                <span style="background: rgba(16,185,129,0.1); color: #10B981; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700;">Paid</span>
                            <?php endif; ?>
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
