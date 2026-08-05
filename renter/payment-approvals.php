<?php
// renter/payment-approvals.php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
require_once "fetch_notifications.php";

// User Profile for Header
$stmt = mysqli_prepare($conn, "SELECT username, name, profile_pic, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$display_name = $user['name'] ?: $user['username'];
$profile_pic = $user['profile_pic'] ?: "assets/img/default-avatar.png";
mysqli_stmt_close($stmt);




// Fetch Approvals from DB
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$total_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM payment_notifications WHERE user_id = $user_id");
$total_row = mysqli_fetch_assoc($total_res);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

$approvals = [];
$res = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $approvals[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Approvals - <?php echo htmlspecialchars(HOUSE_NAME ?? 'Premium Renter'); ?></title>
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
    <style>
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* New Sidebar Dashboard CSS */
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
            
            /* Neons/Accents */
            --accent-red: #FF4B6B;
            --accent-yellow: #F59E0B;
            --accent-purple: #8B5CF6;
            --accent-green: #10B981;
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
            min-height: 100vh;
        }

        /* Modern Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
        }
        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
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

        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1;  overflow-y: auto;}
        .nav-menu::-webkit-scrollbar { width: 4px; }
        .nav-menu::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }


        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 13px;
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
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 2px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .btn-download:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .main-content {
            flex: 1;
            margin-left: 230px;
            padding: 32px 40px;
            max-width: calc(100% - 230px);
            box-sizing: border-box;
        }

        /* Top Header */
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;
        }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 2px; letter-spacing: -1px; }
        .header-greeting p { font-size: 13px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 2px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: rgba(98, 75, 255, 0.02); }

        /* Red Reminder Banner */
        .reminder-banner {
            background: linear-gradient(135deg, #FF6B6B, #FF4B6B);
            border-radius: 20px; padding: 24px 32px; color: white;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;
            position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(255, 75, 107, 0.2); border: 1px solid rgba(255,255,255,0.2);
        }
        .reminder-content { display: flex; align-items: center; gap: 20px; z-index: 2; }
        .reminder-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.3); }
        .reminder-text h3 { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; }
        .reminder-text p { font-size: 13px; opacity: 0.95; margin: 0; font-weight: 500; }
        .reminder-banner .btn-pay-now { background: white; color: #FF4B6B; padding: 12px 24px; border-radius: 14px; font-weight: 700; font-size: 15px; border: none; cursor: pointer; display: flex; align-items: center; gap: 2px; z-index: 2; transition: all 0.2s; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
        .reminder-banner .btn-pay-now:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .reminder-bg-art { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); opacity: 0.1; font-size: 160px; z-index: 1; pointer-events: none; }

        /* 3-Col KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .kpi-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; position: relative; overflow: hidden; transition: all 0.3s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.06); }
        .kpi-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .kpi-icon-box { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .kpi-icon-box.red { background: rgba(255, 75, 107, 0.08); color: #FF4B6B; }
        .kpi-icon-box.yellow { background: rgba(245, 158, 11, 0.08); color: #F59E0B; }
        .kpi-icon-box.purple { background: rgba(139, 92, 246, 0.08); color: #8B5CF6; }
        .kpi-icon-box.green { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .kpi-title { font-size: 13px; font-weight: 600; color: var(--text-gray); }
        .kpi-amount { font-size: 32px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 24px; }
        .kpi-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: auto; z-index: 2; }
        .kpi-tag { font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
        .kpi-tag.alert { background: rgba(255, 75, 107, 0.08); color: #FF4B6B; }
        .kpi-tag.success { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .kpi-due-date { font-size: 12px; font-weight: 600; color: var(--text-gray); display: flex; align-items: center; gap: 6px; }
        
        /* New Sparkline Styling */
        .kpi-sparkline { position: absolute; right: -5%; bottom: -5%; width: 70%; height: 60%; opacity: 0.9; z-index: 1; pointer-events: none; }

        /* 3-Col Main Grid */
        .dashboard-3col { display: grid; grid-template-columns: 1.2fr 1.1fr 1.5fr; gap: 24px; margin-bottom: 32px; align-items: stretch; }
        .dash-panel { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-title { display: flex; align-items: center; gap: 2px; font-size: 16px; font-weight: 800; margin: 0; color: var(--text-dark); }
        .panel-link { font-size: 13px; font-weight: 700; color: var(--primary-purple); text-decoration: none; transition: 0.2s; }
        .panel-link:hover { text-decoration: underline; }

        /* Upcoming Bills List */
        .bill-item { border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; background: #FAFBFC; transition: 0.2s; }
        .bill-item:hover { border-color: rgba(98,75,255,0.2); background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .bill-left { display: flex; align-items: center; gap: 14px; }
        .bill-icon { width: 42px; height: 42px; border-radius: 12px; background: rgba(255, 75, 107, 0.08); color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink:0; }
        .bill-icon.yellow { background: rgba(245, 158, 11, 0.08); color: #F59E0B; }
        .bill-icon.green { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .bill-info h4 { font-size: 13px; font-weight: 700; margin: 0 0 4px 0; color: var(--text-dark); }
        .bill-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .bill-right { text-align: right; }
        .bill-right h4 { font-size: 15px; font-weight: 800; color: #FF4B6B; margin: 0 0 6px 0; }
        .bill-right p { font-size: 11px; font-weight: 700; color: #FF4B6B; margin: 0; background: rgba(255,75,107,0.08); padding: 4px 8px; border-radius: 10px; display: inline-block;}
        .btn-view-all { width: 100%; padding: 12px; background: white; border: 1px solid rgba(98,75,255,0.3); color: var(--primary-purple); border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; margin-top: auto; text-decoration: none; display: flex; justify-content: center; transition: 0.2s; box-shadow: 0 2px 8px rgba(98,75,255,0.05); }
        .btn-view-all:hover { background: rgba(98,75,255,0.02); border-color: var(--primary-purple); }

        /* Quick Actions */
        .quick-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; flex: 1; }
        .action-card { border: 1px solid var(--border); border-radius: 16px; padding: 20px 16px; text-align: center; text-decoration: none; color: var(--text-dark); transition: all 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center; background: white; }
        .action-card:hover { border-color: rgba(98,75,255,0.2); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.03); }
        .action-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; }
        .action-card:nth-child(1) .action-icon { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); }
        .action-card:nth-child(2) .action-icon { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .action-card:nth-child(3) .action-icon { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .action-card:nth-child(4) .action-icon { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .action-card h4 { font-size: 13px; font-weight: 800; margin: 0 0 4px 0; }
        .action-card p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; }

        /* Recent Transactions */
        .transaction-list { display: flex; flex-direction: column; gap: 16px; -ms-overflow-style: none; scrollbar-width: none; }
        .transaction-list::-webkit-scrollbar { display: none; }
        .transaction-item { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .transaction-item:last-child { border-bottom: none; padding-bottom: 0; }
        .tx-left { display: flex; align-items: center; gap: 14px; }
        .tx-icon { width: 38px; height: 38px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .tx-icon.up { transform: rotate(45deg); }
        .tx-icon.elec { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .tx-icon.adv { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .tx-icon.maint { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .tx-info h4 { font-size: 13px; font-weight: 700; margin: 0 0 4px 0; color: var(--text-dark); }
        .tx-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .tx-right { display: flex; align-items: center; justify-content: flex-end; gap: 16px; }
        .tx-amount { font-size: 13px; font-weight: 800; color: #10B981; width: 75px; text-align: right; }
        .tx-amount.pending { color: #FF4B6B; }
        .tx-status { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 8px; width: 60px; text-align: center; }
        .tx-status.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .tx-status.pending { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .tx-date { font-size: 12px; color: var(--text-gray); font-weight: 600; width: 85px; text-align: right; }

        /* Footer Widgets */
        .footer-widgets { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .footer-widget { background: white; border: 1px solid var(--border); border-radius: 20px; padding: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--card-shadow); transition: 0.2s; }
        .footer-widget:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
        .fw-left { display: flex; align-items: center; gap: 16px; }
        .fw-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(98, 75, 255, 0.08); }
        .fw-icon.help { color: var(--primary-purple); }
        .fw-icon.bell { color: var(--primary-purple); }
        .fw-info h4 { font-size: 15px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); }
        .fw-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .btn-fw { border: 1px solid rgba(98, 75, 255, 0.2); background: transparent; color: var(--primary-purple); padding: 10px 14px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; box-shadow: 0 2px 8px rgba(98,75,255,0.03); }
        .btn-fw:hover { background: rgba(98, 75, 255, 0.03); border-color: var(--primary-purple); }

        /* App Footer */
        .app-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; border-top: 1px solid var(--border); margin-top: 20px; }
        .app-footer p { font-size: 12px; color: var(--text-gray); font-weight: 500; margin: 0; }

        .main-content {
            flex: 1;
            margin-left: 230px;
            padding: 32px 40px;
            max-width: calc(100% - 230px);
            box-sizing: border-box;
        }

        /* Mobile overrides */
        @media (max-width: 1400px) {
            .dashboard-3col { grid-template-columns: 1fr 1fr; }
            .dash-panel:nth-child(3) { grid-column: 1 / -1; }
        }
        @media (max-width: 992px) {
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span, .go-mobile-widget { display: none; }
            .nav-item { justify-content: center; padding: 10px 16px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; max-width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .desktop-view-wrapper { display: none !important; }
            .sidebar { display: none; }
            .main-content { margin-left: 0; max-width: 100%; padding: 0px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .dashboard-3col { grid-template-columns: 1fr; }
            .dash-panel:nth-child(3) { grid-column: auto; }
            .footer-widgets { grid-template-columns: 1fr; }
            .tx-right { gap: 12px; }
            .tx-date { display: none; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .header-actions { width: 100%; justify-content: space-between; }
            .mobile-bottom-nav { display: flex !important; }
        }
        @media (min-width: 769px) {
            .mobile-view-wrapper { display: none !important; }
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
            gap: 2px;
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
        
    
        /* My Payments V2 CSS */
        .kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .kpi-card-minimal { background: white; border-radius: 16px; padding: 20px 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 12px; transition: 0.2s; }
        .kpi-card-minimal:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
        .kpi-min-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .kpi-min-info { overflow: hidden; }
        .kpi-min-info h4 { font-size: 12px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
        .kpi-min-info h2 { font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0 0 6px 0; letter-spacing: -0.5px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
        .kpi-min-tag { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; white-space: nowrap; }
        
        .payments-container { background: white; border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 24px; }
        
        .tabs-header { display: flex; align-items: center; padding: 0 24px; border-bottom: 1px solid var(--border); gap: 32px; background: white; }
        .tab-btn { background: none; border: none; border-bottom: 2px solid transparent; padding: 20px 0; font-size: 13px; font-weight: 600; color: var(--text-gray); cursor: pointer; transition: 0.2s; }
        .tab-btn:hover { color: var(--primary-purple); }
        .tab-btn.active { color: var(--primary-purple); border-bottom-color: var(--primary-purple); }
        
        .tab-actions { margin-left: auto; display: flex; gap: 12px; align-items: center; }
        .filter-select { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text-dark); outline: none; background: #FAFBFC; font-family: 'Outfit', sans-serif; cursor: pointer; }
        .btn-filter { background: white; border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; gap: 6px; }
        
        .payments-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        .payments-table th { text-align: left; padding: 16px 12px; font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .payments-table td { padding: 16px 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
        .payments-table tr:last-child td { border-bottom: none; }
        .payments-table tr:hover td { background: #FAFBFC; }
        
        .td-bill-type { display: flex; align-items: center; gap: 12px; }
        .td-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .td-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .td-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .td-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .td-icon.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .td-info h4 { margin: 0 0 4px 0; font-size: 13px; font-weight: 700; white-space: nowrap; }
        .td-info p { margin: 0; font-size: 11px; color: var(--text-gray); font-weight: 500; white-space: nowrap; }
        
        .td-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .td-status.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .td-status.pending { background: rgba(255, 152, 0, 0.1); color: #F57C00; }
        
        .btn-view-receipt { background: none; border: none; color: var(--primary-purple); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; transition: 0.2s; text-decoration: none;}
        .btn-view-receipt:hover { background: rgba(98, 75, 255, 0.05); }
        .btn-action-pay { background: white; border: 1px solid #FF4B6B; color: #FF4B6B; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 8px; transition: 0.2s; }
        .btn-action-pay:hover { background: rgba(255, 75, 107, 0.05); }
        
        .bottom-info-bar { background: rgba(98, 75, 255, 0.04); border: 1px solid rgba(98, 75, 255, 0.1); border-radius: 16px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
        .info-text { font-size: 13px; color: var(--text-gray); font-weight: 500; display: flex; align-items: center; gap: 2px; }
        .info-text i { font-size: 18px; color: var(--primary-purple); }
        .btn-pay-pending { background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 2px; transition: 0.2s; }
        .btn-pay-pending:hover { background: var(--primary-hover); transform: translateY(-1px); }
        
        .pagination { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 24px; padding: 24px; border-top: 1px solid var(--border); }
        .page-btn { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); color: var(--text-gray); font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .page-btn:hover { background: #FAFBFC; color: var(--text-dark); border-color: #E2E8F0; }
        .page-btn.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); box-shadow: 0 4px 12px rgba(98, 75, 255, 0.3); }
        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 13px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    
        .dark-theme thead th:first-child { border-top-left-radius: 14px !important; border-bottom-left-radius: 14px !important; }
        .dark-theme thead th:last-child { border-top-right-radius: 14px !important; border-bottom-right-radius: 14px !important; }

</style>

</head>
<body style="display: block;">

<div class="app-container">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/shared_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/payment-approvals_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/payment-approvals_mobile.php'; ?>
        </div>
        </div> <!-- EXTRA CLOSING DIV TO PREVENT MOBILE DIVS FROM EATING DESKTOP WRAPPER -->

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/payment-approvals_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/payment-approvals_desktop.php'; ?>
        </div>
    </main>
</div>

<script>
    // Include notification scripts here if needed, or they are loaded elsewhere
    function closeModals() {
        // Implementation based on other pages
    }
</script>

</body>
</html>
