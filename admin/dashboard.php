<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

include "../db.php";

/* Stats fetching */
// Dynamically get the latest billed month from the database instead of hardcoding 'last month'
$latest_month_query = mysqli_query($conn, "SELECT month FROM electricity ORDER BY STR_TO_DATE(CONCAT('01 ', month), '%d %M %Y') DESC LIMIT 1");
if ($latest_month_row = mysqli_fetch_assoc($latest_month_query)) {
    $prev_month_str = $latest_month_row['month'];
} else {
    $prev_month_str = date('F Y', strtotime('first day of last month'));
}

// 1) Rent Collected: overall total rent amount collected
$r_coll_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM electricity WHERE status='Paid'"))['total'];
$r_coll_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid'"))['total'];
$rent_collected_total = $r_coll_elec + $r_coll_rent;

// 2) Total Dues: total due amount of all renters combined (subtracts partial payments)
$d_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status!='Paid'"))['total'];
$d_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status!='Paid'"))['total'];
$p_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.status='Partial'"))['total'];
$p_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.status='Partial'"))['total'];
$total_dues_total = max(0, ($d_elec + $d_rent) - ($p_elec + $p_rent));

// 3) Electricity Paid: sum of all electricity amounts paid for the selected month
$elec_collected_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM electricity WHERE status='Paid' AND month='$prev_month_str'"))['total'];

// 5) Total Revenue: combined total of all paid renter amounts (rent + elec + advance + partials)
$rev_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status='Paid'"))['total'];
$rev_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid'"))['total'];
$rev_adv = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(paid_amount),0) AS total FROM payments WHERE bill_type='advance'"))['total'];
$total_revenue_total = $rev_elec + $rev_rent + $p_elec + $p_rent + $rev_adv;

$renters = mysqli_query($conn, "SELECT * FROM users WHERE status = 'active' ORDER BY id ASC");
$elec_records = mysqli_query($conn, "SELECT e.*, u.name as renter_name FROM electricity e JOIN users u ON e.user_id = u.id ORDER BY e.id DESC LIMIT 6");

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');

/* Login Tracking Statistics */
$today_logins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_logs WHERE DATE(login_time) = CURDATE() AND user_type='renter'"))['count'];
$month_logins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_logs WHERE MONTH(login_time) = MONTH(CURDATE()) AND YEAR(login_time) = YEAR(CURDATE()) AND user_type='renter'"))['count'];
$unique_visitors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM login_logs WHERE user_type='renter'"))['count'];
$total_renters_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'active'"))['count'];
$visitor_ratio = $total_renters_count > 0 ? round(($unique_visitors / $total_renters_count) * 100) : 0;

/* Recent Login Activity */
$recent_logins = mysqli_query($conn, "
    SELECT l.*, u.name 
    FROM login_logs l 
    JOIN users u ON l.user_id = u.id 
    WHERE l.user_type = 'renter' 
    ORDER BY l.login_time DESC 
    LIMIT 5
");

/* Calendar Logic */
$month = date('m'); $year = date('Y');
$first_day = date('w', strtotime("$year-$month-01"));
$days_in_month = date('t'); $today = date('j');

/* Support Queries Statistics */
$pending_queries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM queries WHERE status='Pending'"))['count'];

/* Feature 1: Resident Due Grid */
$target_month = date('F Y', strtotime('first day of last month')); // Billing uses "F Y" string
$display_month = $target_month;

// Filters for Rent History
$rent_status_filter = mysqli_real_escape_string($conn, $_GET['rent_status'] ?? 'All');
$rent_search = mysqli_real_escape_string($conn, trim($_GET['rent_q'] ?? ''));
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// ONLY SHOW UNPAID BILLS IN THIS GRID (total_amount)
$rh_where = "WHERE r.status != 'Paid'";
if ($rent_status_filter !== 'All') {
    // We already filter to Due, so this is mostly just for compatibility if they filter.
    // If they ask for Paid, it'll be empty because we only show unpaid in "Rent Due Grid" as requested.
    $rh_where .= " AND r.status = '$rent_status_filter'"; 
}
if ($rent_search !== '') {
    $rh_where .= " AND (u.name LIKE '%$rent_search%' OR u.room_no LIKE '%$rent_search%')";
}

// Rent Due Grid: Display amount which renter do not paid (rent + eval + maintenance = total_amount)
$rent_history_sql = "
    SELECT 
        r.id as bill_id, u.id as user_id, u.name as renter_name, u.room_no, r.month, r.rent_amount, r.total_amount,
        r.status as rent_status
    FROM electricity r
    JOIN users u ON r.user_id = u.id
    $rh_where
    ORDER BY u.name ASC
    LIMIT $limit OFFSET $offset
";

$rent_history_results = mysqli_query($conn, $rent_history_sql);

// Rent Due KPIs
$rh_kpi_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status != 'Paid' THEN 1 ELSE 0 END) as due
    FROM electricity r
    WHERE status != 'Paid'
";
$rh_kpis = mysqli_fetch_assoc(mysqli_query($conn, $rh_kpi_sql));

/* Feature 2: Unified Transactions */
$transaction_range = $_GET['tx_range'] ?? 'All';
$tx_where = "";
if ($transaction_range === '7d') $tx_where = "WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
elseif ($transaction_range === '30d') $tx_where = "WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$unified_tx_sql = "
    SELECT * FROM (
        SELECT 
            id, user_id, bill_type as type, bill_id, total_amount as amount, payment_mode as mode, 
            payment_date, payment_time, 'Success' as status, 'admin' as source
        FROM payments
        UNION ALL
        SELECT 
            id, user_id, bill_type as type, bill_id, amount, 'UPI' as mode, 
            DATE(created_at) as payment_date, TIME(created_at) as payment_time, status, 'renter' as source
        FROM payment_notifications
    ) as combined_tx
    JOIN users u ON combined_tx.user_id = u.id
    $tx_where
    ORDER BY payment_date DESC, payment_time DESC
    LIMIT 10
";
$recent_transactions = mysqli_query($conn, $unified_tx_sql);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../sw.js').then(reg => {
            console.log('SW registered');
          }).catch(err => {
            console.log('SW failed', err);
          });
        });
      }
    </script>
    <script src="../assets/js/pwa.js" defer></script>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome-header animate-up">
        <div class="welcome-text">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo $admin_user; ?>! Here's what's happening today.</p>
        </div>
        <a href="../onboarding-guide.php" target="_blank" class="btn-outline print-btn">
            <i class='bx bx-printer'></i> Print Resident Guide
        </a>
    </div>

    <style>
        .welcome-header { margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        
        @media (max-width: 1024px) {
            .dashboard-grid-70 {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                row-gap: 24px !important;
                column-gap: 12px !important;
            }
            .left-col, .right-col { display: contents !important; }
            
            .left-col > .panel, .right-col > .panel {
                grid-column: span 2;
                margin-bottom: 0 !important;
            }

            .right-col > .stats-card-mini { order: 1; margin-bottom: 0 !important; }
            
            .right-col > .calendar-panel { order: 2; grid-column: span 1 !important; margin-bottom: 0 !important; padding: 16px !important; height: 100% !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; }
            .right-col > .panel:nth-child(3) { order: 3; grid-column: span 1 !important; margin-bottom: 0 !important; padding: 16px !important; height: 100% !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; }
            
            .left-col > .panel:nth-child(2) { order: 4; }
            .left-col > .panel:nth-child(3) { order: 5; }
            .left-col > .panel:nth-child(4) { order: 6; }
            .left-col > .panel:nth-child(5) { order: 7; }
            .right-col > .panel:last-child { order: 8; }
            .left-col > .panel:nth-child(1) { order: 9; }
            
            .right-col > .panel:nth-child(3) .donut { width: 110px !important; height: 110px !important; border-width: 8px !important; }
            .right-col > .panel:nth-child(3) .donut-val h2 { font-size: 18px !important; }
            .right-col > .panel:nth-child(3) .panel-header h2 { font-size: 14px !important; }
            .right-col > .calendar-panel .panel-header h3 { font-size: 14px !important; text-align: left; }
            .right-col > .calendar-panel .calendar-day { font-size: 11px !important; }
            .right-col > .calendar-panel .day-label { font-size: 10px !important; }
            
            /* Pending payments side-by-side mobile fix */
            .panel-header .pending-actions-row { flex-direction: row !important; width: 100% !important; gap: 6px !important; justify-content: space-between !important; align-items: center !important; }
            .panel-header .pending-actions-row form.pending-actions-form { flex-direction: row !important; width: auto !important; gap: 6px !important; flex: 1 !important; margin: 0 !important; }
            .panel-header .pending-actions-row .btn-outline, .panel-header .pending-actions-row .btn-primary { width: auto !important; padding: 0 10px !important; justify-content: center !important; flex-shrink: 0 !important; }
            .panel-header .pending-actions-row .pending-select { width: 100% !important; flex: 1 !important; min-width: 0 !important; padding: 0 4px !important; }
            .panel-header .pending-actions-row .pending-input { width: 100% !important; flex: 1.5 !important; min-width: 0 !important; padding: 0 8px !important; }
            .panel-header h2 { justify-content: center !important; flex-wrap: wrap !important; }
            .panel-header .pending-title { display: grid !important; grid-template-columns: 1fr auto 1fr !important; width: 100% !important; gap: 0 !important; }
            .panel-header .pending-title::before { content: "" !important; }
            .panel-header .pending-title .pending-badge { justify-self: end !important; grid-column: 3 !important; }
            
        }

        @media (max-width: 768px) {
            .welcome-header { flex-direction: column !important; align-items: center !important; text-align: center !important; gap: 20px !important; }
            .welcome-header p { font-size: 13px !important; }
            .welcome-header .btn-outline { width: 100% !important; justify-content: center !important; padding: 14px !important; }
            
            .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
            .kpi-card { text-align: center !important; align-items: center !important; display: flex !important; flex-direction: column !important; padding: 16px 10px !important; }
            .kpi-header { justify-content: center !important; gap: 8px !important; margin-bottom: 12px !important; flex-wrap: wrap !important; }
            .kpi-value { font-size: 18px !important; word-break: break-word !important; }
            .kpi-label { font-size: 11px !important; line-height: 1.2 !important; }
            .kpi-icon { width: 36px !important; height: 36px !important; font-size: 18px !important; border-radius: 10px !important; }
            
            .panel-header { flex-direction: column !important; align-items: center !important; text-align: center !important; gap: 15px !important; }
            .panel-header h2, .panel-header h3 { text-align: center !important; width: 100% !important; }
            .panel-header div { display: flex !important; flex-direction: column !important; width: 100% !important; gap: 10px !important; }
            .panel-header .btn-outline, .panel-header .btn-primary { width: 100% !important; justify-content: center !important; }
            .panel-header div.recent-tx-actions { flex-direction: row !important; gap: 8px !important; }
            .panel-header div.recent-tx-actions > * { flex: 1 !important; margin: 0 !important; width: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; height: 38px !important; }
            .panel-header div.manage-residents-actions { flex-direction: row !important; gap: 8px !important; }
            .panel-header div.manage-residents-actions > * { flex: 1 !important; margin: 0 !important; width: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; height: 38px !important; }
            .panel-header form.summary-actions { flex-direction: row !important; gap: 8px !important; display: flex !important; width: 100% !important; margin: 0 !important; }
            .panel-header form.summary-actions > select { flex: 1 !important; margin: 0 !important; width: 50% !important; display: flex !important; align-items: center !important; justify-content: space-between !important; height: 38px !important; }
            
            table td { padding: 12px 8px !important; }
            .btn-primary, .btn-outline { font-size: 13px !important; padding: 10px 16px !important; }
            
            .right-col .panel { margin-bottom: 20px; text-align: center !important; }
            .stats-card-mini { text-align: center !important; padding: 24px !important; }
            .stats-card-mini div { justify-content: center !important; }
            
            .chart-box { justify-content: center !important; width: 100% !important; }
            .donut { margin: 0 auto !important; }

            /* Rent History Mobile Cards */
            .rent-history-card { margin-bottom: 12px; border: 1px solid var(--border); border-radius: 14px; padding: 16px; background: var(--white); }
            .rent-history-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .rent-history-details { display: none; margin-top: 10px; border-top: 1px dashed var(--border); padding-top: 10px; }
            .rent-history-card.active .rent-history-details { display: block; }
            
            /* General Stacked Table Cards for Mobile */
            .table-responsive { overflow: visible !important; margin: 0 !important; padding: 0 !important; width: 100% !important; border: none !important; }
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; border: none !important; }
            thead { display: none !important; }
            
            tbody tr {
                background: var(--white) !important;
                border: 1px solid var(--border) !important;
                border-radius: 16px !important;
                padding: 16px !important;
                margin-bottom: 16px !important;
                box-shadow: var(--card-shadow) !important;
            }

            tbody td {
                padding: 0 !important;
                border: none !important;
                margin-bottom: 12px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                font-size: 14px !important;
                text-align: right !important;
                font-weight: 500 !important;
            }

            tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-gray);
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
                text-align: left;
                flex: 1;
            }

            tbody td:last-child {
                display: block !important;
                margin-bottom: 0 !important;
                margin-top: 10px !important;
                padding-top: 15px !important;
                border-top: 1px solid var(--border) !important;
            }
            tbody td:last-child::before { display: none !important; }
            
            tbody td:last-child > div {
                justify-content: center !important; 
                display: flex !important; 
                gap: 12px !important; 
                width: 100% !important;
            }

            tbody td:last-child button, tbody td:last-child a.btn-primary, tbody td:last-child a.btn-outline {
                flex: 1 !important;
                justify-content: center !important;
                padding: 12px !important;
                font-size: 15px !important;
                border-radius: 12px !important;
            }
            
            .hide-desktop { display: block !important; }
            .hide-mobile { display: none !important; }
            
            .side-by-side-panels {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
                margin-bottom: 24px !important;
            }
        }
        @media (min-width: 769px) {
            .hide-desktop { display: none !important; }
        }
    </style>

    <div class="kpi-grid animate-up">
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #10B981; background: rgba(16, 185, 129, 0.1);">
                    <i class='bx bx-check-shield'></i>
                </div>
                <div class="trend trend-up"><i class='bx bx-trending-up'></i> 4.5%</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($rent_collected_total); ?></div>
            <div class="kpi-label">Rent Collected</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #EF4444; background: rgba(239, 68, 68, 0.1);">
                    <i class='bx bx-error-circle'></i>
                </div>
                <div class="trend trend-down"><i class='bx bx-trending-down'></i> 1.2%</div>
            </div>
            <div class="kpi-value" style="color: #EF4444;">₹<?php echo number_format($total_dues_total); ?></div>
            <div class="kpi-label">Total Dues</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #3B82F6; background: rgba(59, 130, 246, 0.1);">
                    <i class='bx bx-bolt-circle'></i>
                </div>
                <div class="trend trend-up"><i class='bx bx-trending-up'></i> 2.1%</div>
            </div>
            <?php $display_month = date('M', strtotime('01 ' . $prev_month_str)); ?>
            <div class="kpi-value">₹<?php echo number_format($elec_collected_total); ?></div>
            <div class="kpi-label">Electricity Paid (<?php echo $display_month; ?>)</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);">
                    <i class='bx bx-message-error'></i>
                </div>
                <div class="trend" style="color: #F59E0B; font-weight: 600; font-size: 11px;">Action</div>
            </div>
            <div class="kpi-value" style="color: #F59E0B;"><?php echo $pending_queries; ?></div>
            <div class="kpi-label">Pending Queries</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #8B5CF6; background: rgba(139, 92, 246, 0.1);">
                    <i class='bx bx-wallet'></i>
                </div>
                <div class="trend trend-up"><i class='bx bx-trending-up'></i> 8.4%</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($total_revenue_total); ?></div>
            <div class="kpi-label">Total Revenue</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <div class="kpi-icon" style="color: #06B6D4; background: rgba(6, 182, 212, 0.1);">
                    <i class='bx bx-group'></i>
                </div>
                <div class="trend" style="color: var(--text-gray); font-size: 11px;">Active</div>
            </div>
            <div class="kpi-value"><?php echo $total_renters_count; ?></div>
            <div class="kpi-label">Total Residents</div>
        </div>
    </div>

    <div class="dashboard-grid-70 animate-up">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Manage Residents</h2>
                    <div class="manage-residents-actions" style="display: flex; gap: 8px;">
                        <a href="add-renter.php" class="btn-outline" style="font-size: 13px; color: #10B981; border-color: #DCFCE7;">+ Add New</a>
                        <a href="manage-renters.php" class="btn-outline" style="font-size: 13px;">View All</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = mysqli_fetch_assoc($renters)): ?>
                            <tr>
                                <td data-label="Name" style="font-weight: 600;"><?php echo htmlspecialchars($u['name']); ?></td>
                                <td data-label="Room">Room <?php echo htmlspecialchars($u['room_no']); ?></td>
                                <td data-label="Status"><span class="badge badge-paid">Active</span></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="bill-generator.php?user_id=<?php echo $u['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px;"><i class='bx bx-receipt'></i> Bill</a>
                                        <a href="../onboarding-guide.php?id=<?php echo $u['id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 12px; height: auto;"><i class='bx bx-book-open'></i> Guide</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Only Summary Panel -->
            <div class="panel hide-desktop" id="summary-panel" style="margin-bottom: 20px;">
                <div class="panel-header" style="flex-direction: column; align-items: stretch; gap: 12px;">
                    <h2 style="font-size: 18px; font-weight: 700; text-align: left; width: 100%; margin: 0;">Summary</h2>
                    <?php
                    $summary_month = isset($_GET['summary_m']) ? $_GET['summary_m'] : date('m');
                    $summary_year = isset($_GET['summary_y']) ? $_GET['summary_y'] : date('Y');
                    $summary_target = date('F Y', strtotime("$summary_year-$summary_month-01"));
                    
                    $summary_query = "
                        SELECT u.name, u.room_no, e.status, e.id as bill_id 
                        FROM users u 
                        LEFT JOIN electricity e ON u.id = e.user_id AND e.month = '$summary_target'
                        WHERE CAST(u.room_no AS UNSIGNED) BETWEEN 201 AND 502
                        ORDER BY CAST(u.room_no AS UNSIGNED) ASC
                    ";
                    $summary_res = mysqli_query($conn, $summary_query);
                    ?>
                    <form method="GET" class="summary-actions" id="summary-form">
                        <?php if(isset($_GET['rent_status'])): ?><input type="hidden" name="rent_status" value="<?php echo htmlspecialchars($_GET['rent_status']); ?>"><?php endif; ?>
                        <?php if(isset($_GET['rent_q'])): ?><input type="hidden" name="rent_q" value="<?php echo htmlspecialchars($_GET['rent_q']); ?>"><?php endif; ?>
                        
                        <select name="summary_m" class="btn-outline" style="flex: 1; padding: 8px 12px; font-size: 13px; height: 38px;" onchange="submitSummaryForm()">
                            <?php 
                            for($m=1; $m<=12; $m++){
                                $m_padded = str_pad($m, 2, '0', STR_PAD_LEFT);
                                $m_name = date('F', mktime(0,0,0,$m, 1));
                                $selected = ($summary_month == $m_padded) ? 'selected' : '';
                                echo "<option value='$m_padded' $selected>$m_name</option>";
                            }
                            ?>
                        </select>
                        <select name="summary_y" class="btn-outline" style="flex: 1; padding: 8px 12px; font-size: 13px; height: 38px;" onchange="submitSummaryForm()">
                            <?php 
                            $curr_y = date('Y');
                            for($y=$curr_y-1; $y<=$curr_y+1; $y++){
                                $selected = ($summary_year == $y) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; padding: 0 4px 8px 4px; border-bottom: 1px dashed var(--border); font-size: 11px; color: var(--text-gray); font-weight: 700; text-transform: uppercase;">
                        <span>Resident</span>
                        <span>Status</span>
                    </div>
                    <?php 
                    if(mysqli_num_rows($summary_res) > 0):
                        while($row = mysqli_fetch_assoc($summary_res)): 
                            $status = $row['status'];
                            if(!$status) $status = "Due";
                            if($status == 'Paid' || $status == 'Approved' || $status == 'Success') {
                                $status_text = 'Paid';
                                $badge_style = 'background: #D1FAE5; color: #059669;';
                            } elseif (strcasecmp($status, 'Partial') == 0) {
                                $status_text = 'Partial';
                                $badge_style = 'background: #FEF08A; color: #854D0E;';
                            } else {
                                $status_text = 'Due';
                                $badge_style = 'background: #FEE2E2; color: #EF4444;';
                            }
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-main); border-radius: 12px; border: 1px solid var(--border);">
                        <div style="display: flex; flex-direction: column; text-align: left;">
                            <span style="font-weight: 700; font-size: 14px; color: var(--text-dark);"><?php echo htmlspecialchars($row['name']); ?></span>
                            <span style="font-size: 11px; color: var(--text-gray);">Room <?php echo htmlspecialchars($row['room_no']); ?></span>
                        </div>
                        <span class="badge" style="font-size: 11px; padding: 4px 12px; <?php echo $badge_style; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <div style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px;">No active renters found in Room 201-502.</div>
                    <?php endif; ?>
                </div>
                <script>
                function submitSummaryForm() {
                    let url = new URL(window.location.href);
                    const form = document.getElementById('summary-form');
                    url.searchParams.set('summary_m', form.querySelector('[name=summary_m]').value);
                    url.searchParams.set('summary_y', form.querySelector('[name=summary_y]').value);
                    url.hash = 'summary-panel';
                    window.location.href = url.toString();
                }
                </script>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Recent Transactions</h2>
                    <div class="recent-tx-actions" style="display: flex; gap: 8px;">
                        <select onchange="window.location.href='?tx_range=' + this.value" class="btn-outline" style="padding: 6px 12px; font-size: 12px;">
                            <option value="All" <?php if($transaction_range == 'All') echo 'selected'; ?>>All Time</option>
                            <option value="7d" <?php if($transaction_range == '7d') echo 'selected'; ?>>Last 7 Days</option>
                            <option value="30d" <?php if($transaction_range == '30d') echo 'selected'; ?>>Last 30 Days</option>
                        </select>
                        <a href="transactions-log.php" class="btn-outline" style="font-size: 12px;">View Log</a>
                    </div>
                </div>
                <div class="table-responsive hide-mobile">
                    <table>
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Ref/ID</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($recent_transactions) == 0): ?>
                                <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-gray);">No recent transactions found.</td></tr>
                            <?php else: while ($tx = mysqli_fetch_assoc($recent_transactions)): ?>
                            <tr>
                                <td data-label="Resident">
                                    <div style="font-weight: 600;"><?php echo s($tx['name']); ?></div>
                                    <div style="font-size: 10px; color: var(--text-gray);">
                                        <?php echo date('M d, H:i', strtotime($tx['payment_date'].' '.$tx['payment_time'])); ?>
                                        <i class="bx <?php echo $tx['source'] == 'admin' ? 'bx-user-check' : 'bx-globe'; ?>" title="<?php echo $tx['source'] == 'admin' ? 'Manual' : 'Online'; ?>" style="margin-left:5px; opacity:0.6;"></i>
                                    </div>
                                </td>
                                <td data-label="Ref/ID">
                                    <code style="font-size: 10px; background: var(--bg-main); padding: 2px 4px; border-radius: 4px;">#<?php echo $tx['id']; ?></code>
                                </td>
                                <td data-label="Amount" style="font-weight: 700;">₹<?php echo number_format($tx['amount']); ?></td>
                                <td data-label="Type" style="font-size: 11px;">
                                    <span style="text-transform: capitalize;"><?php echo $tx['type']; ?></span>
                                    <div style="font-size: 9px; opacity: 0.7;"><?php echo $tx['mode']; ?></div>
                                </td>
                                <td data-label="Status">
                                    <span class="badge <?php 
                                        if($tx['status'] == 'Approved' || $tx['status'] == 'Success') echo 'badge-paid'; 
                                        elseif($tx['status'] == 'Pending') echo 'badge-due'; 
                                        else echo 'badge-overdue'; 
                                    ?>" style="font-size: 10px; padding: 4px 8px;">
                                        <?php echo $tx['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($tx['type'] == 'rent'): ?>
                                        <a href="slip.php?rent_id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 4px; border-radius: 6px;"><i class='bx bx-receipt'></i></a>
                                    <?php elseif($tx['type'] == 'electricity'): ?>
                                        <a href="generate-bill.php?id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 4px; border-radius: 6px;"><i class='bx bx-receipt'></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="hide-desktop render-mobile-cards" style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
                    <?php 
                    mysqli_data_seek($recent_transactions, 0);
                    if(mysqli_num_rows($recent_transactions) == 0): ?>
                        <div style="text-align:center; padding: 40px; color: var(--text-gray); background: var(--bg-main); border-radius: 12px;">No recent transactions found.</div>
                    <?php else: while ($tx = mysqli_fetch_assoc($recent_transactions)): ?>
                    <div style="background: var(--bg-main); border-radius: 16px; padding: 16px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(59,130,246,0.1); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                    <i class='bx <?php echo $tx['type'] == 'rent' ? 'bx-home' : 'bx-bulb'; ?>'></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;"><?php echo s($tx['name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-gray); display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                        <?php echo date('M d, H:i', strtotime($tx['payment_date'].' '.$tx['payment_time'])); ?>
                                        <i class="bx <?php echo $tx['source'] == 'admin' ? 'bx-user-check' : 'bx-globe'; ?>" style="opacity:0.6;"></i>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 800; font-size: 15px; color: var(--text-dark);">₹<?php echo number_format($tx['amount']); ?></div>
                                <div style="font-size: 10px; color: var(--text-gray); text-transform: uppercase; margin-top: 2px; font-weight: 600;">
                                    <?php echo $tx['type']; ?> &bull; <?php echo $tx['mode']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed var(--border);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge <?php 
                                    if($tx['status'] == 'Approved' || $tx['status'] == 'Success') echo 'badge-paid'; 
                                    elseif($tx['status'] == 'Pending') echo 'badge-due'; 
                                    else echo 'badge-overdue'; 
                                ?>" style="font-size: 10px; padding: 4px 8px;">
                                    <?php echo $tx['status']; ?>
                                </span>
                                <code style="font-size: 10px; background: var(--bg-main); padding: 2px 6px; border-radius: 4px; color: var(--text-gray);">#<?php echo $tx['id']; ?></code>
                            </div>
                            <div>
                                <?php if($tx['type'] == 'rent'): ?>
                                    <a href="slip.php?rent_id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; border-radius: 6px; font-size: 11px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-receipt'></i> Receipt</a>
                                <?php elseif($tx['type'] == 'electricity'): ?>
                                    <a href="generate-bill.php?id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; border-radius: 6px; font-size: 11px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-receipt'></i> Receipt</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Recent Electricity Records</h2>
                    <a href="electricity-list.php" class="btn-outline" style="font-size: 13px;">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Month</th>
                                <th>Units</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($elec_records, 0); 
                            while ($e = mysqli_fetch_assoc($elec_records)): ?>
                            <tr>
                                <td data-label="Resident" style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="#FFB302" style="flex-shrink: 0;"><path d="M11 21H9l3-9H8l7-10h2l-3 9h4l-7 10z"></path></svg>
                                    <span><?php echo htmlspecialchars($e['renter_name']); ?></span>
                                </td>
                                <td data-label="Month"><?php echo $e['month']; ?></td>
                                <td data-label="Units"><?php echo ($e['current_reading'] - $e['previous_reading']); ?></td>
                                <td data-label="Amount" style="font-weight: 600;">₹<?php echo number_format($e['amount']); ?></td>
                                <td data-label="Status"><span class="badge <?php echo $e['status'] == 'Paid' ? 'badge-paid' : 'badge-due'; ?>"><?php echo $e['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="panel" style="display: flex; flex-direction: column;">
                <div class="panel-header" style="flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center;">
                    <h2 class="pending-title" style="font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin: 0; text-align: left;">
                        Pending Payments
                        <?php $due_count = (int)($rh_kpis['due'] ?? 0); if($due_count > 0): ?>
                        <span class="pending-badge" style="font-size: 11px; background: #FEE2E2; color: #EF4444; padding: 4px 10px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; white-space: nowrap;">Active Unpaid Bills: <?php echo $due_count; ?></span>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="pending-actions-row" style="display: flex; gap: 8px; align-items: center; width: auto;">
                        <form method="GET" style="display: flex; gap: 8px; margin: 0; flex: 1;" class="pending-actions-form">
                            <select name="rent_status" class="btn-outline pending-select" style="padding: 8px 12px; font-size: 12px; border-color: var(--border); height: 36px;" onchange="submitSummaryForm()">
                                <option value="All" <?php if($rent_status_filter == 'All') echo 'selected'; ?>>All Status</option>
                                <option value="Paid" <?php if($rent_status_filter == 'Paid') echo 'selected'; ?>>Paid</option>
                                <option value="Partial" <?php if($rent_status_filter == 'Partial') echo 'selected'; ?>>Partial</option>
                                <option value="Due" <?php if($rent_status_filter == 'Due') echo 'selected'; ?>>Due</option>
                            </select>
                            <input type="text" name="rent_q" value="<?php echo s($rent_search); ?>" placeholder="Search..." class="btn-outline pending-input" style="padding: 8px 12px; font-size: 12px; width: 120px; height: 36px;">
                            <button type="submit" class="btn-primary pending-btn" style="padding: 8px 12px; font-size: 14px; height: 36px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-search'></i></button>
                        </form>
                        <a href="export-rent-history.php?month=<?php echo $target_month; ?>&status=<?php echo $rent_status_filter; ?>&q=<?php echo urlencode($rent_search); ?>" class="btn-outline pending-csv" style="padding: 8px 12px; font-size: 12px; color: #10B981; border-color: #DCFCE7; height: 36px; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class='bx bx-download'></i> CSV</a>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="table-responsive hide-mobile">
                    <table>
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Rent</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($rent_history_results) == 0): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px;">No records found.</td></tr>
                            <?php else: while($row = mysqli_fetch_assoc($rent_history_results)): ?>
                            <tr>
                                <td>
                                    <a href="view-renter.php?id=<?php echo $row['user_id']; ?>" style="font-weight: 600; text-decoration: none; color: var(--primary-purple);">
                                        <?php echo s($row['renter_name']); ?>
                                    </a>
                                    <div style="font-size: 10px; color: var(--text-gray);">Room <?php echo s($row['room_no']); ?> | <?php echo s($row['month']); ?></div>
                                </td>
                                <td>₹<?php echo number_format($row['total_amount']); ?></td>
                                <td>₹0</td>
                                <td>
                                    <span class="badge badge-due" style="background: #FEE2E2; color: #EF4444;">Due</span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="slip.php?elec_id=<?php echo $row['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 4px 8px; font-size: 10px;"><i class='bx bx-file'></i> Slip</a>
                                        <button onclick="openPaymentModal('electricity', <?php echo $row['bill_id']; ?>, <?php echo $row['total_amount']; ?>, '<?php echo addslashes($row['month']); ?>')" class="btn-primary" style="padding: 4px 8px; font-size: 10px;"><i class='bx bx-check-circle'></i> Mark Paid</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="hide-desktop render-mobile-cards">
                    <?php 
                    mysqli_data_seek($rent_history_results, 0);
                    while($row = mysqli_fetch_assoc($rent_history_results)): ?>
                    <div class="rent-history-card" onclick="this.classList.toggle('active')">
                        <div class="rent-history-header">
                            <div style="text-align: left;">
                                <div style="font-weight: 700; color: var(--text-dark);"><?php echo s($row['renter_name']); ?></div>
                                <div style="font-size: 11px; color: var(--text-gray);">Room <?php echo s($row['room_no']); ?></div>
                            </div>
                            <span class="badge" style="background: #FEE2E2; color: #EF4444; height: 100%;">Due</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span>Due Amount: <strong style="color: #EF4444;">₹<?php echo number_format($row['total_amount']); ?></strong></span>
                            <span style="font-weight: 600;">Bill: <?php echo $row['month']; ?></span>
                        </div>
                        <div class="rent-history-details">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <a href="slip.php?elec_id=<?php echo $row['bill_id']; ?>" class="btn-outline" style="justify-content: center; font-size: 12px; padding: 10px;">View Slip</a>
                                <button onclick="openPaymentModal('electricity', <?php echo $row['bill_id']; ?>, <?php echo $row['total_amount']; ?>, '<?php echo addslashes($row['month']); ?>')" class="btn-primary" style="justify-content: center; font-size: 12px; padding: 10px; width: 100%;">Mark Paid</button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>


        </div>

        <div class="right-col">
            <div class="panel calendar-panel">
                <div class="panel-header">
                    <h3 style="font-size: 16px; font-weight: 700;"><?php echo date('F Y'); ?></h3>
                </div>
                <div class="calendar-grid">
                    <div class="day-label">S</div>
                    <div class="day-label">M</div>
                    <div class="day-label">T</div>
                    <div class="day-label">W</div>
                    <div class="day-label">T</div>
                    <div class="day-label">F</div>
                    <div class="day-label">S</div>
                    <?php
                    for ($i = 0; $i < $first_day; $i++) echo '<div></div>';
                    for ($d = 1; $d <= $days_in_month; $d++) {
                        $is_today = ($d == $today) ? 'today' : '';
                        echo "<div class='calendar-day $is_today'>$d</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="panel stats-card-mini" style="background: var(--primary-purple); color: white; border: none;">
                <p style="opacity: 0.8; font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Estimated Revenue</p>
                <h2 style="font-size: 32px; font-weight: 800; margin: 12px 0;">₹<?php echo number_format($total_revenue_total); ?></h2>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                    <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 8px; font-weight: 600;">+12.5%</span>
                    <span style="opacity: 0.9;">Growth this month</span>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 16px; font-weight: 700;">Visitor Activity</h2>
                </div>
                <div class="chart-box" style="padding-top: 0;">
                    <div class="donut" style="width: 140px; height: 140px; border-width: 10px; border-top-color: #10B981;">
                        <div class="donut-val">
                            <h2 style="font-size: 20px;"><?php echo $visitor_ratio; ?>%</h2>
                            <p style="font-size: 10px;">Engagement</p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                        <span style="color: var(--text-gray);">Today's Logins</span>
                        <span style="font-weight: 700; color: var(--text-dark);"><?php echo $today_logins; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                        <span style="color: var(--text-gray);">This Month</span>
                        <span style="font-weight: 700; color: var(--text-dark);"><?php echo $month_logins; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: var(--text-gray);">Unique Visitors</span>
                        <span style="font-weight: 700; color: var(--text-dark);"><?php echo $unique_visitors; ?>/<?php echo $total_renters_count; ?></span>
                    </div>
                </div>
            </div>

            <div class="panel" style="padding: 24px !important;">
                <div class="panel-header" style="margin-bottom: 16px;">
                    <h2 style="font-size: 15px; font-weight: 700;">Live Feed</h2>
                </div>
                <div style="position: relative; padding-left: 12px; display: flex; flex-direction: column; gap: 16px;">
                    <div style="position: absolute; top: 8px; bottom: 8px; left: 3px; width: 2px; background: var(--border); z-index: 1;"></div>
                    <?php while($log = mysqli_fetch_assoc($recent_logins)): ?>
                </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</main>

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
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
            <input type="hidden" name="id" id="paymentBillId">
            <input type="hidden" name="type" id="paymentBillType">
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
                        <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Amount Paid</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" placeholder="Enter amount" required style="width: 100%; padding: 12px 16px 12px 40px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 15px; font-weight: 600; color: #334155; background: #F8FAFC; transition: all 0.2s; outline: none;">
                        </div>
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
    // Dashboard Live Search
    document.getElementById('globalSearch')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    function handlePaymentModeChange() {
        const mode = document.getElementById('paymentMode').value;
        const receiverGroup = document.getElementById('cashReceiverGroup');
        if(receiverGroup) {
            receiverGroup.style.display = (mode === 'Cash') ? 'block' : 'none';
        }
    }

    // Payment Modal Logic
    function openPaymentModal(type, id, amount, month) {
        document.getElementById('paymentBillId').value = id;
        document.getElementById('paymentBillType').value = type;
        document.getElementById('paymentBillAmount').value = amount;
        document.getElementById('paidAmountInput').value = amount;
        document.getElementById('paymentBillInfo').textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Bill for ${month} (₹${amount})`;
        
        // Init date/time
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