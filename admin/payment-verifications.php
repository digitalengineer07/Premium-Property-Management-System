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
        /* New CSS for Payment Verifications */
        .page-header-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 10px 24px 10px;
            margin-bottom: 8px;
            position: relative;
        }
        .header-content {
            display: flex;
            align-items: center;
            gap: 24px;
            z-index: 2;
        }
        .header-icon-box {
            width: 84px;
            height: 84px;
            background: #F5F3FF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6C4DFF;
            font-size: 42px;
        }
        .header-text h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 6px 0;
        }
        .header-text p {
            font-size: 15px;
            color: #64748B;
            margin: 0;
        }
        .header-illustration {
            position: absolute;
            right: 10px;
            top: -10px;
            height: 140px;
            opacity: 1;
            z-index: 1;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #F1F5F9;
        }
        .kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .kpi-blue { background: #EEF2FF; color: #6366F1; }
        .kpi-yellow { background: #FEF9C3; color: #EAB308; }
        .kpi-green { background: #DCFCE7; color: #10B981; }
        .kpi-red { background: #FEE2E2; color: #EF4444; }
        .kpi-details { flex: 1; }
        .kpi-label { font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 4px; }
        .kpi-value { font-size: 26px; font-weight: 800; color: var(--text-dark); margin: 0; line-height: 1; }
        .kpi-sub { font-size: 11px; color: #94A3B8; margin-top: 6px; }

        .filter-panel {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            border: 1px solid #F1F5F9;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .filter-grid-row2 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 16px;
        }
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 8px;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            background: var(--white);
            color: var(--text-dark);
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .filter-group input:focus, .filter-group select:focus {
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .btn-apply {
            background: #6366F1;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-apply:hover { background: #4F46E5; }
        
        .btn-reset {
            background: transparent;
            color: #64748B;
            border: 1px solid #E2E8F0;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-reset:hover { background: #F8FAFC; color: var(--text-dark); }

        .table-panel {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #F1F5F9;
        }
        .table-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
        }
        .table-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        .btn-export {
            background: #EEF2FF;
            color: #6366F1;
            border: 1px solid #E0E7FF;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-export:hover { background: #E0E7FF; }
        
        table { width: 100%; border-collapse: collapse; }
        th {
            background: var(--white);
            padding: 16px 24px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #E2E8F0;
        }
        td {
            padding: 20px 24px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #F8FAFC; }
        
        .user-cell { display: flex; align-items: center; gap: 12px; }
        .avatar-circle {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        .avatar-tu { background: #E0E7FF; color: #4338CA; }
        .avatar-rs { background: #D1FAE5; color: #047857; }
        .avatar-pk { background: #FCE7F3; color: #BE185D; }
        
        .bill-info-type { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 4px; display: block; }
        .bill-info-inv { font-size: 12px; color: #64748B; }
        
        .amount-text { font-size: 14px; font-weight: 800; color: #6366F1; }
        
        .utr-text { font-size: 13px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 6px; }
        .utr-text i { color: #94A3B8; cursor: pointer; font-size: 15px; }
        
        .date-text { font-size: 13px; font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 4px; }
        .time-text { font-size: 12px; color: #64748B; }
        
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .status-pill i { font-size: 8px; }
        .status-pending { background: #FEF9C3; color: #CA8A04; }
        .status-approved { background: #DCFCE7; color: #059669; }
        .status-rejected { background: #FEE2E2; color: #DC2626; }
        
        .mode-text { font-size: 12px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; }
        
        .action-cell { display: flex; align-items: center; gap: 8px; }
        .btn-approve-sm { background: #10B981; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); }
        .btn-reject-sm { background: transparent; color: #EF4444; border: 1px solid #FCA5A5; padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-more { background: transparent; border: none; color: #94A3B8; font-size: 18px; cursor: pointer; padding: 4px; }
        
        .pagination-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; border-top: 1px solid #E2E8F0;
        }
        .page-info { font-size: 12px; font-weight: 500; color: #64748B; }
        .pagination-controls { display: flex; gap: 6px; align-items: center; }
        .page-btn { 
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid #E2E8F0; background: var(--white);
            color: #64748B; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .page-btn.active { background: #6366F1; color: white; border-color: #6366F1; }
        .page-btn:hover:not(.active) { background: #F8FAFC; }

        @media(max-width: 1024px) {
            .filter-grid, .filter-grid-row2 { grid-template-columns: 1fr 1fr; }
            .kpi-grid { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width: 768px) {
            .filter-grid, .filter-grid-row2 { grid-template-columns: 1fr; }
            .filter-actions { flex-direction: column; }
            .kpi-grid { grid-template-columns: 1fr; }
            .header-illustration { display: none; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>
    
    <?php
    // Fetch KPI stats
    $kpi_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payment_notifications"))['c'] ?? 0;
    $kpi_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payment_notifications WHERE status='Pending'"))['c'] ?? 0;
    $kpi_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payment_notifications WHERE status='Approved'"))['c'] ?? 0;
    $kpi_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payment_notifications WHERE status='Rejected'"))['c'] ?? 0;
    ?>

    <!-- New Header Banner -->
    <div class="page-header-banner animate-up">
        <div class="header-content">
            <div class="header-icon-box">
                <i class='bx bx-check-shield'></i>
            </div>
            <div class="header-text">
                <h1>Payment Verifications</h1>
                <p>Verify payments via UPI Transaction Reference (UTR)</p>
            </div>
        </div>
        <!-- Mockup Illustration SVG (Phone & Card) -->
        <svg class="header-illustration" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g>
                <rect x="70" y="20" width="60" height="110" rx="12" fill="white" stroke="#E2E8F0" stroke-width="4"/>
                <path d="M70 32C70 25.3726 75.3726 20 82 20H118C124.627 20 130 25.3726 130 32V45H70V32Z" fill="#EEF2FF"/>
                <text x="100" y="38" font-family="Arial" font-size="10" font-style="italic" font-weight="900" fill="#6C4DFF" text-anchor="middle">UPI</text>
                <circle cx="100" cy="85" r="16" fill="#10B981"/>
                <path d="M94 85L98 89L106 81" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                
                <g transform="translate(105, 75) rotate(10)">
                    <rect width="55" height="34" rx="6" fill="#6C4DFF"/>
                    <rect x="5" y="6" width="14" height="8" rx="2" fill="#E2E8F0" opacity="0.4"/>
                    <circle cx="42" cy="22" r="4" fill="white" opacity="0.8"/>
                    <circle cx="48" cy="22" r="4" fill="white" opacity="0.5"/>
                    <rect x="5" y="22" width="20" height="3" rx="1" fill="white" opacity="0.5"/>
                </g>
                <circle cx="40" cy="50" r="3" fill="#C7D2FE"/>
                <circle cx="150" cy="30" r="2" fill="#C7D2FE"/>
                <circle cx="160" cy="90" r="3" fill="#E0E7FF"/>
                <circle cx="50" cy="110" r="4" fill="#A7F3D0"/>
            </g>
        </svg>
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

    <!-- KPI Cards -->
    <div class="kpi-grid animate-up">
        <div class="kpi-card">
            <div class="kpi-icon kpi-blue"><i class='bx bx-file'></i></div>
            <div class="kpi-details">
                <div class="kpi-label">Total Submissions</div>
                <div class="kpi-value"><?php echo $kpi_total; ?></div>
                <div class="kpi-sub">All time entries</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon kpi-yellow"><i class='bx bx-time'></i></div>
            <div class="kpi-details">
                <div class="kpi-label">Pending</div>
                <div class="kpi-value"><?php echo $kpi_pending; ?></div>
                <div class="kpi-sub">Awaiting verification</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon kpi-green"><i class='bx bx-check-circle'></i></div>
            <div class="kpi-details">
                <div class="kpi-label">Approved</div>
                <div class="kpi-value"><?php echo $kpi_approved; ?></div>
                <div class="kpi-sub">Payments verified</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon kpi-red"><i class='bx bx-x-circle'></i></div>
            <div class="kpi-details">
                <div class="kpi-label">Rejected</div>
                <div class="kpi-value"><?php echo $kpi_rejected; ?></div>
                <div class="kpi-sub">Payments rejected</div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="filter-panel animate-up">
        <form method="GET" action="">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search (Name / UTR)</label>
                    <div style="position:relative;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="Search by name or UTR..." style="padding-right: 40px;">
                        <i class='bx bx-search' style="position:absolute; right:16px; top:12px; color:#94A3B8; font-size:16px;"></i>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="All" <?php if($f_status=='All') echo 'selected';?>>All Statuses</option>
                        <option value="Pending" <?php if($f_status=='Pending') echo 'selected';?>>Pending</option>
                        <option value="Approved" <?php if($f_status=='Approved') echo 'selected';?>>Approved</option>
                        <option value="Rejected" <?php if($f_status=='Rejected') echo 'selected';?>>Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Month</label>
                    <select name="month">
                        <option value="All" <?php if($f_month=='All') echo 'selected';?>>All Months</option>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if($f_month==$m) echo 'selected';?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Year</label>
                    <select name="year">
                        <option value="All" <?php if($f_year=='All') echo 'selected';?>>All Years</option>
                        <?php $cy = date('Y'); for($y=$cy; $y>=$cy-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php if($f_year==$y) echo 'selected';?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-grid-row2">
                <div class="filter-group">
                    <label>Mode</label>
                    <select name="mode">
                        <option value="All" <?php if($f_mode=='All') echo 'selected';?>>All Modes</option>
                        <option value="UPI" <?php if($f_mode=='UPI') echo 'selected';?>>UPI</option>
                        <option value="Bank Transfer" <?php if($f_mode=='Bank Transfer') echo 'selected';?>>Bank Transfer</option>
                        <option value="Cash" <?php if($f_mode=='Cash') echo 'selected';?>>Cash</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($f_start); ?>">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($f_end); ?>">
                </div>
                <div class="filter-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="latest" <?php if($f_sort=='latest') echo 'selected';?>>Latest First</option>
                        <option value="oldest" <?php if($f_sort=='oldest') echo 'selected';?>>Oldest First</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-apply"><i class='bx bx-filter-alt'></i> Apply Filters</button>
                <a href="payment-verifications.php" class="btn-reset"><i class='bx bx-reset'></i> Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="table-panel animate-up">
        <div class="table-header-row">
            <h2 class="table-title">Payment Verification List</h2>
            <button class="btn-export"><i class='bx bx-download'></i> Export CSV</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Bill Info</th>
                        <th>Amount</th>
                        <th>Transaction ID (UTR)</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Mode</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifs)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94A3B8;">No payment notifications found.</td></tr>
                    <?php else: foreach ($notifs as $n): 
                        // Generate avatar initials and colors
                        $names = explode(' ', $n['renter_name']);
                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                        
                        // Seed avatar class based on user id for consistency
                        $classes = ['avatar-tu', 'avatar-rs', 'avatar-pk'];
                        $avatarClass = $classes[$n['user_id'] % 3];
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar-circle <?php echo $avatarClass; ?>"><?php echo $initials; ?></div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-dark); font-size: 13px;"><?php echo s($n['renter_name']); ?></div>
                                    <div style="font-size: 11px; color: #64748B;">Room <?php echo s($n['room_no']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="bill-info-type"><?php echo ucfirst(s($n['bill_type'])); ?> - <?php echo date('M Y', strtotime($n['created_at'])); ?></span>
                            <?php if($n['bill_id']): ?>
                                <span class="bill-info-inv">Invoice #INV<?php echo date('Ym', strtotime($n['created_at'])) . str_pad($n['bill_id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <?php else: ?>
                                <span class="bill-info-inv">Advance Payment</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="amount-text">₹<?php echo number_format($n['amount'], 2); ?></span>
                        </td>
                        <td>
                            <div class="utr-text">
                                <?php echo s($n['transaction_id']); ?> <i class='bx bx-copy' title="Copy UTR" onclick="navigator.clipboard.writeText('<?php echo s($n['transaction_id']); ?>'); alert('UTR Copied!');"></i>
                            </div>
                        </td>
                        <td>
                            <span class="date-text"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                            <span class="time-text"><?php echo date('h:i A', strtotime($n['created_at'])); ?></span>
                        </td>
                        <td>
                            <?php if($n['status'] == 'Pending'): ?>
                                <span class="status-pill status-pending"><i class='bx bxs-circle'></i> Pending</span>
                            <?php elseif($n['status'] == 'Approved'): ?>
                                <span class="status-pill status-approved"><i class='bx bxs-circle'></i> Approved</span>
                            <?php else: ?>
                                <span class="status-pill status-rejected"><i class='bx bxs-circle'></i> Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="mode-text">
                                <?php if($n['payment_method'] == 'UPI'): ?>
                                    <span style="color: #059669; font-style: italic; font-weight: 800; font-size:10px; border:1px solid #059669; padding:2px 4px; border-radius:4px; margin-right:4px;">UPI</span> UPI
                                <?php else: ?>
                                    <i class='bx bx-building-house' style="color: #64748B;"></i> <?php echo s($n['payment_method']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-cell">
                                <?php if($n['status'] == 'Pending'): ?>
                                    <form action="" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-approve-sm" onclick="return confirm('Confirm this payment matches your bank statement?')">Approve</button>
                                    </form>
                                    <form action="" method="POST" style="margin:0;" id="rejectForm_<?php echo $n['id']; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="admin_note" id="adminNote_<?php echo $n['id']; ?>" value="">
                                        <button type="button" class="btn-reject-sm" onclick="openRejectModal(<?php echo $n['id']; ?>)">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 12px; font-weight:600; color: #94A3B8;">—</span>
                                <?php endif; ?>
                                <button class="btn-more"><i class='bx bx-dots-vertical-rounded'></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pagination-footer">
            <div class="page-info">Showing 1 to <?php echo min(count($notifs), 10); ?> of <?php echo $kpi_total; ?> entries</div>
            <div class="pagination-controls">
                <a href="#" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                <a href="#" class="page-btn active">1</a>
                <a href="#" class="page-btn">2</a>
                <a href="#" class="page-btn">3</a>
                <span class="page-btn" style="border:none; cursor:default; background:transparent;">...</span>
                <a href="#" class="page-btn">16</a>
                <a href="#" class="page-btn"><i class='bx bx-chevron-right'></i></a>
            </div>
        </div>
    </div>
</main>

<!-- Rejection Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
    <div class="panel animate-up" style="max-width: 420px; width: 100%; padding: 24px; position: relative; background: var(--white); border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px;"><div style="width:32px; height:32px; background:#FEE2E2; color:#EF4444; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class='bx bx-error-circle'></i></div> Reject Payment</h2>
            <i class='bx bx-x' onclick="closeRejectModal()" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
        </div>
        <p style="font-size: 13px; color: var(--text-gray); margin-bottom: 16px; line-height: 1.5;">Please provide a clear reason for rejecting this payment. The renter will see this reason on their dashboard.</p>
        
        <textarea id="rejectReasonInput" placeholder="e.g. UTR mismatch, Insufficient amount, etc." style="width: 100%; padding: 14px; border: 1px solid #E2E8F0; border-radius: 12px; background: #F8FAFC; color: var(--text-dark); outline: none; font-size: 13px; min-height: 100px; margin-bottom: 24px; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
        
        <div style="display: flex; gap: 12px;">
            <button type="button" class="btn-reset" onclick="closeRejectModal()">Cancel</button>
            <button type="button" class="btn-apply" onclick="submitRejectForm()" style="background: #EF4444;">Reject Payment</button>
        </div>
    </div>
</div>

<script>
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
