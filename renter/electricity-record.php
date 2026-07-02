<?php
session_start();
require_once "../db.php";
require_once "fetch_notifications.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = mysqli_prepare($conn, "SELECT name, email, room_no, profile_pic FROM users WHERE id = ?");
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
$chart_amounts = [];
foreach($chart_records as $cr) {
    $dateObj = DateTime::createFromFormat('!F Y', $cr['month']);
    $shortMonth = $dateObj ? $dateObj->format('M Y') : $cr['month'];
    $chart_labels[] = $shortMonth;
    $chart_data[] = (float)$cr['units_consumed'];
    $chart_amounts[] = (float)$cr['amount'];
}

// Last Recorded Reading & Current Month Details
$latest_record = $electricity_records[0] ?? null;
$last_reading = $latest_record['current_reading'] ?? 0;
$last_reading_date = $latest_record ? date("d M Y", strtotime($latest_record['created_at'])) : 'N/A';

function money($val) {
    return '₹' . number_format((float)$val, 2);
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

        .dark-theme {
            --bg-main: #0B0F19;
            --sidebar-bg: #111827;
            --text-dark: #F8FAFC;
            --text-gray: #94A3B8;
            --border: #1E293B;
            --white: #111827;
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.35);
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
            width: 100%;
            min-height: 100vh;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 230px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        .sidebar-logo {
            width: 40px; height: 40px;
            background: #1E293B; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 800;
        }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
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

        .go-mobile-widget {
            background: rgba(98, 75, 255, 0.03); border: 1px solid rgba(98, 75, 255, 0.05);
            border-radius: 16px; padding: 16px; text-align: left;
            margin-top: auto;
        }
        .go-mobile-widget h4 { font-size: 15px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); }
        .go-mobile-widget p { font-size: 12px; color: var(--text-gray); margin-bottom: 12px; line-height: 1.4; }
        .go-mobile-imgs { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .go-mobile-imgs .mock-phone { width: 50px; height: 80px; background: #333; border-radius: 8px; border: 2px solid #111; display: flex; align-items: center; justify-content: center; }
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .btn-download:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .main-content { flex: 1; margin-left: 230px; padding: 32px 40px; min-height: 100vh; background: var(--bg-main); max-width: calc(100% - 230px); box-sizing: border-box; }
        
        /* Top Header */
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }
        .header-title h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-title p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        
        /* Icons and buttons */
        .icon-btn { width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; transition: 0.2s; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.02); text-decoration: none; }
        .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .btn-outline-support { border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple); padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02); white-space: nowrap; }
        .btn-outline-support:hover { background: rgba(98, 75, 255, 0.02); }
        
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
                    .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    
    /* Standardized Notification Dropdown CSS */
    .notification-wrapper { position: relative; }
    #notifDropdown { 
        position: absolute; 
        top: 110%; 
        right: 0; 
        width: 340px; 
        background: white; 
        border-radius: 16px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
        border: 1px solid var(--border); 
        z-index: 99999; 
        overflow: hidden; 
        text-align: left;
    }


        /* Dark Theme Specific Overrides for Electricity Record */
        .dark-theme .main-content {
            background: var(--bg-main) !important;
        }
        .dark-theme .kpi-card,
        .dark-theme .panel,
        .dark-theme .icon-btn,
        .dark-theme .btn-outline-support,
        .dark-theme #notifDropdown {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .header-title h1,
        .dark-theme .panel-header h3,
        .dark-theme .cmd-value,
        .dark-theme .kpi-info h2 {
            color: var(--text-dark) !important;
        }
        .dark-theme .cmd-panel {
            background: rgba(255, 255, 255, 0.02) !important;
        }
        .dark-theme .cmd-item {
            background: transparent !important;
            border: none !important;
            border-bottom: 1px solid var(--border) !important;
            box-shadow: none !important;
        }
        .dark-theme .cmd-item:last-child {
            border-bottom: none !important;
        }
        .dark-theme .filter-select {
            background: var(--bg-main) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .btn-filter-small {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .er-table tr:hover td {
            background: rgba(255, 255, 255, 0.03) !important;
        }
        .dark-theme .btn-table-action {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .btn-table-action.pay {
            background: rgba(139, 92, 246, 0.15) !important;
        }

    
        /* EXCLUSIVE MOBILE VIEW MODE STYLES - ZERO IMPACT ON DESKTOP */
        @media screen and (max-width: 991px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 14px !important; }
            .grid-2-1, .dashboard-3col { grid-template-columns: 1fr !important; gap: 20px !important; }
            .sidebar { width: 80px !important; padding: 24px 10px !important; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span, .go-mobile-widget { display: none !important; }
            .nav-item { justify-content: center !important; padding: 12px !important; }
            .nav-item i { font-size: 24px !important; }
            .main-content { margin-left: 80px !important; max-width: calc(100% - 80px) !important; }
        }

        @media screen and (max-width: 768px) {
            .sidebar { display: none !important; }
            .main-content { 
                margin-left: 0 !important; 
                max-width: 100% !important; 
                padding: 16px !important; 
                padding-bottom: 86px !important; /* Space for bottom nav */
            }
            .kpi-grid { grid-template-columns: 1fr !important; gap: 12px !important; }
            .grid-2-1, .dashboard-3col, .cmd-grid { grid-template-columns: 1fr !important; gap: 16px !important; }
            .header-renter, .top-header { 
                flex-direction: column !important; 
                align-items: flex-start !important; 
                gap: 16px !important; 
            }
            .header-actions { width: 100% !important; justify-content: space-between !important; }
            .table-responsive { 
                overflow-x: auto !important; 
                -webkit-overflow-scrolling: touch !important; 
                width: 100% !important;
            }
            .payment-tabs { 
                display: flex !important; 
                overflow-x: auto !important; 
                padding-bottom: 6px !important; 
                gap: 8px !important; 
            }
            .tab-btn { white-space: nowrap !important; flex-shrink: 0 !important; }
            .table-header { flex-direction: column !important; align-items: stretch !important; gap: 12px !important; }
            .table-header > div { width: 100% !important; justify-content: space-between !important; }
            .footer-widgets { grid-template-columns: 1fr !important; gap: 16px !important; }
            .tx-right { gap: 12px !important; }
            .tx-date { display: none !important; }
            
            /* Show Universal Mobile Bottom Navigation */
            .mobile-bottom-nav { display: flex !important; }
        }

        /* Mobile Bottom Nav Bar Default (Hidden on Desktop) */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: var(--white, #FFFFFF);
            border-top: 1px solid var(--border, #F1F5F9);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
            z-index: 9999;
            justify-content: space-around;
            align-items: center;
            padding: 0 8px;
        }
        .dark-theme .mobile-bottom-nav {
            background: #111827;
            border-top-color: #1E293B;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
        }
        .mb-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-gray, #64748B);
            font-size: 11px;
            font-weight: 600;
            gap: 4px;
            transition: all 0.2s ease;
            padding: 6px 12px;
            border-radius: 12px;
        }
        .mb-nav-item i { font-size: 22px; }
        .mb-nav-item.active {
            color: var(--primary-purple, #624BFF);
        }


        .mb-nav-center {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #624BFF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer;
            margin-top: -24px;
            border: 4px solid var(--white, #FFFFFF);
            transition: transform 0.2s;
        }
        .dark-theme .mb-nav-center {
            border-color: #111827;
        }


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
            <a href="electricity-record.php" class="nav-item active">
                <i class='bx bx-bolt-circle'></i>
                <span>Electricity Record</span>
            </a>
            <a href="my-bills.php" class="nav-item">
                <i class='bx bx-receipt'></i>
                <span>My Bills</span>
            </a>
            <a href="queries.php" class="nav-item">
                <i class='bx bx-message-square-dots'></i>
                <span>Raise Query</span>
            </a>
            <a href="notices.php" class="nav-item">
                <i class='bx bx-bell'></i>
                <span>Notices</span>
            </a>
            <a href="documents.php" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </nav>
        
        <div class="go-mobile-widget">
            <h4>Go Mobile!</h4>
            <p>Manage your payments on the go.</p>
            <div class="go-mobile-imgs">
                <div class="mock-phone">
                    <i class='bx bx-wallet' style="color: white; font-size: 20px;"></i>
                </div>
                <div class="mock-qr">
                    <img src="../assets/img/qr-placeholder.png" alt="QR" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlMGUwZTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTBweCIgZmlsbD0iIzY2NiIgZG1pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5RUjwvdGV4dD48L3N2Zz4='">
                </div>
            </div>
            <a href="#" class="btn-download"><i class='bx bx-download'></i> Download App</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/electricity-record_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/electricity-record_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/electricity-record_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/electricity-record_desktop.php'; ?>
        </div>
</main>
</div>

<script>
    // Data for Chart
    const labels = <?php echo json_encode($chart_labels); ?>;
    const unitsData = <?php echo json_encode($chart_data); ?>;
    const amountData = <?php echo json_encode($chart_amounts); ?>;

    let currentMetric = 'units';
    let usageCharts = [];

    document.querySelectorAll('#usageChart').forEach((canvas, index) => {
        const ctx = canvas.getContext('2d');
        
        let unitsGradient = ctx.createLinearGradient(0, 0, 0, 250);
        unitsGradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
        unitsGradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

        let amountGradient = ctx.createLinearGradient(0, 0, 0, 250);
        amountGradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
        amountGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        let usageChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Units Consumed',
                data: unitsData,
                borderColor: '#8B5CF6',
                backgroundColor: unitsGradient,
                borderWidth: 2.5,
                pointBackgroundColor: '#8B5CF6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4.5,
                pointHoverRadius: 7,
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
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 12,
                    titleFont: { family: 'Outfit', size: 13 },
                    bodyFont: { family: 'Outfit', size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            if (currentMetric === 'amount') {
                                return '₹' + context.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                            return context.parsed.y + ' Units';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                    ticks: {
                        font: { family: 'Outfit', size: 11 },
                        color: '#64748B',
                        callback: function(value) {
                            if (currentMetric === 'amount') {
                                return '₹' + value;
                            }
                            return value;
                        }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Outfit', size: 11 }, color: '#64748B' }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
        });
        usageCharts.push({chart: usageChart, unitsGrad: unitsGradient, amountGrad: amountGradient});
    });

    document.querySelectorAll('#chartMetricSelect').forEach((metricSelect, idx) => {
        const titleText = document.querySelectorAll('#chartTitleText')[idx];
        if (metricSelect && titleText && usageCharts[idx]) {
            metricSelect.addEventListener('change', function() {
                currentMetric = this.value;
                let c = usageCharts[idx];
                if (currentMetric === 'amount') {
                    titleText.textContent = 'Usage Overview (Amount)';
                    c.chart.data.datasets[0].label = 'Electricity Amount';
                    c.chart.data.datasets[0].data = amountData;
                    c.chart.data.datasets[0].borderColor = '#10B981';
                    c.chart.data.datasets[0].backgroundColor = c.amountGrad;
                    c.chart.data.datasets[0].pointBackgroundColor = '#10B981';
                } else {
                    titleText.textContent = 'Usage Overview (Units)';
                    c.chart.data.datasets[0].label = 'Units Consumed';
                    c.chart.data.datasets[0].data = unitsData;
                    c.chart.data.datasets[0].borderColor = '#8B5CF6';
                    c.chart.data.datasets[0].backgroundColor = c.unitsGrad;
                    c.chart.data.datasets[0].pointBackgroundColor = '#8B5CF6';
                }
                c.chart.update();
            });
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
<script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>

<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item "><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item "><i class='bx bx-user'></i><span>Profile</span></a>
</nav>

</body>
</html>