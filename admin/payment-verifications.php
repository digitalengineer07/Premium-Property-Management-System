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

$success = '';
$error = '';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['id'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $id = (int)$_POST['id'];
        $action = $_POST['action'];
        $admin_note = s($_POST['admin_note'] ?? '');
        
        $q = mysqli_query($conn, "SELECT pn.*, u.email as user_email, u.name as user_name FROM payment_notifications pn JOIN users u ON pn.user_id = u.id WHERE pn.id = $id");
        $notif = mysqli_fetch_assoc($q);
        
        if ($notif && $notif['status'] == 'Pending') {
            if ($action == 'approve') {
                mysqli_query($conn, "UPDATE payment_notifications SET status='Approved', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id");
                
                // If this payment was for a bill, update the bill status to Paid
                if ($notif['bill_id']) {
                    $bid = (int)$notif['bill_id'];
                    if ($notif['bill_type'] == 'rent') {
                        mysqli_query($conn, "UPDATE rent SET status='Paid' WHERE id=$bid");
                    } elseif ($notif['bill_type'] == 'electricity') {
                        mysqli_query($conn, "UPDATE electricity SET status='Paid' WHERE id=$bid");
                    }
                }
                
                $success = "Payment #{$notif['transaction_id']} approved successfully.";
                
                // Send email
                if (!empty($notif['user_email'])) {
                    $sub = "Payment Approved - " . HOUSE_NAME;
                    $msg = "Hello {$notif['user_name']},<br><br>Your payment of Rs. {$notif['amount']} (Ref: {$notif['transaction_id']}) has been approved.<br><br>Thank you!";
                    sendEmail($notif['user_email'], $sub, $msg);
                }
                
            } elseif ($action == 'reject') {
                mysqli_query($conn, "UPDATE payment_notifications SET status='Rejected', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id");
                $success = "Payment #{$notif['transaction_id']} rejected.";
                
                if (!empty($notif['user_email'])) {
                    $sub = "Payment Rejected - " . HOUSE_NAME;
                    $msg = "Hello {$notif['user_name']},<br><br>Your payment of Rs. {$notif['amount']} (Ref: {$notif['transaction_id']}) was rejected.<br><br>Reason: $admin_note<br><br>Please contact admin.";
                    sendEmail($notif['user_email'], $sub, $msg);
                }
            }
        }
    }
}

// Fetch Filters
$f_search = $_GET['search'] ?? '';
$f_status = $_GET['status'] ?? 'All';
$f_month = $_GET['month'] ?? 'All';
$f_year = $_GET['year'] ?? 'All';
$f_mode = $_GET['mode'] ?? 'All';
$f_start = $_GET['start_date'] ?? '';
$f_end = $_GET['end_date'] ?? '';
$f_sort = $_GET['sort'] ?? 'latest';

// Build Query
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$base_query = " FROM payment_notifications pn LEFT JOIN users u ON pn.user_id = u.id WHERE 1=1 ";

if ($f_search !== '') {
    $sq = mysqli_real_escape_string($conn, $f_search);
    $base_query .= " AND (u.name LIKE '%$sq%' OR pn.transaction_id LIKE '%$sq%') ";
}
if ($f_status !== 'All') {
    $st = mysqli_real_escape_string($conn, $f_status);
    $base_query .= " AND pn.status = '$st' ";
}
if ($f_month !== 'All') {
    $m = (int)$f_month;
    $base_query .= " AND MONTH(pn.created_at) = $m ";
}
if ($f_year !== 'All') {
    $y = (int)$f_year;
    $base_query .= " AND YEAR(pn.created_at) = $y ";
}
if ($f_mode !== 'All') {
    $md = mysqli_real_escape_string($conn, $f_mode);
    $base_query .= " AND pn.payment_method = '$md' ";
}
if ($f_start !== '') {
    $sd = mysqli_real_escape_string($conn, $f_start);
    $base_query .= " AND DATE(pn.created_at) >= '$sd' ";
}
if ($f_end !== '') {
    $ed = mysqli_real_escape_string($conn, $f_end);
    $base_query .= " AND DATE(pn.created_at) <= '$ed' ";
}

if ($f_sort == 'oldest') {
    $order = " ORDER BY pn.created_at ASC";
} else {
    $order = " ORDER BY pn.created_at DESC";
}

$count_res = mysqli_query($conn, "SELECT COUNT(pn.id) as c " . $base_query);
$total_records = $count_res ? mysqli_fetch_assoc($count_res)['c'] : 0;
$total_pages = ceil($total_records / $limit);
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $limit;

$sql = "SELECT pn.*, u.name as renter_name, u.room_no " . $base_query . $order . " LIMIT $limit OFFSET $offset";

$notifs = [];
$res = mysqli_query($conn, $sql);
if ($res) {
    while($row = mysqli_fetch_assoc($res)){
        $notifs[] = $row;
    }
}
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
        /* PV Specific CSS to override global conflicts */
        .pv-page-header-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 10px 24px 10px;
            margin-bottom: 8px;
            position: relative;
        }
        .pv-header-content {
            display: flex;
            align-items: center;
            gap: 24px;
            z-index: 2;
        }
        .pv-header-icon-box {
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
        .pv-header-text h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 6px 0;
            line-height: 1.2;
        }
        .pv-header-text p {
            font-size: 15px;
            color: #64748B;
            margin: 0;
        }
        .pv-header-illustration {
            position: absolute;
            right: 10px;
            top: -10px;
            height: 140px;
            opacity: 1;
            z-index: 1;
        }
        
        .pv-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .pv-kpi-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 16px;
            border: 1px solid #F1F5F9;
        }
        .pv-kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .pv-kpi-blue { background: #EEF2FF; color: #6366F1; }
        .pv-kpi-yellow { background: #FEF9C3; color: #EAB308; }
        .pv-kpi-green { background: #DCFCE7; color: #10B981; }
        .pv-kpi-red { background: #FEE2E2; color: #EF4444; }
        .pv-kpi-details { flex: 1; display: flex; flex-direction: column; align-items: flex-start; }
        .pv-kpi-label { font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .pv-kpi-value { font-size: 26px; font-weight: 800; color: #0F172A; margin: 0; line-height: 1; }
        .pv-kpi-sub { font-size: 11px; color: #94A3B8; margin-top: 6px; }

        .pv-filter-panel {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            margin-bottom: 24px;
            border: 1px solid #F1F5F9;
        }
        .pv-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .pv-filter-grid-row2 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 16px;
        }
        .pv-filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 8px;
        }
        .pv-filter-group input, .pv-filter-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            background: #ffffff;
            color: #0F172A;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            -webkit-appearance: auto;
        }
        .pv-filter-group input:focus, .pv-filter-group select:focus {
            border-color: #6C4DFF;
            box-shadow: 0 0 0 3px rgba(108, 77, 255, 0.1);
        }
        .pv-filter-actions {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }
        .pv-btn-apply {
            background: #6C4DFF;
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
        .pv-btn-apply:hover { background: #5a3df0; }
        
        .pv-btn-reset {
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
        .pv-btn-reset:hover { background: #F8FAFC; color: #0F172A; }

        .pv-table-panel {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            overflow: hidden;
            border: 1px solid #F1F5F9;
        }
        .pv-table-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
        }
        .pv-table-title {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
        }
        .pv-btn-export {
            background: #F3F0FF;
            color: #6C4DFF;
            border: none;
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
        .pv-btn-export:hover { background: #E0D4FF; }
        
        .pv-table { width: 100%; border-collapse: collapse; }
        .pv-table th {
            background: #ffffff;
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }
        .pv-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
            background: #ffffff;
        }
        .pv-table tr:last-child td { border-bottom: none; }
        .pv-table tr:hover td { background: #F8FAFC; }
        
        .pv-user-cell { display: flex; align-items: center; gap: 10px; }
        .pv-avatar-circle {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
        }
        .pv-avatar-tu { background: #E0E7FF; color: #4338CA; }
        .pv-avatar-rs { background: #D1FAE5; color: #047857; }
        .pv-avatar-pk { background: #FCE7F3; color: #BE185D; }
        
        .pv-bill-info-type { font-size: 12px; font-weight: 600; color: #0F172A; margin-bottom: 2px; display: block; white-space: nowrap; }
        .pv-bill-info-inv { font-size: 11px; color: #64748B; white-space: nowrap; }
        
        .pv-amount-text { font-size: 13px; font-weight: 800; color: #6C4DFF; white-space: nowrap; }
        
        .pv-utr-text { font-size: 12px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .pv-utr-text i { color: #94A3B8; cursor: pointer; font-size: 14px; }
        
        .pv-date-text { font-size: 12px; font-weight: 600; color: #0F172A; display: block; margin-bottom: 2px; white-space: nowrap; }
        .pv-time-text { font-size: 11px; color: #64748B; white-space: nowrap; }
        
        .pv-status-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
            white-space: nowrap;
        }
        .pv-status-pill i { font-size: 6px; }
        .pv-status-pending { background: #FEF9C3; color: #CA8A04; }
        .pv-status-approved { background: #DCFCE7; color: #059669; }
        .pv-status-rejected { background: #FEE2E2; color: #DC2626; }
        
        .pv-mode-text { font-size: 11px; font-weight: 600; color: #0F172A; display: flex; align-items: center; gap: 4px; white-space: nowrap; }
        
        .pv-action-cell { display: flex; align-items: center; gap: 4px; }
        .pv-action-cell form { display: flex; margin: 0; }
        .pv-btn-approve-sm { background: #10B981; color: white; border: none; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); white-space: nowrap; }
        .pv-btn-reject-sm { background: transparent; color: #EF4444; border: 1px solid #FCA5A5; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .pv-btn-more { background: transparent; border: none; color: #94A3B8; font-size: 16px; cursor: pointer; padding: 2px; }
        
        .pv-pagination-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; border-top: 1px solid #E2E8F0;
        }
        .pv-page-info { font-size: 12px; font-weight: 500; color: #64748B; }
        .pv-pagination-controls { display: flex; gap: 6px; align-items: center; }
        .pv-page-btn { 
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid #E2E8F0; background: #ffffff;
            color: #64748B; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .pv-page-btn.active { background: #6C4DFF; color: white; border-color: #6C4DFF; }
        .pv-page-btn:hover:not(.active) { background: #F8FAFC; }

        @media(max-width: 1024px) {
            .pv-filter-grid, .pv-filter-grid-row2 { grid-template-columns: 1fr 1fr; }
            .pv-kpi-grid { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width: 768px) {
            .pv-filter-grid, .pv-filter-grid-row2 { grid-template-columns: 1fr; }
            .pv-filter-actions { flex-direction: column; }
            .pv-kpi-grid { grid-template-columns: 1fr; }
            .pv-header-illustration { display: none; }
            .pv-table th { display: none; }
            .pv-table td { display: block; width: 100%; border: none; padding: 10px; }
            .pv-table tr { display: block; border-bottom: 1px solid #E2E8F0; padding: 10px 0; }
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

    <!-- Header Banner -->
    <div class="pv-page-header-banner animate-up">
        <div class="pv-header-content">
            <div class="pv-header-icon-box">
                <i class='bx bx-check-shield'></i>
            </div>
            <div class="pv-header-text">
                <h1>Payment Verifications</h1>
                <p>Verify payments via UPI Transaction Reference (UTR)</p>
            </div>
        </div>
        <!-- Mockup Illustration SVG (Phone & Card) -->
        <svg class="pv-header-illustration" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
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
    <div class="pv-kpi-grid animate-up">
        <div class="pv-kpi-card">
            <div class="pv-kpi-icon pv-kpi-blue"><i class='bx bx-file'></i></div>
            <div class="pv-kpi-details">
                <div class="pv-kpi-label">Total Submissions</div>
                <div class="pv-kpi-value"><?php echo $kpi_total; ?></div>
                <div class="pv-kpi-sub">All time entries</div>
            </div>
        </div>
        <div class="pv-kpi-card">
            <div class="pv-kpi-icon pv-kpi-yellow"><i class='bx bx-time'></i></div>
            <div class="pv-kpi-details">
                <div class="pv-kpi-label">Pending</div>
                <div class="pv-kpi-value"><?php echo $kpi_pending; ?></div>
                <div class="pv-kpi-sub">Awaiting verification</div>
            </div>
        </div>
        <div class="pv-kpi-card">
            <div class="pv-kpi-icon pv-kpi-green"><i class='bx bx-check-circle'></i></div>
            <div class="pv-kpi-details">
                <div class="pv-kpi-label">Approved</div>
                <div class="pv-kpi-value"><?php echo $kpi_approved; ?></div>
                <div class="pv-kpi-sub">Payments verified</div>
            </div>
        </div>
        <div class="pv-kpi-card">
            <div class="pv-kpi-icon pv-kpi-red"><i class='bx bx-x-circle'></i></div>
            <div class="pv-kpi-details">
                <div class="pv-kpi-label">Rejected</div>
                <div class="pv-kpi-value"><?php echo $kpi_rejected; ?></div>
                <div class="pv-kpi-sub">Payments rejected</div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="pv-filter-panel animate-up">
        <form method="GET" action="">
            <div class="pv-filter-grid">
                <div class="pv-filter-group">
                    <label>Search (Name / UTR)</label>
                    <div style="position:relative;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="Search by name or UTR..." style="padding-right: 40px;">
                        <i class='bx bx-search' style="position:absolute; right:16px; top:12px; color:#94A3B8; font-size:16px;"></i>
                    </div>
                </div>
                <div class="pv-filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="All" <?php if($f_status=='All') echo 'selected';?>>All Statuses</option>
                        <option value="Pending" <?php if($f_status=='Pending') echo 'selected';?>>Pending</option>
                        <option value="Approved" <?php if($f_status=='Approved') echo 'selected';?>>Approved</option>
                        <option value="Rejected" <?php if($f_status=='Rejected') echo 'selected';?>>Rejected</option>
                    </select>
                </div>
                <div class="pv-filter-group">
                    <label>Month</label>
                    <select name="month">
                        <option value="All" <?php if($f_month=='All') echo 'selected';?>>All Months</option>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if($f_month==$m) echo 'selected';?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="pv-filter-group">
                    <label>Year</label>
                    <select name="year">
                        <option value="All" <?php if($f_year=='All') echo 'selected';?>>All Years</option>
                        <?php $cy = date('Y'); for($y=$cy; $y>=$cy-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php if($f_year==$y) echo 'selected';?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="pv-filter-grid-row2">
                <div class="pv-filter-group">
                    <label>Mode</label>
                    <select name="mode">
                        <option value="All" <?php if($f_mode=='All') echo 'selected';?>>All Modes</option>
                        <option value="UPI" <?php if($f_mode=='UPI') echo 'selected';?>>UPI</option>
                        <option value="Bank Transfer" <?php if($f_mode=='Bank Transfer') echo 'selected';?>>Bank Transfer</option>
                        <option value="Cash" <?php if($f_mode=='Cash') echo 'selected';?>>Cash</option>
                    </select>
                </div>
                <div class="pv-filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($f_start); ?>">
                </div>
                <div class="pv-filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($f_end); ?>">
                </div>
                <div class="pv-filter-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="latest" <?php if($f_sort=='latest') echo 'selected';?>>Latest First</option>
                        <option value="oldest" <?php if($f_sort=='oldest') echo 'selected';?>>Oldest First</option>
                    </select>
                </div>
            </div>
            
            <div class="pv-filter-actions">
                <button type="submit" class="pv-btn-apply"><i class='bx bx-filter-alt'></i> Apply Filters</button>
                <a href="payment-verifications.php" class="pv-btn-reset"><i class='bx bx-reset'></i> Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="pv-table-panel animate-up">
        <div class="pv-table-header-row">
            <h2 class="pv-table-title">Payment Verification List</h2>
            <button class="pv-btn-export"><i class='bx bx-download'></i> Export CSV</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="pv-table">
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
                        $classes = ['pv-avatar-tu', 'pv-avatar-rs', 'pv-avatar-pk'];
                        $avatarClass = $classes[$n['user_id'] % 3];
                    ?>
                    <tr>
                        <td>
                            <div class="pv-user-cell">
                                <div class="pv-avatar-circle <?php echo $avatarClass; ?>"><?php echo $initials; ?></div>
                                <div style="min-width: 80px;">
                                    <div style="font-weight: 700; color: #0F172A; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;"><?php echo s($n['renter_name']); ?></div>
                                    <div style="font-size: 11px; color: #64748B;">Room <?php echo s($n['room_no']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $bType = trim(ucfirst(s($n['bill_type']))); 
                            if (strtolower($bType) === 'total') $bType = '';
                            ?>
                            <span class="pv-bill-info-type"><?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?></span>
                            <?php if($n['bill_id']): ?>
                                <span class="pv-bill-info-inv">Invoice #INV<?php echo date('Ym', strtotime($n['created_at'])) . str_pad($n['bill_id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <?php else: ?>
                                <span class="pv-bill-info-inv">Advance Payment</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="pv-amount-text">₹<?php echo number_format($n['amount'], 2); ?></span>
                        </td>
                        <td>
                            <div class="pv-utr-text">
                                <?php echo s($n['transaction_id']); ?> <i class='bx bx-copy' title="Copy UTR" onclick="navigator.clipboard.writeText('<?php echo s($n['transaction_id']); ?>'); alert('UTR Copied!');"></i>
                            </div>
                        </td>
                        <td>
                            <span class="pv-date-text"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                            <span class="pv-time-text"><?php echo date('h:i A', strtotime($n['created_at'])); ?></span>
                        </td>
                        <td>
                            <?php if($n['status'] == 'Pending'): ?>
                                <span class="pv-status-pill pv-status-pending"><i class='bx bxs-circle'></i> Pending</span>
                            <?php elseif($n['status'] == 'Approved'): ?>
                                <span class="pv-status-pill pv-status-approved"><i class='bx bxs-circle'></i> Approved</span>
                            <?php else: ?>
                                <span class="pv-status-pill pv-status-rejected"><i class='bx bxs-circle'></i> Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="pv-mode-text">
                                <?php if($n['payment_method'] == 'UPI'): ?>
                                    <span style="color: #059669; font-style: italic; font-weight: 800; font-size:10px; border:1px solid #059669; padding:2px 4px; border-radius:4px; margin-right:4px;">UPI</span> UPI
                                <?php else: ?>
                                    <i class='bx bx-building-house' style="color: #64748B;"></i> <?php echo s($n['payment_method']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="pv-action-cell">
                                <?php if($n['status'] == 'Pending'): ?>
                                    <form action="" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="pv-btn-approve-sm" onclick="return confirm('Confirm this payment matches your bank statement?')">Approve</button>
                                    </form>
                                    <form action="" method="POST" style="margin:0;" id="rejectForm_<?php echo $n['id']; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="admin_note" id="adminNote_<?php echo $n['id']; ?>" value="">
                                        <button type="button" class="pv-btn-reject-sm" onclick="openRejectModal(<?php echo $n['id']; ?>)">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 12px; font-weight:600; color: #94A3B8;">—</span>
                                <?php endif; ?>
                                <button class="pv-btn-more"><i class='bx bx-dots-vertical-rounded'></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pv-pagination-footer">
            <div class="pv-page-info">Showing <?php echo ($total_records > 0) ? $offset + 1 : 0; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries</div>
            <div class="pv-pagination-controls">
                <?php
                // Preserve GET parameters
                $get_params = $_GET;
                unset($get_params['page']);
                $qstr = http_build_query($get_params);
                $qstr = $qstr ? '&' . $qstr : '';
                
                if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $qstr; ?>" class="pv-page-btn"><i class='bx bx-chevron-left'></i></a>
                <?php else: ?>
                    <span class="pv-page-btn" style="opacity:0.5;cursor:default;"><i class='bx bx-chevron-left'></i></span>
                <?php endif; ?>
                
                <?php for($i=max(1, $page-2); $i<=min($total_pages, $page+2); $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $qstr; ?>" class="pv-page-btn <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $qstr; ?>" class="pv-page-btn"><i class='bx bx-chevron-right'></i></a>
                <?php else: ?>
                    <span class="pv-page-btn" style="opacity:0.5;cursor:default;"><i class='bx bx-chevron-right'></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Rejection Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
    <div class="pv-filter-panel animate-up" style="max-width: 420px; width: 100%; padding: 24px; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 8px;"><div style="width:32px; height:32px; background:#FEE2E2; color:#EF4444; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class='bx bx-error-circle'></i></div> Reject Payment</h2>
            <i class='bx bx-x' onclick="closeRejectModal()" style="font-size: 24px; cursor: pointer; color: #64748B;"></i>
        </div>
        <p style="font-size: 13px; color: #64748B; margin-bottom: 16px; line-height: 1.5;">Please provide a clear reason for rejecting this payment. The renter will see this reason on their dashboard.</p>
        
        <textarea id="rejectReasonInput" placeholder="e.g. UTR mismatch, Insufficient amount, etc." style="width: 100%; padding: 14px; border: 1px solid #E2E8F0; border-radius: 12px; background: #ffffff; color: #0F172A; outline: none; font-size: 13px; min-height: 100px; margin-bottom: 24px; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
        
        <div style="display: flex; gap: 12px;">
            <button type="button" class="pv-btn-reset" onclick="closeRejectModal()">Cancel</button>
            <button type="button" class="pv-btn-apply" onclick="submitRejectForm()" style="background: #EF4444;">Reject Payment</button>
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
