<?php
// EXCLUSIVE MOBILE VIEW FOR MY-BILLS.PHP

// Calculate $all_bills if not already done (since it's done in desktop view after this)
$mobile_all_bills = [];

// 1. Pure Rent
$rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status, COALESCE(p.payment_date, r.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = r.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                FROM rent r LEFT JOIN payments p ON p.bill_type='rent' AND p.bill_id=r.id 
                                WHERE r.user_id=$user_id");
while($r = mysqli_fetch_assoc($rent_q)) {
    $mobile_all_bills[] = [
        'id' => $r['id'], 'type' => 'rent', 'filter_type' => ($r['status'] == 'Paid' ? 'paid' : 'unpaid'),
        'title' => 'February 2026', // Mocking based on month for UI matching
        'real_title' => date('F Y', strtotime($r['month'])),
        'subtitle' => 'Room ' . $room_no,
        'period' => $r['month'],
        'bill_date' => date('01 M Y', strtotime($r['month'])),
        'due_date' => date('d M Y', strtotime($r['month'] . '-05')),
        'amount' => $r['amount'], 'status' => $r['status'] == 'Due' ? 'Unpaid' : $r['status'],
        'paid_on' => $r['payment_date'] ? date('d M Y', strtotime($r['payment_date'])) : '-',
        'icon' => 'bx-home', 'icon_bg' => 'rgba(139, 92, 246, 0.1)', 'icon_color' => '#8B5CF6',
        'badge' => 'Rent', 'badge_bg' => 'rgba(255, 75, 107, 0.1)', 'badge_color' => '#FF4B6B',
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
    $mobile_all_bills[] = [
        'id' => $e['id'], 'type' => 'electricity', 'filter_type' => ($e['status'] == 'Paid' ? 'paid' : 'unpaid'),
        'real_title' => date('F Y', strtotime($e['month'])),
        'subtitle' => 'Room ' . $room_no,
        'period' => $e['month'],
        'bill_date' => date('01 M Y', strtotime($e['month'])),
        'due_date' => date('d M Y', strtotime('+1 month', strtotime($e['month'] . '-05'))),
        'amount' => $e['amount'], 'status' => $e['status'] == 'Due' ? 'Unpaid' : $e['status'],
        'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
        'icon' => 'bx-bolt-circle', 'icon_bg' => 'rgba(245, 158, 11, 0.1)', 'icon_color' => '#F59E0B',
        'badge' => 'Electricity', 'badge_bg' => 'rgba(245, 158, 11, 0.1)', 'badge_color' => '#F59E0B',
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
    $mobile_all_bills[] = [
        'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : 'unpaid'),
        'real_title' => date('F Y', strtotime($m['month'])),
        'subtitle' => 'Room ' . $room_no,
        'period' => $m['month'],
        'bill_date' => date('01 M Y', strtotime($m['month'])),
        'due_date' => date('d M Y', strtotime($m['month'] . '-05')),
        'amount' => $m['combined_amount'], 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
        'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
        'icon' => 'bx-wrench', 'icon_bg' => 'rgba(59, 130, 246, 0.1)', 'icon_color' => '#3B82F6',
        'badge' => 'Maintenance', 'badge_bg' => 'rgba(59, 130, 246, 0.1)', 'badge_color' => '#3B82F6',
        'summary' => [
            'Monthly Rent' => $m['rent_amount'],
            'Maintenance Charge' => $m['maintenance'],
            'Other Charges' => $m['dues']
        ]
    ];
}

// Sort by Period Descending
usort($mobile_all_bills, function($a, $b) { 
    return strtotime($b['bill_date']) - strtotime($a['bill_date']);
});

$paid_this_year = 0;
$bills_paid_count = 0;
foreach($mobile_all_bills as $b) {
    if ($b['status'] == 'Paid') {
        $paid_this_year += $b['amount'];
        $bills_paid_count++;
    }
}
$due_this_month = $total_due ?? 0; 
$total_bills_count = count($mobile_all_bills);
?>

<style>
    .m-bills-container { background: var(--bg-main); padding-bottom: 270px; font-family: 'Outfit', sans-serif; }
    .m-header-custom { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: transparent; position: sticky; top: 0; z-index: 100; }
    
    .m-kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 16px; margin-bottom: 24px; }
    .m-kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .m-kpi-top { display: flex; align-items: center; gap: 12px; }
    .m-kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-kpi-title { font-size: 11px; font-weight: 600; color: var(--text-dark); margin: 0; }
    .m-kpi-value { font-size: 18px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px; }
    .m-kpi-pill { font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 12px; display: inline-block; white-space: nowrap; }

    .m-tabs { display: flex; gap: 24px; padding: 0 16px; border-bottom: 1px solid var(--border); margin-bottom: 16px; overflow-x: auto; scrollbar-width: none; }
    .m-tabs::-webkit-scrollbar { display: none; }
    .m-tab { font-size: 13px; font-weight: 600; color: var(--text-gray); padding-bottom: 12px; cursor: pointer; white-space: nowrap; }
    .m-tab.active { color: #624BFF; border-bottom: 2px solid #624BFF; }

    .m-filters { display: flex; justify-content: space-between; align-items: center; padding: 0 16px; margin-bottom: 16px; }
    .m-filter-btn { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--white); font-size: 12px; font-weight: 600; color: var(--text-dark); }
    .m-filter-btn-purple { color: #624BFF; border-color: rgba(98, 75, 255, 0.2); }

    .m-bill-list { display: flex; flex-direction: column; gap: 12px; padding: 0 16px; }
    .m-bill-item { background: var(--white); border-radius: 16px; padding: 16px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border); }
    .m-bill-left { display: flex; align-items: center; gap: 12px; flex: 1; }
    .m-bill-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .m-bill-info h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0 0 4px 0; }
    .m-bill-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    .m-bill-badge { font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
    
    .m-bill-mid { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; }
    .m-bill-mid h4 { font-size: 12px; font-weight: 600; color: var(--text-dark); margin: 0 0 4px 0; }
    .m-bill-mid p { font-size: 10px; color: #FF4B6B; margin: 0; font-weight: 600; }

    .m-bill-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex: 1; text-align: right; }
    .m-bill-right-info h4 { font-size: 14px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.3px; display: flex; align-items: center; gap: 12px; }
    .m-bill-status { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; display: inline-block; }
    .m-bill-action { color: var(--text-gray); font-size: 18px; display: flex; align-items: center; }

    .m-bottom-panel { position: fixed; bottom: 0; left: 0; right: 0; background: var(--white); border-top-left-radius: 24px; border-top-right-radius: 24px; padding: 24px 20px; box-shadow: 0 -4px 20px rgba(0,0,0,0.05); z-index: 99; border-top: 1px solid var(--border); }
    .m-panel-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px; color: var(--text-dark); font-weight: 600; }
    .m-panel-total { display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border); font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; }
    .m-btn-primary { width: 100%; background: #624BFF; color: white; border: none; border-radius: 12px; padding: 14px; font-size: 14px; font-weight: 700; display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 12px; }
    .m-btn-outline { width: 100%; background: transparent; color: #624BFF; border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 12px; padding: 14px; font-size: 14px; font-weight: 700; display: flex; justify-content: center; align-items: center; gap: 8px; }
    
    /* Hide global mobile bottom nav to make room for the sticky summary panel */
    .mobile-bottom-nav { display: none !important; }
    
    /* Push the PWA install button above the sticky summary panel */
    #pwaInstallBtn { bottom: 230px !important; }
</style>

<div class="m-bills-container animate-up">
    <!-- Header -->
    <header class="m-header-custom">
        <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }" style="cursor: pointer;">
            <i class='bx bx-menu-alt-left' style="font-size: 28px; color: var(--text-dark);"></i>
        </div>
        <div class="m-header-brand" style="flex: 1; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; line-height: 1.2; margin-left: 16px;">
            <span style="font-size: 18px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.3px;">My Bills</span>
            <span style="font-size: 12px; font-weight: 500; color: var(--text-gray);">View and manage all your bills</span>
        </div>
        <div class="m-header-right" style="display: flex; align-items: center; gap: 14px;">
            <div style="position: relative; cursor: pointer;" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'block' : 'none';">
                <i class='bx bx-bell' style="font-size: 24px; color: var(--text-dark);"></i>
                <span style="position: absolute; top: 0px; right: 0px; background: #FF4B6B; width: 14px; height: 14px; border-radius: 50%; color: white; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-main);">1</span>
            </div>
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #624BFF; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 15px; cursor: pointer;">
                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 2)); ?>
            </div>
        </div>
    </header>

    <!-- KPIs -->
    <div class="m-kpi-grid">
        <!-- Outstanding -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">
                    <i class='bx bx-receipt'></i>
                </div>
                <h4 class="m-kpi-title">Total Outstanding</h4>
            </div>
            <div>
                <h2 class="m-kpi-value" style="color: #FF4B6B;"><?php echo money($total_due ?? 0); ?></h2>
                <span class="m-kpi-pill" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Payment Due</span>
            </div>
        </div>

        <!-- Due This Month -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-calendar'></i>
                </div>
                <h4 class="m-kpi-title">Due This Month</h4>
            </div>
            <div>
                <h2 class="m-kpi-value" style="color: var(--text-dark);"><?php echo money($due_this_month); ?></h2>
                <span class="m-kpi-pill" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Due on 05 <?php echo date('M Y'); ?></span>
            </div>
        </div>

        <!-- Paid This Year -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-check-circle'></i>
                </div>
                <h4 class="m-kpi-title">Paid This Year</h4>
            </div>
            <div>
                <h2 class="m-kpi-value" style="color: var(--text-dark);"><?php echo money($paid_this_year); ?></h2>
                <span class="m-kpi-pill" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><?php echo $bills_paid_count; ?> Bills Paid</span>
            </div>
        </div>

        <!-- Total Bills -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">
                    <i class='bx bx-file'></i>
                </div>
                <h4 class="m-kpi-title">Total Bills</h4>
            </div>
            <div>
                <h2 class="m-kpi-value" style="color: var(--text-dark);"><?php echo $total_bills_count; ?></h2>
                <span class="m-kpi-pill" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">All Time</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="m-tabs">
        <div class="m-tab active">All Bills</div>
        <div class="m-tab">Unpaid</div>
        <div class="m-tab">Paid</div>
        <div class="m-tab">Overdue</div>
    </div>

    <!-- Filters -->
    <div class="m-filters">
        <div class="m-filter-btn">
            <i class='bx bx-calendar'></i> All Years <i class='bx bx-chevron-down'></i>
        </div>
        <div class="m-filter-btn m-filter-btn-purple">
            <i class='bx bx-filter-alt'></i> Filter
        </div>
    </div>

    <!-- Bill List -->
    <div class="m-bill-list">
        <?php foreach(array_slice($mobile_all_bills, 0, 10) as $bill): ?>
        <div class="m-bill-item" onclick="selectMobileBill(<?php echo htmlspecialchars(json_encode($bill)); ?>)">
            <div class="m-bill-left">
                <div class="m-bill-icon" style="background: <?php echo $bill['icon_bg']; ?>; color: <?php echo $bill['icon_color']; ?>;">
                    <i class='bx <?php echo $bill['icon']; ?>'></i>
                </div>
                <div class="m-bill-info">
                    <h4><?php echo $bill['real_title']; ?></h4>
                    <p><?php echo $bill['subtitle']; ?></p>
                    <span class="m-bill-badge" style="background: <?php echo $bill['badge_bg']; ?>; color: <?php echo $bill['badge_color']; ?>;"><?php echo $bill['badge']; ?></span>
                </div>
            </div>
            <div class="m-bill-mid">
                <h4><?php echo $bill['due_date']; ?></h4>
                <?php if($bill['status'] === 'Unpaid'): ?>
                    <p>Due Today</p>
                <?php endif; ?>
            </div>
            <div class="m-bill-right">
                <div class="m-bill-right-info">
                    <h4>
                        <?php echo money($bill['amount']); ?>
                        <div class="m-bill-action">
                            <?php if($bill['status'] === 'Paid'): ?>
                                <i class='bx bx-download' style="color: #624BFF; font-size: 16px;"></i>
                            <?php else: ?>
                                <i class='bx bx-chevron-right' style="font-size: 16px;"></i>
                            <?php endif; ?>
                        </div>
                    </h4>
                    <?php if($bill['status'] === 'Paid'): ?>
                        <span class="m-bill-status" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Paid</span>
                    <?php else: ?>
                        <span class="m-bill-status" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Unpaid</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; font-size: 12px; color: var(--text-gray);">
            <span>Showing 1 to <?php echo min(10, count($mobile_all_bills)); ?> of <?php echo count($mobile_all_bills); ?> bills</span>
            <div style="display: flex; gap: 8px;">
                <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;"><i class='bx bx-chevron-left'></i></div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: #624BFF; color: white; display: flex; align-items: center; justify-content: center;">1</div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center;">2</div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;"><i class='bx bx-chevron-right'></i></div>
            </div>
        </div>
    </div>

    <!-- Bottom Summary Panel -->
    <div class="m-bottom-panel" id="mBillSummaryPanel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--text-dark);">Bill Summary</h3>
            <span id="mSummaryStatus" style="font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 12px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Unpaid</span>
        </div>
        
        <div id="mSummaryDetails">
            <!-- Dynamically populated -->
            <div class="m-panel-row"><span>Monthly Rent</span><span>₹0.00</span></div>
            <div class="m-panel-row"><span>Maintenance Charge</span><span>₹0.00</span></div>
            <div class="m-panel-row"><span>Other Charges</span><span>₹0.00</span></div>
        </div>

        <div class="m-panel-total">
            <span>Total Amount</span>
            <span id="mSummaryTotal" style="color: #FF4B6B;">₹0.00</span>
        </div>

        <button class="m-btn-primary" onclick="if(typeof openPaymentModal==='function') openPaymentModal(0, 'Quick Payment', 'general');">
            <i class='bx bx-credit-card-front'></i> Pay Now
        </button>
        <button class="m-btn-outline">
            <i class='bx bx-download'></i> Download Bill
        </button>
    </div>
</div>

<script>
    // Set initial bill summary if bills exist
    const mobileBills = <?php echo json_encode($mobile_all_bills); ?>;
    
    function selectMobileBill(bill) {
        if(!bill) return;
        
        const detailsContainer = document.getElementById('mSummaryDetails');
        detailsContainer.innerHTML = '';
        
        for (const [key, val] of Object.entries(bill.summary)) {
            detailsContainer.innerHTML += `<div class="m-panel-row"><span>${key}</span><span>₹${parseFloat(val).toFixed(2)}</span></div>`;
        }
        
        document.getElementById('mSummaryTotal').innerText = '₹' + parseFloat(bill.amount).toFixed(2);
        
        const statusBadge = document.getElementById('mSummaryStatus');
        if (bill.status === 'Paid') {
            statusBadge.style.background = 'rgba(16, 185, 129, 0.1)';
            statusBadge.style.color = '#10B981';
            statusBadge.innerText = 'Paid';
            document.getElementById('mSummaryTotal').style.color = 'var(--text-dark)';
        } else {
            statusBadge.style.background = 'rgba(255, 75, 107, 0.1)';
            statusBadge.style.color = '#FF4B6B';
            statusBadge.innerText = 'Unpaid';
            document.getElementById('mSummaryTotal').style.color = '#FF4B6B';
        }
    }

    if (mobileBills && mobileBills.length > 0) {
        // Find first unpaid bill, else first bill
        let firstBill = mobileBills.find(b => b.status === 'Unpaid') || mobileBills[0];
        selectMobileBill(firstBill);
    }
</script>