<?php
// admin/master-ledger.php
require_once "../db.php";
require_once "allocate_payment.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle Add Manual Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual_payment'])) {
    $user_id = (int)$_POST['user_id'];
    $bill_type = mysqli_real_escape_string($conn, $_POST['bill_type']);
    // For month we expect "April 2026" or similar format in DB, the form sends "2026-04"
    $raw_month = $_POST['month'];
    $month = '';
    if (!empty($raw_month)) {
        $month = date('F Y', strtotime($raw_month . '-01'));
    }
    
    $amount = (float)$_POST['amount'];
    $payment_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id'] ?? '');
    if(empty(trim($transaction_id))) {
        $prefix = ($payment_mode === 'Cash') ? 'CASH-' : (($payment_mode === 'UPI') ? 'UPI-' : 'MAN-');
        $transaction_id = $prefix . date('Ymd') . '-' . strtoupper(substr(md5($sys_tx_id), 0, 6));
    }
    
    $payment_date = date('Y-m-d');
    $payment_time = date('H:i:s');
    $admin_name = $_SESSION['admin'];
    
    $vhash = generate_payment_hash($user_id, $amount, $sys_tx_id);
    
    // We need to resolve the bill_id if possible
    $bill_id = 0;
    if ($bill_type === 'rent' || $bill_type === 'electricity' || $bill_type === 'elec_rent') {
        $bq = mysqli_query($conn, "SELECT id FROM electricity WHERE user_id=$user_id AND month='$month' LIMIT 1");
        if ($br = mysqli_fetch_assoc($bq)) {
            $bill_id = $br['id'];
        }
    }
    
    if ($bill_id > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, paid_amount, payment_mode, payment_date, payment_time, transaction_id, admin_name, sys_tx_id, verification_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isisddsssssss", $user_id, $bill_type, $bill_id, $month, $amount, $amount, $payment_mode, $payment_date, $payment_time, $transaction_id, $admin_name, $sys_tx_id, $vhash);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Manual payment recorded successfully!";
            recalculate_bill_status($conn, $bill_type, $bill_id);
        } else {
            $error_msg = "Failed to record payment.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "No bill found for the selected month. Please generate a bill first.";
    }
}


// Set up the month filter
$cy = date('Y');
$f_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n'); // numeric month
$f_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)$cy;

$month_name = date('F', mktime(0, 0, 0, $f_month, 1));
$target_month = $month_name . ' ' . $f_year; // e.g., "April 2026"

// We need to fetch all active users, and LEFT JOIN with electricity table for $target_month
$sql = "SELECT u.id as user_id, u.name, u.room_no, u.status as user_status, 
               e.id as bill_id, e.total_amount, e.amount as elec_part, 
               e.rent_amount, e.maintenance, e.dues, e.extra_charges,
               e.status as bill_status, e.elec_status, e.rent_status, e.paid_date
        FROM users u 
        LEFT JOIN electricity e ON u.id = e.user_id AND e.month = '$target_month'
        WHERE u.status = 'active' OR e.id IS NOT NULL
        ORDER BY u.room_no ASC";

$res = mysqli_query($conn, $sql);

$kpi_billed = 0;
$kpi_paid = 0;
$kpi_dues = 0;
$kpi_pending_approvals = 0;

$records = [];
while ($row = mysqli_fetch_assoc($res)) {
    $bill_amt = (float)$row['total_amount'];
    
    // Calculate total paid vs due
    $paid = 0;
    $due = $bill_amt;
    $status = 'No Bill';
    $status_color = 'var(--text-gray)';
    $status_bg = 'var(--bg-main)';
    
    if ($row['bill_id']) {
        // Calculate paid amount from payments table for this bill
        $bid = $row['bill_id'];
        $pq = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_id=$bid");
        $pr = mysqli_fetch_assoc($pq);
        $paid = (float)$pr['tp'];
        $due = max(0, $bill_amt - $paid);
        
        $kpi_billed += $bill_amt;
        $kpi_paid += $paid;
        $kpi_dues += $due;
        
        $status = ucfirst($row['bill_status']); // Paid, Partial, Due
        
        if ($status == 'Paid') {
            $status_color = '#059669'; $status_bg = '#DCFCE7';
        } elseif ($status == 'Partial') {
            $status_color = '#CA8A04'; $status_bg = '#FEF9C3';
        } else {
            $status_color = '#DC2626'; $status_bg = '#FEE2E2';
        }
        
        // Check for pending verifications
        $vq = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payment_notifications WHERE bill_id=$bid AND status='Pending'");
        $vr = mysqli_fetch_assoc($vq);
        if ($vr['cnt'] > 0) {
            $status = 'Pending Verif.';
            $status_color = '#D97706'; $status_bg = '#FEF3C7';
            $kpi_pending_approvals += $vr['cnt'];
        }
    }
    
    $row['paid'] = $paid;
    $row['due'] = $due;
    $row['display_status'] = $status;
    $row['status_color'] = $status_color;
    $row['status_bg'] = $status_bg;
    $records[] = $row;
}

// Fetch all users for dropdown with pending notification count
$users_res = mysqli_query($conn, "SELECT u.id, u.name, u.room_no, (SELECT COUNT(*) FROM payment_notifications WHERE user_id = u.id AND status='Pending') as pending_count FROM users u WHERE u.status='active' ORDER BY u.name ASC");
$all_users = [];
while ($row = mysqli_fetch_assoc($users_res)) {
    $all_users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Master Ledger - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        .ledger-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .ledger-kpi-card { background: var(--white); border-radius: 16px; padding: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 16px; box-shadow: var(--card-shadow); }
        .ledger-kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .ledger-kpi-info p { margin: 0; font-size: 13px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; }
        .ledger-kpi-info h3 { margin: 4px 0 0 0; font-size: 24px; font-weight: 800; color: var(--text-dark); }
        
        .table-wrapper { background: var(--white); border-radius: 16px; border: 1px solid var(--border); overflow-x: auto; box-shadow: var(--card-shadow); }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0,0,0,0.02); padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 600; color: var(--text-dark); vertical-align: middle; }
        tr:hover { background: rgba(0,0,0,0.01); }
        
        .status-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        
        .btn-pay { background: var(--primary-purple); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-pay:hover { filter: brightness(1.1); }
        .btn-remind { background: rgba(234, 179, 8, 0.1); color: #CA8A04; border: 1px solid rgba(234, 179, 8, 0.2); padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; display: inline-block; }
        .btn-remind:hover { background: rgba(234, 179, 8, 0.2); }
        
        .filter-bar { background: var(--white); padding: 16px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 24px; display: flex; gap: 12px; align-items: center; box-shadow: var(--card-shadow); flex-wrap: wrap; }
        .filter-bar select, .filter-bar button { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--bg-main); color: var(--text-dark); font-weight: 600; outline: none; }
        .filter-bar button { background: var(--primary-purple); color: white; border: none; cursor: pointer; transition: 0.2s; }
        .filter-bar button:hover { filter: brightness(1.1); }
        
        .filter-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; background: var(--bg-main); color: var(--text-dark); outline: none; }
        
        /* Dark Mode Overrides */
        .dark-theme .ledger-kpi-card, .dark-theme .table-wrapper, .dark-theme .filter-bar, .dark-theme th, .dark-theme td { background: var(--white); border-color: var(--border); box-shadow: none; color: var(--text-dark); }
        .dark-theme th { color: var(--text-gray); }
        .dark-theme .ledger-kpi-info h3 { color: var(--text-dark); }
        .dark-theme .filter-bar select, .dark-theme .filter-bar button:not(.btn-primary), .dark-theme .filter-input { background: var(--bg-main); border-color: var(--border); color: var(--text-dark); }
        .dark-theme tr:hover { background: rgba(255,255,255,0.02); }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
    <?php include "sidebar.php"; ?>
    <main class="main">
        <?php include 'header.php'; ?>
        
        <div style="padding: 24px;">
            <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h1 style="font-size: 26px; font-weight: 900; margin: 0; color: var(--text-dark);">Payments & Ledger</h1>
                    <p style="color: var(--text-gray); font-size: 14px; margin: 4px 0 0 0;">Complete history of payments and monthly billing status</p>
                </div>
                <div>
                    <button onclick="document.getElementById('manualPaymentModal').style.display='flex'" class="btn-pay" style="padding: 10px 20px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class='bx bx-plus'></i> Add Manual Payment
                    </button>
                </div>
            </div>
            
            <div class="view-toggle" style="background: var(--bg-main); padding: 6px; border-radius: 12px; display: inline-flex; gap: 4px; border: 1px solid var(--border); margin-bottom: 24px;">
                <a href="transactions-log.php" class="toggle-btn" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; color: var(--text-gray); transition: 0.2s;">
                    <i class='bx bx-list-ul'></i> Transaction History
                </a>
                <a href="master-ledger.php" class="toggle-btn active" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; background: var(--primary-purple); color: white; box-shadow: var(--card-shadow);">
                    <i class='bx bx-book-open'></i> Monthly Ledger
                </a>
            </div>
            
            <?php if(isset($success_msg)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-check-circle'></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle'></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <form class="filter-bar" method="GET">
                <select name="month">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo $m == $f_month ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year">
                    <?php for($y=$cy+1; $y>=$cy-2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $f_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit">Filter Ledger</button>
                <span style="color: var(--text-gray); font-size: 14px; font-weight: 600; margin-left: auto;">Showing results for: <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($target_month); ?></strong></span>
            </form>

            <div class="ledger-kpi-grid">
                <div class="ledger-kpi-card">
                    <div class="ledger-kpi-icon" style="background: rgba(108, 77, 255, 0.1); color: var(--primary-purple);">
                        <i class='bx bx-receipt'></i>
                    </div>
                    <div class="ledger-kpi-info">
                        <p>Total Billed</p>
                        <h3>₹<?php echo number_format($kpi_billed); ?></h3>
                    </div>
                </div>
                <div class="ledger-kpi-card">
                    <div class="ledger-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                        <i class='bx bx-wallet'></i>
                    </div>
                    <div class="ledger-kpi-info">
                        <p>Total Paid</p>
                        <h3>₹<?php echo number_format($kpi_paid); ?></h3>
                    </div>
                </div>
                <div class="ledger-kpi-card">
                    <div class="ledger-kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                        <i class='bx bx-error-circle'></i>
                    </div>
                    <div class="ledger-kpi-info">
                        <p>Remaining Dues</p>
                        <h3>₹<?php echo number_format($kpi_dues); ?></h3>
                    </div>
                </div>
                <div class="ledger-kpi-card">
                    <div class="ledger-kpi-icon" style="background: rgba(234, 179, 8, 0.1); color: #CA8A04;">
                        <i class='bx bx-time-five'></i>
                    </div>
                    <div class="ledger-kpi-info">
                        <p>Pending Approvals</p>
                        <h3><?php echo $kpi_pending_approvals; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Renter</th>
                            <th>Total Bill</th>
                            <th>Paid</th>
                            <th>Remaining Due</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $rec): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary-purple);"><?php echo htmlspecialchars($rec['room_no']); ?></td>
                            <td style="max-width: 130px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%; vertical-align: middle;" title="<?php echo htmlspecialchars($rec['name']); ?>">
                                    <?php echo htmlspecialchars($rec['name']); ?>
                                </div>
                                <?php if($rec['user_status'] == 'inactive') echo '<span style="color:#EF4444; font-size:10px; border:1px solid #EF4444; border-radius:4px; padding:2px 4px; margin-left:6px; vertical-align: middle;">Inactive</span>'; ?>
                            </td>
                            <td>₹<?php echo number_format((float)$rec['total_amount'], 2); ?></td>
                            <td style="color:#10B981;">₹<?php echo number_format((float)$rec['paid'], 2); ?></td>
                            <td style="color:#EF4444;">₹<?php echo number_format((float)$rec['due'], 2); ?></td>
                            <td>
                                <span class="status-pill" style="background: <?php echo $rec['status_bg']; ?>; color: <?php echo $rec['status_color']; ?>;">
                                    <?php echo htmlspecialchars($rec['display_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($rec['bill_id']): ?>
                                    <?php if ($rec['display_status'] == 'Pending Verif.'): ?>
                                        <a href="payment-verifications.php" class="btn-pay" style="text-decoration:none; display:inline-block; background: #D97706;">Verify</a>
                                    <?php elseif ($rec['due'] > 0): ?>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <button class="btn-pay" onclick="openPaymentModal(<?php echo $rec['user_id']; ?>, 'elec_rent', <?php echo $rec['due']; ?>, '<?php echo sprintf('%04d-%02d', $f_year, $f_month); ?>')">Pay</button>
                                            <a href="#" onclick="alert('Reminder sent!'); return false;" class="btn-remind">Remind</a>
                                        </div>
                                    <?php else: ?>
                                        <a href="../renter/receipt.php?uid=<?php echo $rec['user_id']; ?>&month=<?php echo urlencode(sprintf('%04d-%02d', $f_year, $f_month)); ?>&bill_id=<?php echo $rec['bill_id']; ?>" class="btn-outline" style="padding:6px 12px; font-size:12px; border-radius:8px; text-decoration:none;">Receipt</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-gray); font-size:12px;">No Action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($records) == 0): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-gray);">No renters found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <!-- Global Manual Payment Modal -->
    <div id="manualPaymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: var(--white); border-radius: 24px; padding: 32px; width: 100%; max-width: 500px; position: relative; box-shadow: var(--card-shadow);">
            <button onclick="document.getElementById('manualPaymentModal').style.display='none'" style="position: absolute; top: 24px; right: 24px; background: none; border: none; font-size: 24px; color: var(--text-gray); cursor: pointer;"><i class='bx bx-x'></i></button>
            <h2 style="margin: 0 0 24px 0; font-size: 20px; color: var(--text-dark);">Record Manual Payment</h2>
            
            <form method="POST">
                <input type="hidden" name="add_manual_payment" value="1">
                
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Select Renter</label>
                    <select name="user_id" id="modal_user_id" required class="filter-input" style="width: 100%;">
                        <option value="">-- Choose Renter --</option>
                        <?php foreach($all_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" data-pending="<?php echo $u['pending_count']; ?>"><?php echo htmlspecialchars($u['name']); ?> (Room <?php echo $u['room_no']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div id="pending_notification_warning" style="display: none; margin-top: 8px; padding: 10px; background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; border-radius: 8px; font-size: 13px; font-weight: 600;">
                        ⚠️ <span id="pending_count_text"></span> pending payment notifications! Check the <a href="payment-verifications.php" style="color: #991B1B; text-decoration: underline;">verifications page</a> before adding a manual payment.
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Bill Type</label>
                        <select name="bill_type" id="modal_bill_type" required class="filter-input" style="width: 100%;">
                            <option value="elec_rent">Total Bill (Rent+Elec)</option>
                            <option value="rent">Rent Only</option>
                            <option value="electricity">Electricity Only</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Bill Month</label>
                        <input type="month" name="month" id="modal_month" required class="filter-input" style="width: 100%;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Amount (₹)</label>
                        <input type="number" name="amount" id="modal_amount" step="0.01" required class="filter-input" style="width: 100%;" placeholder="0.00">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Payment Mode</label>
                        <select name="payment_mode" required class="filter-input" style="width: 100%;">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Transaction ID / Ref (Optional)</label>
                    <input type="text" name="transaction_id" class="filter-input" style="width: 100%;" placeholder="e.g. UTR or Cash Receipt No">
                </div>
                
                <button type="submit" id="btn_record_payment" class="btn-primary" style="width: 100%; padding: 12px; font-size: 15px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700;">Record Payment</button>
            </form>
        </div>
    </div>

    <script>
        function checkPendingNotifications() {
            const select = document.getElementById('modal_user_id');
            const selectedOption = select.options[select.selectedIndex];
            const pendingCount = parseInt(selectedOption.getAttribute('data-pending') || '0', 10);
            
            const warningDiv = document.getElementById('pending_notification_warning');
            const countText = document.getElementById('pending_count_text');
            
            if (pendingCount > 0) {
                countText.textContent = pendingCount;
                warningDiv.style.display = 'block';
            } else {
                warningDiv.style.display = 'none';
            }
        }

        document.getElementById('modal_user_id').addEventListener('change', checkPendingNotifications);

        function openPaymentModal(userId, billType, amount, month) {
            document.getElementById('modal_user_id').value = userId;
            checkPendingNotifications(); // Check when opened via button
            document.getElementById('modal_bill_type').value = billType;
            document.getElementById('modal_amount').value = amount;
            document.getElementById('modal_month').value = month;
            document.getElementById('manualPaymentModal').style.display = 'flex';
        }
        
        document.getElementById('manualPaymentModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });

        // Prevent double submit
        document.querySelector('#manualPaymentModal form').addEventListener('submit', function() {
            const btn = document.getElementById('btn_record_payment');
            btn.disabled = true;
            btn.textContent = 'Processing...';
        });
    </script>
</body>
</html>
