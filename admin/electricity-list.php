<?php
// admin/electricity-list.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$filter_month = trim($_GET['month'] ?? '');
$filter_user = trim($_GET['user'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT e.*, u.name, u.room_no FROM electricity e LEFT JOIN users u ON e.user_id = u.id WHERE 1=1";
$params = []; $types = "";
if ($filter_month !== '') {
    $sql .= " AND e.month = ?"; $params[] = $filter_month; $types .= "s";
}
if ($filter_user !== '') {
    $sql .= " AND (u.name LIKE ?)"; $lk = "%{$filter_user}%"; $params[] = $lk; $types .= "s";
}
if ($filter_status !== '') {
    $sql .= " AND e.status = ?"; $params[] = $filter_status; $types .= "s";
}
$sql .= " ORDER BY e.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = []; while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
mysqli_stmt_close($stmt);

$months_q = mysqli_query($conn, "SELECT DISTINCT month FROM electricity ORDER BY id DESC LIMIT 24");
$months = []; while ($m = mysqli_fetch_assoc($months_q)) $months[] = $m['month'];

// --- Recharge Tracking Logic (PREVIOUS MONTH) ---
// Since bills are typically generated in the current month *for* the previous month's usage.
$prev_month_full = (new DateTime('first day of last month'))->format('F Y'); // e.g., February 2026
$prev_month_db = (new DateTime('first day of last month'))->format('Y-m'); // e.g., 2026-02

// 1. Calculate Total Recharged LAST month
$recharge_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM meter_recharges WHERE DATE_FORMAT(recharge_date, '%Y-%m') = '$prev_month_db'");
$recharge_row = mysqli_fetch_assoc($recharge_q);
$total_recharged = $recharge_row['total'] ?? 0;

// 2. Calculate Total Billed for LAST month (from all renters)
$billed_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM electricity WHERE month = '$prev_month_db' OR month = '$prev_month_full'");
$billed_row = mysqli_fetch_assoc($billed_q);
$total_billed = $billed_row['total'] ?? 0;

$net_balance = $total_billed - $total_recharged;
$balance_color = $net_balance >= 0 ? '#10B981' : '#EF4444';

$admin_user = s($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Electricity Records | Rent Manager</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        /* Card View for Electricity Records on Mobile */
        @media (max-width: 768px) {
            .panel { background: transparent !important; box-shadow: none !important; padding: 0 !important; border: none !important; margin: 0 !important; width: 100% !important; }
            .panel-header { background: var(--white); border-radius: 16px; padding: 16px !important; margin-bottom: 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow); width: 100% !important; }
            
            .panel-header div.filter-row { flex-direction: row !important; flex-wrap: nowrap !important; }
            .panel-header div.filter-row select, .panel-header div.filter-row input { width: auto !important; }
            .panel-header div.filter-actions { flex-direction: row !important; }
            .panel-header div.filter-actions button, .panel-header div.filter-actions a { width: auto !important; flex: 1 !important; }
            
            .table-responsive { overflow: visible !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }

            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            
            #elecTable tr {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 16px;
                margin-bottom: 16px;
                box-shadow: var(--card-shadow);
                width: 100% !important;
                position: relative;
            }

            #elecTable td {
                padding: 0 !important;
                border: none !important;
                margin-bottom: 12px;
                width: 100% !important;
            }

            /* Resident & Room */
            #elecTable td:nth-child(1) { 
                margin-bottom: 16px;
                border-bottom: 1px dotted var(--border) !important;
                padding-bottom: 12px !important;
                font-size: 16px;
                padding-right: 70px !important; /* space for status */
            }

            /* Status badge */
            #elecTable td:nth-child(6) {
                position: absolute;
                top: 16px;
                right: 16px;
                width: auto !important;
                margin: 0;
            }

            #elecTable tr:hover td {
                background: transparent !important;
            }

            /* Middle items */
            #elecTable td:nth-child(2), 
            #elecTable td:nth-child(4), 
            #elecTable td:nth-child(5) {
                display: inline-block !important;
                width: 26% !important;
                margin-right: 0;
                margin-bottom: 12px;
                font-size: 14px;
                vertical-align: top;
            }

            #elecTable td:nth-child(2)::before { content: "Month: "; font-weight: 600; color: var(--text-gray); font-size: 11px; display: block; margin-bottom: 2px; }
            #elecTable td:nth-child(4)::before { content: "Usage: "; font-weight: 600; color: var(--text-gray); font-size: 11px; display: block; margin-bottom: 2px; }
            #elecTable td:nth-child(5)::before { content: "Bill: "; font-weight: 600; color: var(--text-gray); font-size: 11px; display: block; margin-bottom: 2px; }

            /* Meter Photo */
            #elecTable td:nth-child(3) {
                display: inline-block !important;
                width: auto !important;
                float: right;
                text-align: right !important;
                margin-top: -10px;
            }

            /* Actions */
            #elecTable td:last-child { 
                margin-top: 12px;
                padding-top: 16px !important;
                border-top: 1px dashed var(--border) !important;
                clear: both;
            }
            #elecTable td:last-child div {
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 8px !important;
                width: 100% !important;
            }
            #elecTable td:last-child div a, 
            #elecTable td:last-child div button {
                padding: 10px 4px !important;
                justify-content: center;
                text-align: center;
                margin: 0 !important;
            }
            #elecTable td:last-child div button.btn-primary {
                grid-column: span 3;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1><i class='bx bx-bolt' style="color: var(--primary-purple); font-size: 32px; vertical-align: middle;"></i> Electricity Records</h1>
        <p>Viewing all historical utility billing</p>
    </div>

    <!-- Monthly Tracking HUD -->
    <div class="kpi-grid animate-up" style="margin-bottom: 24px;">
        <div class="kpi-card" style="border-left: 4px solid #F59E0B;">
            <div class="kpi-header">
                <i class='bx bx-wallet kpi-icon' style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);"></i>
                <div class="trend" style="background: rgba(245, 158, 11, 0.1); color: #B45309;">Expense</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($total_recharged, 0); ?></div>
            <div class="kpi-label">Meter Recharged (<?php echo (new DateTime('first day of last month'))->format('M'); ?>)</div>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #10B981;">
            <div class="kpi-header">
                <i class='bx bx-trending-up kpi-icon' style="color: #10B981; background: rgba(16, 185, 129, 0.1);"></i>
                <div class="trend trend-up">Revenue</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($total_billed, 0); ?></div>
            <div class="kpi-label">Resident Bills (<?php echo (new DateTime('first day of last month'))->format('M'); ?>)</div>
        </div>
        <div class="kpi-card" style="border-left: 4px solid <?php echo $balance_color; ?>;">
            <div class="kpi-header">
                <i class='bx bx-analyse kpi-icon' style="color: <?php echo $balance_color; ?>; background: <?php echo $balance_color; ?>15;"></i>
                <div class="trend" style="background: <?php echo $balance_color; ?>15; color: <?php echo $balance_color; ?>;">Status</div>
            </div>
            <div class="kpi-value" style="color: <?php echo $balance_color; ?>;">
                ₹<?php echo number_format(abs($net_balance), 0); ?>
                <small style="font-size: 12px; opacity: 0.7;"><?php echo $net_balance >= 0 ? '(Profit)' : '(Loss)'; ?></small>
            </div>
            <div class="kpi-label">Monthly Recovery Status</div>
        </div>
    </div>

    <div class="panel animate-up" id="records-panel">
        <div class="panel-header">
            <form id="filter-form" onsubmit="event.preventDefault(); submitFilterForm();" method="GET" style="display: flex; gap: 12px; align-items: center; flex: 1; flex-wrap: wrap; width: 100%;">
                <div class="filter-row" style="display: flex; gap: 6px; flex: 1; flex-wrap: nowrap; width: 100%;">
                    <select name="month" class="btn-outline" style="flex: 1; min-width: 0; padding: 8px 6px; font-size: 12px; text-align: left;">
                        <option value="">All Months</option>
                        <?php foreach ($months as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php if ($filter_month == $m) echo 'selected'; ?>><?php echo htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name="user" placeholder="Resident..." value="<?php echo htmlspecialchars($filter_user); ?>" class="btn-outline" style="flex: 1.2; min-width: 0; padding: 8px 6px; font-size: 12px; text-align: left; cursor: text;">
                    <select name="status" class="btn-outline" style="flex: 0.8; min-width: 0; padding: 8px 6px; font-size: 12px; text-align: left;">
                        <option value="">Status</option>
                        <option value="Paid" <?php if($filter_status == 'Paid') echo 'selected'; ?>>Paid</option>
                        <option value="Due" <?php if($filter_status == 'Due') echo 'selected'; ?>>Due</option>
                    </select>
                </div>
                <div class="filter-actions" style="display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="electricity-list.php#records-panel" class="btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Month</th>
                        <th>Meter Photo</th>
                        <th>Units</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="elecTable">
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">No records found.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <?php echo htmlspecialchars($r['name']); ?>
                            <div style="font-size: 11px; font-weight: 400; color: var(--text-gray);">Room <?php echo htmlspecialchars($r['room_no']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($r['month']); ?></td>
                        <td style="text-align:center;">
                            <?php 
                            $main_img = !empty($r['meter_screenshot']) ? $r['meter_screenshot'] : ($r['meter_screenshot_orig'] ?? null);
                            if($main_img): 
                                $orig_img = !empty($r['meter_screenshot_orig']) ? $r['meter_screenshot_orig'] : $main_img;
                                $thumb_img = !empty($r['meter_screenshot_thumb']) ? $r['meter_screenshot_thumb'] : $main_img;
                            ?>
                                <div style="position: relative; width: 60px; height: 40px; cursor: pointer; border-radius: 8px; overflow: hidden; border: 1px solid var(--border);" onclick="viewImage('../uploads/meter_readings/<?php echo htmlspecialchars($main_img); ?>', '../uploads/meter_readings/<?php echo htmlspecialchars($orig_img); ?>')">
                                    <img src="../uploads/meter_readings/<?php echo htmlspecialchars($thumb_img); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div style="position: absolute; bottom: 0; right: 0; background: rgba(0,0,0,0.5); color: white; font-size: 8px; padding: 2px 4px;"><i class='bx bx-zoom-in'></i></div>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 10px; color: var(--text-gray);">No Photo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['units']); ?> Units</td>
                        <td style="font-weight: 700; color: var(--text-dark);">₹<?php echo number_format($r['amount'], 2); ?></td>
                        <td><span class="badge <?php echo $r['status'] == 'Paid' ? 'badge-paid' : 'badge-due'; ?>"><?php echo $r['status']; ?></span></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="slip.php?elec_id=<?php echo $r['id']; ?>" class="btn-outline" style="padding: 6px 12px; font-size: 11px;" target="_blank">Slip</a>
                                <a href="update-electricity.php?user_id=<?php echo $r['user_id'] ?? 0; ?>&id=<?php echo $r['id']; ?>" class="btn-outline" style="padding: 6px 8px; font-size: 11px;"><i class='bx bx-edit'></i></a>
                                <a href="delete-bill.php?id=<?php echo $r['id']; ?>" class="btn-outline" style="padding: 6px 8px; font-size: 11px; color: #EF4444; border-color: #FCA5A5;" onclick="return confirm('Delete this utility bill completely?');"><i class='bx bx-trash'></i></a>
                                <?php if($r['status'] != 'Paid'): ?>
                                    <button onclick="openPaymentModal(<?php echo $r['id']; ?>, <?php echo $r['amount']; ?>, '<?php echo addslashes($r['month']); ?>', '<?php echo addslashes($r['name']); ?>')" class="btn-primary" style="padding: 6px 12px; font-size: 11px;">Mark Paid</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function submitFilterForm() {
        const form = document.getElementById('filter-form');
        let url = new URL(window.location.href);
        url.searchParams.set('month', form.querySelector('[name="month"]').value);
        url.searchParams.set('user', form.querySelector('[name="user"]').value);
        url.searchParams.set('status', form.querySelector('[name="status"]').value);
        url.hash = 'records-panel';
        window.location.href = url.toString();
    }

    document.getElementById('liveFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('#elecTable tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    function viewImage(cropUrl, origUrl) {
        const modal = document.getElementById('imageViewerModal');
        const cropImg = document.getElementById('viewerCrop');
        const origImg = document.getElementById('viewerOrig');
        const downloadOrig = document.getElementById('downloadOrig');
        
        cropImg.src = cropUrl;
        origImg.src = origUrl;
        downloadOrig.href = origUrl;
        
        modal.style.display = 'flex';
        showTab('crop');
    }

    function closeImageViewer() {
        document.getElementById('imageViewerModal').style.display = 'none';
    }

    function showTab(tab) {
        const cropBtn = document.getElementById('tabCropBtn');
        const origBtn = document.getElementById('tabOrigBtn');
        const cropImg = document.getElementById('viewerCrop');
        const origImg = document.getElementById('viewerOrig');
        
        if (tab === 'crop') {
            cropImg.style.display = 'block';
            origImg.style.display = 'none';
            cropBtn.style.background = 'var(--primary-purple)';
            cropBtn.style.color = 'white';
            origBtn.style.background = 'transparent';
            origBtn.style.color = 'var(--text-gray)';
        } else {
            cropImg.style.display = 'none';
            origImg.style.display = 'block';
            origBtn.style.background = 'var(--primary-purple)';
            origBtn.style.color = 'white';
            cropBtn.style.background = 'transparent';
            cropBtn.style.color = 'var(--text-gray)';
        }
    }
</script>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10001; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
    <div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 15px;">
        <a id="downloadOrig" href="#" download class="btn-primary" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);"><i class='bx bx-download'></i></a>
        <button onclick="closeImageViewer()" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 20px;"><i class='bx bx-x'></i></button>
    </div>
    
    <div style="margin-bottom: 20px; display: flex; gap: 10px; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 12px;">
        <button id="tabCropBtn" onclick="showTab('crop')" class="btn-outline" style="border: none; padding: 8px 16px; font-size: 13px;">Cropped View</button>
        <button id="tabOrigBtn" onclick="showTab('orig')" class="btn-outline" style="border: none; padding: 8px 16px; font-size: 13px;">Full Photo</button>
    </div>

    <div style="max-width: 95%; max-height: 80vh; overflow: auto; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
        <img id="viewerCrop" src="" style="max-width: 100%; display: block;">
        <img id="viewerOrig" src="" style="max-width: 100%; display: none;">
    </div>
</div>

    <!-- Payment Mode Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 650px; width: 100%; padding: 32px; background: #FFFFFF; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-radius: 20px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #E2E8F0; padding-bottom: 16px;">
                <div style="width: 48px; height: 48px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-credit-card' style="font-size: 24px; color: #10B981;"></i>
                </div>
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #1E293B; margin: 0;">Record Payment</h3>
                    <p id="paymentBillInfo" style="color: #64748B; font-size: 13px; margin: 4px 0 0 0;">Select payment method and amount.</p>
                </div>
            </div>
            
            <form action="mark-paid.php" method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="id" id="paymentBillId">
                <input type="hidden" name="type" value="electricity">
                <input type="hidden" name="bill_amount" id="paymentBillAmount">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Payment Mode</label>
                            <select name="payment_mode" id="paymentMode" required onchange="handlePaymentModeChange()" style="width: 100%; padding: 12px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; transition: all 0.2s; outline: none;">
                                <option value="Online">Online</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Amount Paid (₹)</label>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" placeholder="Enter amount" required style="width: 100%; padding: 12px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 15px; font-weight: 600; color: #334155; background: #F8FAFC; transition: all 0.2s; outline: none;">
                            <small style="color: #94A3B8; font-size: 12px; display: block; margin-top: 6px;">Partial payments are allowed.</small>
                        </div>
                    </div>
                    
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Date</label>
                                <input type="date" name="payment_date" id="paymentDateInput" required style="width: 100%; padding: 11px 12px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; outline: none;">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Time</label>
                                <input type="time" name="payment_time" id="paymentTimeInput" required style="width: 100%; padding: 11px 12px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; outline: none;">
                            </div>
                        </div>

                        <div class="form-group" id="cashReceiverGroup" style="display: none; margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Cash Received By</label>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 11px 16px; border: 1px solid #E2E8F0; border-radius: 10px; background: #F1F5F9;">
                                <i class='bx bx-user' style="color: #6366F1; font-size: 18px;"></i>
                                <span style="font-size: 15px; font-weight: 600; color: #0F172A;"><?php echo htmlspecialchars($admin_user); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 16px; margin-top: 12px; padding-top: 24px; border-top: 1px solid #E2E8F0;">
                    <button type="button" onclick="closePaymentModal()" class="btn-outline" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; color: #64748B; border-color: #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn-primary" style="background: #10B981; padding: 12px 28px; border-radius: 10px; font-weight: 600; border: none;">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

<script>
    function handlePaymentModeChange() {
        const mode = document.getElementById('paymentMode').value;
        const receiverGroup = document.getElementById('cashReceiverGroup');
        if(receiverGroup) {
            receiverGroup.style.display = (mode === 'Cash') ? 'block' : 'none';
        }
    }

    function openPaymentModal(id, amount, month, name) {
        document.getElementById('paymentBillId').value = id;
        document.getElementById('paymentBillAmount').value = amount;
        document.getElementById('paidAmountInput').value = amount;
        document.getElementById('paymentBillInfo').textContent = `Electricity for ${name} (${month}) - ₹${amount}`;
        
        const now = new Date();
        document.getElementById('paymentDateInput').value = now.toISOString().split('T')[0];
        document.getElementById('paymentTimeInput').value = now.toTimeString().slice(0, 5);
        
        document.getElementById('paymentModal').style.display = 'flex';
        handlePaymentModeChange();
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }
</script>

</body>
</html>
