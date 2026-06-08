<?php
// admin/monthly-report.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$month_filter = trim($_GET['month'] ?? '');
$export = (isset($_GET['export']) && $_GET['export'] === 'csv');

$where = "";
$params = [];
$types = "";
if ($month_filter !== '') {
    $where = " WHERE month = ?";
    $params[] = $month_filter;
    $types .= "s";
}

/* Aggregate rent by month */
$qRent = mysqli_prepare($conn, "SELECT month, IFNULL(SUM(rent_amount),0) as total_rent FROM rent $where GROUP BY month ORDER BY month DESC");
if ($params) {
    mysqli_stmt_bind_param($qRent, $types, ...$params);
}
mysqli_stmt_execute($qRent);
$rents = mysqli_stmt_get_result($qRent);
$rent_rows = [];
while ($rr = mysqli_fetch_assoc($rents)) {
    $rent_rows[$rr['month']] = $rr['total_rent'];
}
mysqli_stmt_close($qRent);

/* Aggregate electricity by month */
$qElec = mysqli_prepare($conn, "SELECT month, IFNULL(SUM(total_amount),0) as total_elec FROM electricity $where GROUP BY month ORDER BY month DESC");
if ($params) {
    mysqli_stmt_bind_param($qElec, $types, ...$params);
}
mysqli_stmt_execute($qElec);
$elecs = mysqli_stmt_get_result($qElec);
$elec_rows = [];
while ($er = mysqli_fetch_assoc($elecs)) {
    $elec_rows[$er['month']] = $er['total_elec'];
}
mysqli_stmt_close($qElec);

/* months union */
$months = array_unique(array_merge(array_keys($rent_rows), array_keys($elec_rows)));
rsort($months);

if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="monthly_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Month', 'Total Rent', 'Total Electricity', 'Grand Total']);
    foreach ($months as $m) {
        $tr = $rent_rows[$m] ?? 0;
        $te = $elec_rows[$m] ?? 0;
        fputcsv($out, [$m, number_format($tr, 2, '.', ''), number_format($te, 2, '.', ''), number_format($tr + $te, 2, '.', '')]);
    }
    fclose($out);
    exit;
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');

/* Totals for KPI cards (selected filter or overall top month) */
$display_month = $month_filter ?: ($months[0] ?? 'N/A');
$disp_rent = $rent_rows[$display_month] ?? 0;
$disp_elec = $elec_rows[$display_month] ?? 0;
$disp_total = $disp_rent + $disp_elec;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monthly Report | <?php echo HOUSE_NAME; ?></title>
    <!-- Icons & Fonts -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        @media (max-width: 768px) {
            .welcome { text-align: center !important; margin-bottom: 24px !important; }
            .kpi-grid { grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
            .kpi-card { text-align: center !important; align-items: center !important; }
            .kpi-header { justify-content: center !important; }
            .dashboard-grid-70 { display: flex !important; flex-direction: column !important; gap: 24px !important; align-items: stretch !important; }
            .left-col, .right-col { width: 100% !important; }

            .panel-header { 
                flex-direction: column !important; 
                align-items: stretch !important; 
                gap: 16px !important; 
            }
            .panel-header div, .panel-header form { width: 100% !important; display: flex !important; flex-direction: column !important; gap: 10px !important; }
            .panel-header select, .panel-header a { width: 100% !important; justify-content: center !important; }

            /* Card View for Revenue History */
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            tr { background: var(--bg-main); border-radius: 16px; padding: 16px; margin-bottom: 16px; border: 1px solid var(--border); }
            td { padding: 8px 0 !important; border: none !important; display: flex !important; justify-content: space-between !important; align-items: center !important; }
            td::before { content: attr(data-label); font-weight: 700; font-size: 13px; color: var(--text-gray); }
            td:last-child { 
                border-top: 1px solid var(--border); 
                margin-top: 8px; 
                padding-top: 12px !important; 
                font-weight: 800 !important;
                color: var(--primary-purple) !important;
            }
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
                <input type="text" placeholder="Search report metrics...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="welcome animate-up">
        <h1>Monthly Report</h1>
        <p>This is your monthly revenue report for <?php echo HOUSE_NAME; ?></p>
    </div>

    <div class="kpi-grid animate-up">
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <i class='bx bx-credit-card kpi-icon'></i>
                <div class="trend trend-up"><i class='bx bx-trending-up'></i> Healthy</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($disp_rent); ?></div>
            <div class="kpi-label">Total Rent Collected</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <i class='bx bx-bolt kpi-icon' style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);"></i>
                <div class="trend trend-up"><i class='bx bx-trending-up'></i> Stable</div>
            </div>
            <div class="kpi-value">₹<?php echo number_format($disp_elec); ?></div>
            <div class="kpi-label">Electricity Revenue</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <i class='bx bx-wallet kpi-icon' style="color: #10B981; background: rgba(16, 185, 129, 0.1);"></i>
            </div>
            <div class="kpi-value">₹<?php echo number_format($disp_total); ?></div>
            <div class="kpi-label">Gross Revenue</div>
        </div>
        <div class="kpi-card hover-lift">
            <div class="kpi-header">
                <i class='bx bx-calendar kpi-icon'></i>
            </div>
            <div class="kpi-value"><?php echo count($months); ?></div>
            <div class="kpi-label">Months Audited</div>
        </div>
    </div>

    <div class="dashboard-grid-70 animate-up">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 20px; font-weight: 700;">Revenue History</h2>
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <form method="GET" style="display:flex; gap:10px;">
                            <select name="month" class="btn-outline" style="padding: 8px 16px; font-size: 13px;" onchange="this.form.submit()">
                                <option value="">All months</option>
                                <?php foreach ($months as $m): ?>
                                    <option value="<?php echo htmlspecialchars($m); ?>" <?php if ($month_filter === $m) echo 'selected'; ?>><?php echo htmlspecialchars($m); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <a href="monthly-report.php?<?php echo ($month_filter ? 'month=' . urlencode($month_filter) . '&' : '') ?>export=csv" class="btn-primary" style="padding: 8px 16px; font-size: 13px; text-decoration:none">
                            <i class='bx bx-download'></i> Export CSV
                        </a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month / Period</th>
                                <th>Rent Revenue</th>
                                <th>Elec. Revenue</th>
                                <th>Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($months)): ?>
                                <tr><td colspan="4" style="text-align: center; color: var(--text-gray);">No revenue data found.</td></tr>
                            <?php else: foreach ($months as $m): 
                                $tr = $rent_rows[$m] ?? 0; 
                                $te = $elec_rows[$m] ?? 0; 
                            ?>
                                <tr class="animate-up">
                                    <td data-label="Month / Period" style="font-weight: 600;"><?php echo htmlspecialchars($m); ?></td>
                                    <td data-label="Rent Revenue" style="color: #4B5563;">₹<?php echo number_format($tr, 2); ?></td>
                                    <td data-label="Elec. Revenue" style="color: #4B5563;">₹<?php echo number_format($te, 2); ?></td>
                                    <td data-label="Grand Total" style="font-weight: 700; color: var(--primary-purple);">₹<?php echo number_format($tr + $te, 2); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End Left Col -->

        <div class="right-col">
            <div class="panel">
                <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 24px;">Revenue Split</h2>
                <div class="chart-box">
                    <div class="donut">
                        <div class="donut-val">
                            <?php 
                                $rent_pct = ($disp_total > 0) ? round(($disp_rent / $disp_total) * 100) : 0;
                            ?>
                            <h2><?php echo $rent_pct; ?>%</h2>
                            <p>Rent Split</p>
                        </div>
                    </div>
                    <div style="margin-top: 32px; width: 100%;">
                        <div style="display:flex; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:10px; height:10px; border-radius:3px; background: var(--primary-purple);"></div>
                                <span style="font-size:13px; color: var(--text-gray);">Rent Revenue</span>
                            </div>
                            <span style="font-weight:600; font-size:13px; color: var(--text-dark);"><?php echo $rent_pct; ?>%</span>
                        </div>
                        <div style="display:flex; justify-content: space-between;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:10px; height:10px; border-radius:3px; background: var(--bg-main); border: 1px solid var(--border);"></div>
                                <span style="font-size:13px; color: var(--text-gray);">Electricity</span>
                            </div>
                            <span style="font-weight:600; font-size:13px; color: var(--text-dark);"><?php echo 100 - $rent_pct; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <button class="btn-outline" style="width:100%; margin-top: 32px; justify-content: center;" onclick="window.print()">
                    <i class='bx bx-printer'></i> Print Report
                </button>
            </div>
        </div>
    </div>
</main>

</body>
</html>
