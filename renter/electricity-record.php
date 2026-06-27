<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = mysqli_prepare($conn, "SELECT name, email, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_res);
mysqli_stmt_close($stmt);

// Constants
if(!defined('HOUSE_NAME')) define('HOUSE_NAME', 'Madhav Kunj');

// Get current year
$current_year = date("Y");

// Query 1: Total Units (This Year)
$q_total_units = "SELECT SUM(units_consumed) as total FROM electricity WHERE user_id = $user_id AND month LIKE '%$current_year'";
$res_total_units = mysqli_query($conn, $q_total_units);
$total_units = mysqli_fetch_assoc($res_total_units)['total'] ?? 0;

// Query 2: Amount Paid (This Year)
$q_amount_paid = "SELECT SUM(amount) as total FROM electricity WHERE user_id = $user_id AND status = 'Paid' AND month LIKE '%$current_year'";
$res_amount_paid = mysqli_query($conn, $q_amount_paid);
$amount_paid = mysqli_fetch_assoc($res_amount_paid)['total'] ?? 0;

// Query 3: Pending Amount
$q_pending = "SELECT SUM(amount) as total FROM electricity WHERE user_id = $user_id AND status != 'Paid'";
$res_pending = mysqli_query($conn, $q_pending);
$pending_amount = mysqli_fetch_assoc($res_pending)['total'] ?? 0;

// Fetch all electricity records
$records_q = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id = $user_id ORDER BY id DESC");
$electricity_records = [];
while($row = mysqli_fetch_assoc($records_q)) {
    $electricity_records[] = $row;
}

// Chart Data (last 12 chronological)
$chart_records = array_slice($electricity_records, 0, 12);
$chart_records = array_reverse($chart_records);
$chart_labels = [];
$chart_data = [];
foreach($chart_records as $cr) {
    $dateObj = DateTime::createFromFormat('F Y', $cr['month']);
    $shortMonth = $dateObj ? $dateObj->format('M Y') : $cr['month'];
    $chart_labels[] = $shortMonth;
    $chart_data[] = $cr['units_consumed'];
}

// Last Recorded Reading & Current Month Details
$latest_record = $electricity_records[0] ?? null;
$last_reading = $latest_record['current_reading'] ?? 0;
// We'll use created_at for the date of the reading, or current date if none
$last_reading_date = $latest_record ? date("d M Y", strtotime($latest_record['created_at'])) : 'N/A';

function money($val) {
    return '₹' . number_format((float)$val, 2);
}

// Unread Notifications Count (mock or real)
$dismissed_cookie = $_COOKIE['dismissed_notifs'] ?? '';
$dismissed_ids = $dismissed_cookie ? explode(',', $dismissed_cookie) : [];
$unread_count = 0;
$ann_q = mysqli_query($conn, "SELECT id FROM announcements WHERE created_at >= NOW() - INTERVAL 1 DAY");
if($ann_q){
    while($ac = mysqli_fetch_assoc($ann_q)) {
        if(!in_array('ann_'.$ac['id'], $dismissed_ids)) {
            $unread_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Record - <?php echo HOUSE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #FAFBFC;
            --sidebar-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --primary-purple: #624BFF;
            --primary-hover: #5039E6;
            --border: #F1F5F9;
            --white: #FFFFFF;
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.03);
        }


        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: var(--bg-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-dark);
            display: block !important;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Overrides & Additions */
        .sidebar { width: 260px; background: white; padding: 24px 20px; border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow-y: auto; height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; -ms-overflow-style: none; scrollbar-width: none; }
        .sidebar::-webkit-scrollbar { display: none; }
        .sidebar-header { margin-bottom: 40px; display: flex; align-items: center; gap: 12px; }
        .sidebar-logo { width: 40px; height: 40px; background: #1E293B; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: 800; }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; color: var(--text-dark); margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        
        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all 0.2s ease;
        }
        .nav-item i { font-size: 18px; opacity: 0.8; }
        .nav-item:hover { background: rgba(98, 75, 255, 0.03); color: var(--primary-purple); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.25); }
        .nav-item.active i { opacity: 1; }
        
        .save-more-card { background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1); border-radius: 16px; padding: 20px; text-align: center; margin-top: 24px; }
        .save-more-card h4 { font-size: 15px; font-weight: 800; color: var(--text-dark); margin: 0 0 6px 0; }
        .save-more-card p { font-size: 12px; color: var(--text-gray); font-weight: 500; margin: 0 0 16px 0; line-height: 1.4; }
        .save-more-img { width: 120px; margin: 0 auto 16px auto; display: block; } /* Using an icon fallback for illustration */
        .btn-tips { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; background: white; border: 1px solid rgba(139, 92, 246, 0.2); color: var(--primary-purple); padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; text-decoration: none;}
        .btn-tips:hover { border-color: var(--primary-purple); }

        /* Main Content */
        .main-content { margin-left: 260px; padding: 32px 40px; min-height: 100vh; background: #FAFBFC; max-width: calc(100% - 260px); box-sizing: border-box; }
        
        /* Top Header */
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header-title h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-title p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        
        /* Icons and buttons */
        .icon-btn { width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; transition: 0.2s; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.02); text-decoration: none; }
        .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .btn-support { border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple); padding: 10px 16px; height: auto; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .btn-support:hover { background: rgba(98, 75, 255, 0.02); }
        
        .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px; transition: 0.2s; }
        .user-avatar { width: 38px; height: 38px; background: var(--primary-purple); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; }
        
        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .kpi-card { background: white; border-radius: 20px; padding: 20px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 14px; transition: all 0.3s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.06); }
        
        .kpi-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .kpi-icon.purple { background: rgba(139, 92, 246, 0.08); color: #8B5CF6; }
        .kpi-icon.green { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .kpi-icon.orange { background: rgba(245, 158, 11, 0.08); color: #F59E0B; }
        .kpi-icon.blue { background: rgba(59, 130, 246, 0.08); color: #3B82F6; }
        
        .kpi-info h4 { font-size: 12px; font-weight: 600; color: var(--text-gray); margin: 0 0 4px 0; }
        .kpi-info h2 { font-size: 24px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; margin: 0 0 4px 0; }
        .kpi-info p { font-size: 11px; font-weight: 600; color: var(--text-gray); margin: 0; }
        .kpi-info p.green { color: #10B981; }

        /* Two Column Layout */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
        
        .panel { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-header h3 { font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0; }
        
        /* Current Month Details */
        .cmd-panel { background: rgba(139, 92, 246, 0.02); }
        .cmd-panel .panel-header h3 { display: flex; align-items: center; gap: 8px; color: var(--primary-purple); }
        .cmd-list { display: flex; flex-direction: column; gap: 12px; }
        .cmd-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .cmd-item:last-child { border-bottom: none; padding-bottom: 0; }
        .cmd-label { font-size: 13px; color: var(--text-gray); font-weight: 500; }
        .cmd-value { font-size: 13px; color: var(--text-dark); font-weight: 700; }
        
        .cmd-total { display: flex; justify-content: space-between; align-items: center; background: rgba(139, 92, 246, 0.08); padding: 12px 16px; border-radius: 12px; margin-top: 12px; }
        .cmd-total .cmd-label { color: var(--primary-purple); font-weight: 700; font-size: 14px; }
        .cmd-total .cmd-value { color: var(--primary-purple); font-weight: 800; font-size: 16px; }

        /* Dropdown styling */
        .filter-select { padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 12px; font-weight: 600; color: var(--text-gray); outline: none; background: white; cursor: pointer; }
        .btn-filter-small { background: white; border: 1px solid var(--border); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; gap: 6px; }

        /* Table */
        .table-responsive { overflow-x: auto; margin-top: 16px; }
        .er-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        .er-table th { text-align: left; padding: 12px; font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .er-table td { padding: 14px 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid var(--border); vertical-align: middle; }
        .er-table tr:hover td { background: #FAFBFC; }
        
        .badge-current { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; margin-left: 8px; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; text-align: center; width: 65px;}
        .status-badge.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .status-badge.unpaid { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .status-badge.due { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }

        .btn-table-action { background: white; border: 1px solid rgba(139, 92, 246, 0.2); color: var(--primary-purple); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; }
        .btn-table-action:hover { background: rgba(139, 92, 246, 0.05); }
        .btn-table-action.pay { border-color: rgba(139, 92, 246, 0.4); background: rgba(139, 92, 246, 0.05); }
        .btn-table-action.pay:hover { background: rgba(139, 92, 246, 0.1); }
        
        .view-more-container { text-align: center; margin-top: 16px; padding-top: 16px; }
        .btn-view-more { background: none; border: none; color: var(--primary-purple); font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        
    </style>
</head>
<body>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class='bx bx-home-heart'></i>
            </div>
            <div class="sidebar-brand">
                <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
                <p>Resident Dashboard</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class='bx bx-grid-alt'></i>
                <span>Dashboard</span>
            </a>
            <a href="my-payments.php" class="nav-item">
                <i class='bx bx-wallet'></i>
                <span>My Payments</span>
            </a>
            <a href="payment-history.php" class="nav-item">
                <i class='bx bx-history'></i>
                <span>Payment History</span>
            </a>
            <a href="electricity-record.php" class="nav-item active">
                <i class='bx bx-bolt-circle'></i>
                <span>Electricity Record</span>
            </a>
            <a href="my-bills.php" class="nav-item">
                <i class=\'bx bx-receipt\'></i>
                <span>My Bills</span>
            </a>
            <a href="queries.php" class="nav-item">
                <i class='bx bx-message-square-dots'></i>
                <span>Raise Query</span>
            </a>
            <a href="#" class="nav-item">
                <i class='bx bx-bell'></i>
                <span>Notices</span>
            </a>
            <a href="#" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
        </nav>
        
        <div class="save-more-card">
            <h4>Save More</h4>
            <p>Conserve electricity and save on your monthly bills.</p>
            <!-- Using an illustration approximation with boxicons -->
            <div style="font-size: 64px; color: #F59E0B; margin-bottom: 16px;">
                <i class='bx bx-bulb'></i>
            </div>
            <a href="#" class="btn-tips"><i class='bx bx-bulb'></i> Tips to Save Electricity</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header" style="padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 24px;">
            <div class="header-title" style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);">
                    <i class='bx bx-bolt-circle' style="font-size: 28px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 6px 0;">Electricity Record</h1>
                    <p>Track your monthly electricity usage and billing details.</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="icon-btn bell-icon">
                    <i class='bx bx-bell'></i>
                    <?php if($unread_count > 0): ?>
                    <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; border: 2px solid white;">
                        <?php echo $unread_count; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="icon-btn">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="#" class="btn-support">
                    <i class='bx bx-help-circle'></i> Help & Support
                </a>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <p>Room <?php echo htmlspecialchars($user['room_no']); ?></p>
                    </div>
                    <i class='bx bx-chevron-down' style="color: var(--text-gray); font-size: 18px;"></i>
                </div>
            </div>
        </header>
        
        <!-- KPI Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class='bx bx-credit-card'></i></div>
                <div class="kpi-info">
                    <h4>Total Units (This Year)</h4>
                    <h2><?php echo number_format($total_units); ?> Units</h2>
                    <p>Total electricity consumed</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon green"><i class='bx bx-money'></i></div>
                <div class="kpi-info">
                    <h4>Amount Paid (This Year)</h4>
                    <h2><?php echo money($amount_paid); ?></h2>
                    <p>Total paid for electricity</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class='bx bx-time-five'></i></div>
                <div class="kpi-info">
                    <h4>Pending Amount</h4>
                    <h2><?php echo money($pending_amount); ?></h2>
                    <?php if($pending_amount == 0): ?>
                        <p class="green">All payments cleared</p>
                    <?php else: ?>
                        <p style="color: #FF4B6B;">Outstanding dues</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class='bx bx-tachometer'></i></div>
                <div class="kpi-info">
                    <h4>Last Recorded Reading</h4>
                    <h2><?php echo number_format($last_reading); ?> Units</h2>
                    <p><?php echo $last_reading_date; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid (Chart + Current Details) -->
        <div class="dashboard-grid">
            <!-- Chart Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Usage Overview (Units)</h3>
                    <select class="filter-select">
                        <option>Units</option>
                        <option>Amount</option>
                    </select>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>
            
            <!-- Current Month Details -->
            <div class="panel cmd-panel">
                <div class="panel-header" style="margin-bottom: 20px;">
                    <h3><i class='bx bx-bolt-circle'></i> Current Month Details</h3>
                </div>
                <?php if($latest_record): ?>
                <div class="cmd-list">
                    <div class="cmd-item">
                        <span class="cmd-label">Billing Month</span>
                        <span class="cmd-value"><?php echo htmlspecialchars($latest_record['month']); ?></span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Previous Reading</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['previous_reading']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Current Reading</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['current_reading']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Units Consumed</span>
                        <span class="cmd-value"><?php echo number_format($latest_record['units_consumed']); ?> Units</span>
                    </div>
                    <div class="cmd-item">
                        <span class="cmd-label">Rate per Unit</span>
                        <span class="cmd-value">₹<?php echo number_format((float)$latest_record['rate_per_unit'], 2); ?></span>
                    </div>
                </div>
                <div class="cmd-total">
                    <span class="cmd-label">Amount Payable</span>
                    <span class="cmd-value"><?php echo money($latest_record['amount']); ?></span>
                </div>
                <?php else: ?>
                <div style="text-align: center; color: var(--text-gray); padding: 40px 0;">
                    <i class='bx bx-info-circle' style="font-size: 32px; opacity: 0.5; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px; margin: 0;">No records found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Electricity Record Table Panel -->
        <div class="panel">
            <div class="panel-header" style="margin-bottom: 16px;">
                <h3>Electricity Record</h3>
                <div style="display: flex; gap: 12px;">
                    <select class="filter-select">
                        <option>All Years</option>
                        <option><?php echo $current_year; ?></option>
                    </select>
                    <button class="btn-filter-small">
                        <i class='bx bx-filter-alt'></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="er-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Month / Year</th>
                            <th style="text-align: right;">Prev. Reading<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Curr. Reading<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Consumed<br><span style="text-transform:none; font-weight: 500;">(Units)</span></th>
                            <th style="text-align: right;">Amount<br><span style="text-transform:none; font-weight: 500;">(₹)</span></th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Paid On</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recordTableBody">
                        <?php 
                        $counter = 1;
                        foreach($electricity_records as $idx => $rec): 
                            $is_current = ($idx === 0); // Assuming sorted DESC by ID
                            $status_class = strtolower($rec['status']);
                            if ($status_class == 'due') $status_class = 'unpaid';
                            $status_text = ucfirst($status_class);
                            if ($status_text == 'Due') $status_text = 'Unpaid';
                        ?>
                        <tr class="rec-row" data-index="<?php echo $idx; ?>">
                            <td style="text-align: center; color: var(--text-gray); font-weight: 500;"><?php echo $counter++; ?></td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <?php echo htmlspecialchars($rec['month']); ?>
                                </div>
                            </td>
                            <td style="text-align: right;"><?php echo number_format($rec['previous_reading']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($rec['current_reading']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($rec['units_consumed']); ?></td>
                            <td style="text-align: right; font-weight: 800;"><?php echo money($rec['amount']); ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td style="text-align: center; color: var(--text-gray); font-weight: 500;">
                                <?php echo ($rec['status'] == 'Paid' && !empty($rec['payment_date'])) ? date("d M Y", strtotime($rec['payment_date'])) : '&mdash;'; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="../admin/slip.php?elec_id=<?php echo $rec['id']; ?>" class="btn-table-action"><i class='bx bx-receipt'></i> View Bill</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="view-more-container" id="viewMoreContainer" style="<?php echo (count($electricity_records) > 5) ? '' : 'display:none;'; ?>">
                <button class="btn-view-more" onclick="showAllRecords()">
                    View More Records <i class='bx bx-chevron-down'></i>
                </button>
            </div>
        </div>

    </main>
</div>

<script>
    // Data for Chart
    const labels = <?php echo json_encode($chart_labels); ?>;
    const dataPoints = <?php echo json_encode($chart_data); ?>;

    // Initialize Chart
    const ctx = document.getElementById('usageChart').getContext('2d');
    
    // Create gradient for fill
    let gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Units Consumed',
                data: dataPoints,
                borderColor: '#8B5CF6',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#8B5CF6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 10,
                    titleFont: { family: 'Outfit', size: 13 },
                    bodyFont: { family: 'Outfit', size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' Units';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: { font: { family: 'Outfit', size: 11 }, color: '#94A3B8' }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Outfit', size: 11 }, color: '#94A3B8' }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });

    // Simple View More logic
    const allRows = document.querySelectorAll('.rec-row');
    const viewMoreBtn = document.getElementById('viewMoreContainer');
    
    // Initially hide rows after 5
    allRows.forEach((row, index) => {
        if(index >= 5) {
            row.style.display = 'none';
        }
    });

    function showAllRecords() {
        allRows.forEach(row => {
            row.style.display = 'table-row';
        });
        viewMoreBtn.style.display = 'none';
    }
</script>

</body>
</html>
