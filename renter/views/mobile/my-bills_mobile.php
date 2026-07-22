<?php
// EXCLUSIVE MOBILE VIEW FOR MY-BILLS.PHP

// Calculate $all_bills if not already done (since it's done in desktop view after this)
$mobile_all_bills = [];

// 1. Pure Rent
$rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status, COALESCE(p.payment_date, r.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = r.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                FROM rent r LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='rent' GROUP BY bill_id) p ON p.bill_id=r.id 
                                WHERE r.user_id=$user_id");
while($r = mysqli_fetch_assoc($rent_q)) {
    $mobile_all_bills[] = [
        'id' => $r['id'], 'type' => 'rent', 'filter_type' => ($r['status'] == 'Paid' ? 'paid' : (strtotime($r['month'].'-05') < time() ? 'overdue' : 'unpaid')),
        'title' => 'February 2026', // Mocking based on month for UI matching
        'real_title' => date('F Y', strtotime($r['month'])),
        'subtitle' => 'Room ' . $room_no,
        'period' => $r['month'],
        'bill_date' => date('01 F Y', strtotime($r['month'])),
        'due_date' => date('d F Y', strtotime($r['month'] . '-05')),
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

// 2. Combined Bill (Electricity + Rent + Maintenance)
$comb_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount as elec_amount, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, COALESCE(NULLIF(e.status, ''), 'Due') as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type IN ('electricity', 'elec_rent') GROUP BY bill_id) p ON p.bill_id=e.id 
                                WHERE e.user_id=$user_id AND (e.amount > 0 OR e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0 OR e.extra_charges > 0)");
while($c = mysqli_fetch_assoc($comb_q)) {
    $total_amt = (float)$c['elec_amount'] + (float)$c['rent_amount'] + (float)$c['maintenance'] + (float)$c['dues'] + (float)$c['extra_charges'];
    
    $summary = [];
    if ((float)$c['rent_amount'] > 0) $summary['Monthly Rent'] = (float)$c['rent_amount'];
    if ((float)$c['maintenance'] > 0) $summary['Maintenance Charge'] = (float)$c['maintenance'];
    if ((float)$c['elec_amount'] > 0) $summary['Electricity Usage'] = (float)$c['elec_amount'];
    if ((float)$c['extra_charges'] > 0) $summary['Other Charges'] = (float)$c['extra_charges'];
    if ((float)$c['dues'] != 0) $summary['Advance Applied'] = (float)$c['dues'];
    
    $mobile_all_bills[] = [
        'id' => $c['id'], 'type' => 'combined', 'filter_type' => ($c['status'] == 'Paid' ? 'paid' : (strtotime($c['month'].'-05') < time() ? 'overdue' : 'unpaid')),
        'real_title' => date('F Y', strtotime($c['month'])),
        'subtitle' => 'Room ' . $room_no,
        'period' => $c['month'],
        'bill_date' => date('01 F Y', strtotime($c['month'])),
        'due_date' => date('d F Y', strtotime($c['month'] . '-07')),
        'amount' => $total_amt, 'status' => $c['status'] == 'Due' ? 'Unpaid' : $c['status'],
        'paid_on' => $c['payment_date'] ? date('d M Y', strtotime($c['payment_date'])) : '-',
        'icon' => 'bx-layer', 'icon_bg' => 'rgba(98, 75, 255, 0.1)', 'icon_color' => 'var(--primary-purple)',
        'badge' => 'Rent + Utility', 'badge_bg' => 'rgba(98, 75, 255, 0.1)', 'badge_color' => 'var(--primary-purple)',
        'summary' => $summary
    ];
}

// Sort by Period Descending
usort($mobile_all_bills, function($a, $b) { 
    return strtotime($b['bill_date'] ?? 'now') - strtotime($a['bill_date'] ?? 'now');
});
$due_this_month = $total_due ?? 0; 
$total_bills_count = count($mobile_all_bills);
?>

<style>
    .m-bills-container { background: var(--bg-main); padding-bottom: 85px; font-family: 'Outfit', sans-serif; }
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
    .m-tab { font-size: 13px; font-weight: 600; color: var(--text-gray); padding-bottom: 8px; cursor: pointer; white-space: nowrap; }
    .m-tab.active { color: #624BFF; border-bottom: 2px solid #624BFF; }

    .m-filters { display: flex; justify-content: space-between; align-items: center; padding: 0 16px; margin-bottom: 16px; }
    .m-filter-btn { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--white); font-size: 12px; font-weight: 600; color: var(--text-dark); }
    .m-filter-btn-purple { color: #624BFF; border-color: rgba(98, 75, 255, 0.2); }

    .m-bill-list { display: flex; flex-direction: column; gap: 12px; padding: 0 16px; }
    .m-bill-item { background: var(--white); border-radius: 16px; padding: 16px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border); position: relative; cursor: pointer; transition: all 0.2s ease; }
    .m-bill-item.selected { border-color: #624BFF; background: rgba(98, 75, 255, 0.03); box-shadow: 0 4px 20px rgba(98, 75, 255, 0.08); }
    .m-bill-left { display: flex; align-items: center; gap: 12px; flex: 1.2; min-width: 0; }
    .m-bill-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .m-bill-info { min-width: 0; }
    .m-bill-info h4 { font-size: 13px; font-weight: 700; color: var(--text-dark); margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .m-bill-info p { font-size: 11px; font-weight: 500; color: var(--text-gray); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .m-bill-badge { font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
    
    .m-bill-mid { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; min-width: 0; padding: 0 4px; }
    .m-bill-mid h4 { font-size: 11px; font-weight: 700; color: var(--text-gray); margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; letter-spacing: 0.5px; }
    .m-bill-mid p { font-size: 10px; color: #FF4B6B; margin: 0; font-weight: 600; white-space: nowrap; }

    .m-bill-right { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 2px; flex: 1; text-align: right; min-width: 0; }
    .m-bill-right-info h4 { font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.3px; display: flex; align-items: center; gap: 12px; }
    .m-bill-status { position: absolute; top: 0; right: 0; font-size: 9px; font-weight: 800; padding: 4px 12px; border-top-right-radius: 15px; border-bottom-left-radius: 12px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
    .m-bill-action { color: var(--text-gray); font-size: 18px; display: flex; align-items: center; }
    .m-download-btn-mini { width: 26px; height: 26px; background: rgba(98, 75, 255, 0.1); color: #624BFF; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer; transition: 0.2s; margin-left: 6px; }
    .m-download-btn-mini:active { background: rgba(98, 75, 255, 0.2); }

    /* In-flow UI for Bill Summary */
    .m-bottom-panel { background: var(--white); border-radius: 24px; margin: 0 16px 24px 16px; padding: 24px 20px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: none; }
    .m-bottom-panel.show { display: block; }
    
    .m-panel-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; color: var(--text-dark); font-weight: 500; }
    .m-panel-total { display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border); font-size: 15px; font-weight: 800; color: var(--text-dark); margin-bottom: 24px; }
    .m-btn-primary { width: 100%; background: #624BFF; color: white; border: none; border-radius: 14px; padding: 14px; font-size: 13px; font-weight: 700; display: flex; justify-content: center; align-items: center; gap: 2px; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2); cursor: pointer; }
    .m-btn-outline { width: 100%; background: transparent; color: #624BFF; border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 14px; padding: 14px; font-size: 13px; font-weight: 700; display: flex; justify-content: center; align-items: center; gap: 2px; cursor: pointer; }
    
</style>

<div class="m-bills-container animate-up">
    <!-- Header -->
    <header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 2px;">
            <i class='bx bx-receipt' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            My Bills
        </h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 2px;">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-icon-btn" onclick="openMobileNotif()">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
        <a href="#" class="header-profile-btn" onclick="openMobileProfile(); return false;" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
            <?php if (!empty($user['profile_pic']) && file_exists("../" . $user['profile_pic'])): ?>
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


    <!-- KPIs -->
    <div class="m-kpi-grid">
        <!-- Outstanding -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">
                    <i class='bx bx-receipt'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Total Outstanding</h4>
                    <h2 class="m-kpi-value" style="color: #FF4B6B; margin: 4px 0 0 0; font-size: 16px;"><?php echo money($total_due ?? 0); ?></h2>
                </div>
            </div>
            <div style="margin-top: auto;">
                <span class="m-kpi-pill" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Payment Due</span>
            </div>
        </div>

        <!-- Due This Month -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-calendar'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Due This Month</h4>
                    <h2 class="m-kpi-value" style="color: var(--text-dark); margin: 4px 0 0 0; font-size: 16px;"><?php echo money($due_this_month); ?></h2>
                </div>
            </div>
            <div style="margin-top: auto;">
                <span class="m-kpi-pill" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Due on 05 <?php echo date('M Y'); ?></span>
            </div>
        </div>

        <!-- Paid This Year -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-check-circle'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Paid This Year</h4>
                    <h2 class="m-kpi-value" style="color: var(--text-dark); margin: 4px 0 0 0; font-size: 16px;"><?php echo money($paid_this_year); ?></h2>
                </div>
            </div>
            <div style="margin-top: auto;">
                <span class="m-kpi-pill" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><?php echo $bills_paid_count; ?> Bills Cleared</span>
            </div>
        </div>

        <!-- Total Bills -->
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">
                    <i class='bx bx-file'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Total Bills</h4>
                    <h2 class="m-kpi-value" style="color: var(--text-dark); margin: 4px 0 0 0; font-size: 16px;"><?php echo $total_bills_count; ?></h2>
                </div>
            </div>
            <div style="margin-top: auto;">
                <span class="m-kpi-pill" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">All Time</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="m-tabs">
        <div class="m-tab active" onclick="filterMobileBills('all', this)">All Bills</div>
        <div class="m-tab" onclick="filterMobileBills('unpaid', this)">Unpaid</div>
        <div class="m-tab" onclick="filterMobileBills('paid', this)">Paid</div>
        <div class="m-tab" onclick="filterMobileBills('overdue', this)">Overdue</div>
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

    <!-- In-flow Bill Summary Panel -->
    <div class="m-bottom-panel" id="mBillSummaryPanel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--text-dark);">Bill Summary</h3>
        </div>
        
        <div id="mSummaryDetails">
            <!-- Dynamically populated -->
        </div>

        <div class="m-panel-total">
            <span>Total Amount</span>
            <span id="mSummaryTotal" style="color: #FF4B6B;">₹0.00</span>
        </div>


        <button id="mBtnDownloadBill" class="m-btn-outline" style="display: none;" onclick="">
            <i class='bx bx-download'></i> Download Bill
        </button>
    </div>

    <!-- Bill List -->
    <div class="m-bill-list" id="mBillListContainer">
        <?php foreach(array_slice($mobile_all_bills, 0, 10) as $bill): ?>
        <div class="m-bill-item" data-bill-id="<?php echo $bill['type'] . '-' . $bill['id']; ?>" data-status="<?php echo $bill['filter_type']; ?>" onclick="selectMobileBill(<?php echo htmlspecialchars(json_encode($bill)); ?>)">
            <div class="m-bill-left">
                <div class="m-bill-icon" style="background: <?php echo $bill['icon_bg']; ?>; color: <?php echo $bill['icon_color']; ?>;">
                    <i class='bx <?php echo $bill['icon']; ?>'></i>
                </div>
                <div class="m-bill-info">
                    <h4><?php echo $bill['real_title']; ?></h4>
                    <p><?php echo $bill['subtitle']; ?></p>
                </div>
            </div>
            <div class="m-bill-mid">
                <h4><?php echo $bill['due_date']; ?></h4>
                <span class="m-bill-badge" style="background: <?php echo $bill['badge_bg']; ?>; color: <?php echo $bill['badge_color']; ?>;"><?php echo $bill['badge']; ?></span>
                <?php if($bill['status'] === 'Unpaid'): ?>
                    <p style="margin-top: 4px;">Due Today</p>
                <?php endif; ?>
            </div>
            <div class="m-bill-right">
                <div class="m-bill-right-info">
                    <h4>
                        <?php echo money($bill['amount']); ?>
                        <div class="m-bill-action">
                            <?php if($bill['status'] === 'Paid'): ?>
                                <i class='bx bx-download m-download-btn-mini' onclick="event.stopPropagation(); window.location.href='invoice.php?id=<?php echo $bill['id']; ?>'"></i>
                            <?php else: ?>
                                <i class='bx bx-chevron-right' style="font-size: 16px;"></i>
                            <?php endif; ?>
                        </div>
                    </h4>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; font-size: 12px; color: var(--text-gray);">
            <span>Showing 1 to <?php echo min(10, count($mobile_all_bills)); ?> of <?php echo count($mobile_all_bills); ?> bills</span>
            <div style="display: flex; gap: 2px;">
                <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;"><i class='bx bx-chevron-left'></i></div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: #624BFF; color: white; display: flex; align-items: center; justify-content: center;">1</div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center;">2</div>
                <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;"><i class='bx bx-chevron-right'></i></div>
            </div>
        </div>
    </div>
</div>

<script>
    // In-flow UI Logic
    const mobileBills = <?php echo json_encode($mobile_all_bills); ?>;
    
    function filterMobileBills(status, tabElement) {
        // Update active tab styling
        const tabs = document.querySelectorAll('.m-tabs .m-tab');
        tabs.forEach(t => t.classList.remove('active'));
        tabElement.classList.add('active');
        
        // Filter items
        const items = document.querySelectorAll('.m-bill-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const itemStatus = item.getAttribute('data-status');
            if (status === 'all' || itemStatus === status) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Update pagination text
        const paginationText = document.querySelector('.m-bill-list + div span');
        if (paginationText) {
            paginationText.innerText = `Showing ${visibleCount} bill${visibleCount !== 1 ? 's' : ''}`;
        }
    }
    
    function showMobileBillSummary() {
        document.getElementById('mBillSummaryPanel').classList.add('show');
        // Scroll to summary nicely if needed
        document.getElementById('mBillSummaryPanel').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    function selectMobileBill(bill) {
        if(!bill) return;
        
        // Highlight selected card
        document.querySelectorAll('.m-bill-item').forEach(el => el.classList.remove('selected'));
        const activeItem = document.querySelector(`.m-bill-item[data-bill-id="${bill.type}-${bill.id}"]`);
        if (activeItem) {
            activeItem.classList.add('selected');
        }
        
        const detailsContainer = document.getElementById('mSummaryDetails');
        detailsContainer.innerHTML = '';
        
        for (const [key, val] of Object.entries(bill.summary)) {
            detailsContainer.innerHTML += `<div class="m-panel-row"><span>${key}</span><span>₹${parseFloat(val).toFixed(2)}</span></div>`;
        }
        
        document.getElementById('mSummaryTotal').innerText = '₹' + parseFloat(bill.amount).toFixed(2);
        
        if (bill.status === 'Paid') {
            document.getElementById('mSummaryTotal').style.color = 'var(--text-dark)';
        } else {
            document.getElementById('mSummaryTotal').style.color = '#FF4B6B';
        }
        
        const downloadBtn = document.getElementById('mBtnDownloadBill');
        if (downloadBtn) {
            downloadBtn.style.display = 'flex';
            if (bill.status === 'Unpaid') {
                downloadBtn.innerHTML = "<i class='bx bx-receipt'></i> View Bill";
            } else {
                downloadBtn.innerHTML = "<i class='bx bx-download'></i> Download Bill";
            }
            downloadBtn.onclick = () => window.location.href = 'invoice.php?id=' + bill.id;
        }
        
        showMobileBillSummary();
    }
    
    // Auto-select the first unpaid bill on page load
    if (mobileBills && mobileBills.length > 0) {
        let firstBill = mobileBills.find(b => b.status === 'Unpaid') || mobileBills[0];
        // We select it, but we can instantly show it without scrolling animation
        selectMobileBill(firstBill);
        // Reset scroll position to top after auto-selecting to avoid jumping down
        setTimeout(() => { window.scrollTo(0, 0); }, 10);
    }

</script>
<?php include 'mobile_notifications.php'; ?>
