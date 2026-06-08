<?php
// admin/payment-verifications.php
require_once "../db.php";
session_start();
require_once "utils_mailer.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
$admin_user = s($_SESSION['admin'] ?? 'Admin');


$success = "";
$error = "";

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bill_type ENUM('rent', 'electricity', 'total', 'advance') NOT NULL,
    bill_id INT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_id VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'UPI',
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed.";
    } else {
        $id = (int)$_POST['id'];
        $action = $_POST['action'];

        // Fetch notification info
        $stmt = mysqli_prepare($conn, "SELECT * FROM payment_notifications WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $notif = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($notif && $notif['status'] === 'Pending') {
            if ($action === 'approve') {
                mysqli_begin_transaction($conn);
                try {
                    // Update the bill status
                    if ($notif['bill_type'] === 'rent' && $notif['bill_id']) {
                        mysqli_query($conn, "UPDATE rent SET status = 'Paid' WHERE id = " . $notif['bill_id']);
                    } elseif ($notif['bill_type'] === 'electricity' && $notif['bill_id']) {
                        mysqli_query($conn, "UPDATE electricity SET status = 'Paid' WHERE id = " . $notif['bill_id']);
                    } elseif ($notif['bill_type'] === 'total') {
                        mysqli_query($conn, "UPDATE rent SET status = 'Paid' WHERE user_id = " . $notif['user_id'] . " AND status = 'Due'");
                        mysqli_query($conn, "UPDATE electricity SET status = 'Paid' WHERE user_id = " . $notif['user_id'] . " AND status = 'Due'");
                    } elseif ($notif['bill_type'] === 'advance') {
                        $pay_query = "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, adjustment_amount, adjustment_type, payment_date, payment_time) VALUES (?, 'advance', 0, 'Advance', ?, 'Online', ?, 0, NULL, CURDATE(), CURTIME())";
                        $pay_stmt = mysqli_prepare($conn, $pay_query);
                        mysqli_stmt_bind_param($pay_stmt, "idd", $notif['user_id'], $notif['amount'], $notif['amount']);
                        mysqli_stmt_execute($pay_stmt);
                        mysqli_stmt_close($pay_stmt);
                    }

                    mysqli_query($conn, "UPDATE payment_notifications SET status = 'Approved' WHERE id = $id");
                    
                    // Send Email Receipt
                    $qUser = mysqli_query($conn, "SELECT name, email FROM users WHERE id = " . $notif['user_id']);
                    if ($uRow = mysqli_fetch_assoc($qUser)) {
                        if (!empty($uRow['email'])) {
                            $details = ["Payment for " . ucfirst($notif['bill_type']) . " via " . ($notif['payment_method'] ?? 'UPI') . " (Ref: " . $notif['transaction_id'] . ")"];
                            
                            $pdf_path = null;
                            if ($notif['bill_type'] === 'electricity' && $notif['bill_id']) {
                                $qElec = mysqli_query($conn, "SELECT bill_file FROM electricity WHERE id = " . $notif['bill_id']);
                                if ($eRow = mysqli_fetch_assoc($qElec)) {
                                    $pdf_path = !empty($eRow['bill_file']) ? $eRow['bill_file'] : null;
                                }
                            }
                            send_payment_receipt_email($uRow['email'], $uRow['name'], $details, $notif['amount'], $pdf_path);
                        }
                    }

                    mysqli_commit($conn);
                    $success = "Payment approved and bill(s) marked as Paid!";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Failed to process approval: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $admin_note = trim($_POST['admin_note'] ?? '');
                $stmt = mysqli_prepare($conn, "UPDATE payment_notifications SET status = 'Rejected', admin_note = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $admin_note, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $error = "Payment notification rejected.";
            }
        }
    }
}

// Filter Inputs
$f_search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));
$f_status = mysqli_real_escape_string($conn, $_GET['status'] ?? 'All');
$f_month = mysqli_real_escape_string($conn, $_GET['month'] ?? 'All');
$f_year = mysqli_real_escape_string($conn, $_GET['year'] ?? 'All');
$f_mode = mysqli_real_escape_string($conn, $_GET['mode'] ?? 'All');
$f_start = mysqli_real_escape_string($conn, $_GET['start_date'] ?? '');
$f_end = mysqli_real_escape_string($conn, $_GET['end_date'] ?? '');
$f_sort = mysqli_real_escape_string($conn, $_GET['sort'] ?? 'latest');

$where = ["1=1"];

if ($f_search !== '') {
    $where[] = "(u.name LIKE '%$f_search%' OR p.transaction_id LIKE '%$f_search%')";
}
if ($f_status !== 'All') {
    $where[] = "p.status = '$f_status'";
}
if ($f_month !== 'All') {
    $where[] = "MONTH(p.created_at) = '$f_month'";
}
if ($f_year !== 'All') {
    $where[] = "YEAR(p.created_at) = '$f_year'";
}
if ($f_mode !== 'All') {
    $where[] = "p.payment_method = '$f_mode'";
}
if ($f_start !== '') {
    $where[] = "DATE(p.created_at) >= '$f_start'";
}
if ($f_end !== '') {
    $where[] = "DATE(p.created_at) <= '$f_end'";
}

$where_clause = implode(" AND ", $where);

$order_clause = "ORDER BY p.status = 'Pending' DESC, p.created_at DESC";
if ($f_sort === 'oldest') {
    $order_clause = "ORDER BY p.status = 'Pending' DESC, p.created_at ASC";
} elseif ($f_sort === 'latest') {
    $order_clause = "ORDER BY p.status = 'Pending' DESC, p.created_at DESC";
}

$sql = "SELECT p.*, u.name as renter_name, u.room_no 
        FROM payment_notifications p 
        JOIN users u ON p.user_id = u.id 
        WHERE $where_clause 
        $order_clause";
        
$res = mysqli_query($conn, $sql);
$notifs = [];
while ($row = mysqli_fetch_assoc($res)) $notifs[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verifications | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        @media (max-width: 768px) {
            .welcome { text-align: center !important; }
            .panel:not(.filter-panel) { background: transparent !important; box-shadow: none !important; padding: 0 !important; border: none !important; margin: 0 !important; width: 100% !important; }
            .filter-panel { padding: 16px !important; margin-bottom: 24px !important; background: var(--white) !important; border-radius: 20px !important; box-shadow: var(--card-shadow) !important; }
            .table-responsive { overflow: visible !important; }
            
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            
            tbody tr {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: var(--card-shadow);
                position: relative;
            }

            tbody tr:hover td {
                background: transparent !important;
            }

            tbody td {
                padding: 0 !important;
                border: none !important;
                margin-bottom: 15px;
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                font-size: 14px;
            }

            tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-gray);
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
            }

            tbody td:first-child { 
                margin-bottom: 20px;
                padding-bottom: 15px !important;
                border-bottom: 1px dashed var(--border) !important;
                display: block !important;
            }
            tbody td:first-child::before { display: none; }

            tbody td[data-label="Action"] {
                margin-bottom: 0;
                margin-top: 10px;
                padding-top: 15px !important;
                border-top: 1px solid var(--border) !important;
                display: block !important;
            }
            tbody td[data-label="Action"]::before { display: none; }
            
            .btn-primary, .btn-outline {
                width: 100% !important;
                justify-content: center !important;
                padding: 12px !important;
            }
        }
        
        /* Filter Grid Layout */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            align-items: flex-end;
        }
        .filter-search { grid-column: span 2; }
        .filter-buttons { display: flex; gap: 8px; grid-column: 1 / -1; }
        
        @media (max-width: 768px) {
            .filter-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-search { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .filter-grid { grid-template-columns: 1fr; }
            .filter-search { grid-column: span 1; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <header class="header">
        <div class="header-content">
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search transactions...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="welcome animate-up">
        <h1><i class='bx bx-check-shield' style="color: var(--primary-purple); font-size: 32px; vertical-align: middle;"></i> Payment Verifications</h1>
        <p>Verify Payment via UPI Transaction Reference (UTR)</p>
    </div>

    <?php if ($success): ?>
        <div class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7;">
            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2;">
            <i class='bx bx-error-circle'></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="panel animate-up filter-panel" style="margin-bottom: 24px; padding: 20px;">
        <form method="GET" action="" class="filter-grid">
            <div class="filter-search">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Search (Name / UTR)</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="Search..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
                    <option value="All" <?php if($f_status=='All') echo 'selected';?>>All Statuses</option>
                    <option value="Pending" <?php if($f_status=='Pending') echo 'selected';?>>Pending</option>
                    <option value="Approved" <?php if($f_status=='Approved') echo 'selected';?>>Approved</option>
                    <option value="Rejected" <?php if($f_status=='Rejected') echo 'selected';?>>Rejected</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Month</label>
                <select name="month" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
                    <option value="All" <?php if($f_month=='All') echo 'selected';?>>All Months</option>
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php if($f_month==$m) echo 'selected';?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Year</label>
                <select name="year" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
                    <option value="All" <?php if($f_year=='All') echo 'selected';?>>All Years</option>
                    <?php $cy = date('Y'); for($y=$cy; $y>=$cy-5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php if($f_year==$y) echo 'selected';?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Mode</label>
                <select name="mode" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
                    <option value="All" <?php if($f_mode=='All') echo 'selected';?>>All Modes</option>
                    <option value="UPI" <?php if($f_mode=='UPI') echo 'selected';?>>UPI</option>
                    <option value="Bank Transfer" <?php if($f_mode=='Bank Transfer') echo 'selected';?>>Bank Transfer</option>
                    <option value="Cash" <?php if($f_mode=='Cash') echo 'selected';?>>Cash</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($f_start); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($f_end); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Sort By</label>
                <select name="sort" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-main); color: var(--text-dark);">
                    <option value="latest" <?php if($f_sort=='latest') echo 'selected';?>>Latest First</option>
                    <option value="oldest" <?php if($f_sort=='oldest') echo 'selected';?>>Oldest First</option>
                </select>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; padding: 10px; border-radius: 8px;"><i class='bx bx-filter-alt'></i> Apply</button>
                <a href="payment-verifications.php" class="btn-outline" style="flex: 1; justify-content: center; padding: 10px; border-radius: 8px;"><i class='bx bx-reset'></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="panel animate-up">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Bill Info</th>
                        <th>Amount</th>
                        <th>Transaction ID (UTR)</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifs)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">No payment notifications yet.</td></tr>
                    <?php else: foreach ($notifs as $n): ?>
                    <tr style="<?php echo $n['status'] == 'Pending' ? 'background: rgba(98, 75, 255, 0.02);' : ''; ?>">
                        <td data-label="Resident">
                            <div style="font-weight: 600;"><?php echo s($n['renter_name']); ?></div>
                            <div style="font-size: 11px; color: var(--text-gray);">Room <?php echo s($n['room_no']); ?></div>
                        </td>
                        <td data-label="Bill Info">
                            <span style="text-transform: capitalize; font-weight: 600;"><?php echo s($n['bill_type']); ?></span>
                            <?php if($n['bill_id']): ?>
                                <div style="font-size: 11px; color: var(--text-gray);">ID: #<?php echo s($n['bill_id']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Amount" style="font-weight: 700; color: var(--primary-purple);">₹<?php echo number_format($n['amount'], 2); ?></td>
                        <td data-label="UTR ID">
                            <code style="background: var(--bg-main); padding: 4px 8px; border-radius: 6px; font-weight: 700; color: var(--text-dark);"><?php echo s($n['transaction_id']); ?></code>
                        </td>
                        <td data-label="Submitted"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></td>
                        <td data-label="Status">
                            <span class="badge <?php 
                                if($n['status'] == 'Pending') echo 'badge-due'; 
                                elseif($n['status'] == 'Approved') echo 'badge-paid'; 
                                else echo 'badge-rejected'; 
                            ?>">
                                <?php echo $n['status']; ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <?php if($n['status'] == 'Pending'): ?>
                                <div style="display: flex; gap: 8px;">
                                    <form action="" method="POST" style="flex: 1;">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size: 11px; background: #10B981; width: 100%;" onclick="return confirm('Confirm this payment matching your bank statement?')">Approve</button>
                                    </form>
                                    <form action="" method="POST" style="flex: 1;" id="rejectForm_<?php echo $n['id']; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="admin_note" id="adminNote_<?php echo $n['id']; ?>" value="">
                                        <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 11px; color: #EF4444; border-color: #FEE2E2; width: 100%;" onclick="openRejectModal(<?php echo $n['id']; ?>)">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 11px; color: var(--text-gray);"><?php echo $n['status']; ?> on <?php echo date('M d'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
    .badge-rejected { background: #FEF2F2; color: #EF4444; }
</style>
    <!-- Rejection Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 24px; position: relative; background: var(--white); border-radius: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: #EF4444; display: flex; align-items: center; gap: 8px;"><i class='bx bx-error-circle'></i> Reject Payment</h2>
                <i class='bx bx-x' onclick="closeRejectModal()" style="font-size: 26px; cursor: pointer; color: var(--text-gray);"></i>
            </div>
            <p style="font-size: 13px; color: var(--text-gray); margin-bottom: 16px; line-height: 1.5;">Please provide a clear reason for rejecting this payment. The renter will see this reason on their dashboard.</p>
            
            <textarea id="rejectReasonInput" placeholder="e.g. UTR mismatch, Insufficient amount, etc." style="width: 100%; padding: 14px; border: 1px solid var(--border); border-radius: 12px; background: var(--bg-main); color: var(--text-dark); outline: none; font-size: 14px; min-height: 100px; margin-bottom: 24px; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn-outline" onclick="closeRejectModal()" style="flex: 1; justify-content: center; padding: 12px; font-size: 14px; border-radius: 12px;">Cancel</button>
                <button type="button" class="btn-primary" onclick="submitRejectForm()" style="flex: 1; justify-content: center; background: #EF4444; padding: 12px; font-size: 14px; border: none; border-radius: 12px;">Reject</button>
            </div>
        </div>
    </div>

<script>
    document.querySelector('.search-bar input')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    let currentRejectFormId = null;

    function openRejectModal(formId) {
        currentRejectFormId = formId;
        document.getElementById('rejectReasonInput').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
        setTimeout(() => document.getElementById('rejectReasonInput').focus(), 100);
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        currentRejectFormId = null;
    }

    function submitRejectForm() {
        let reason = document.getElementById('rejectReasonInput').value.trim();
        if (reason === '') {
            alert('Rejection reason is required.');
            document.getElementById('rejectReasonInput').focus();
            return;
        }
        
        if (currentRejectFormId !== null) {
            document.getElementById('adminNote_' + currentRejectFormId).value = reason;
            document.getElementById('rejectForm_' + currentRejectFormId).submit();
        }
    }
</script>
</body>
</html>
