<?php
// renter/notices.php - Redesigned with Unified SaaS UI
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
$usr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$display_name = $usr['name'] ?: $usr['username'];
$profile_pic = $usr['profile_pic'] ?: "assets/img/default-avatar.png";
mysqli_stmt_close($stmt);


// Fetch Announcements from DB
$notices = [];
$res = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $priority = $row['priority'] ?? 'Normal';
        $category = $priority === 'High' ? 'Important' : 'General';
        
        if ($category === 'Important') {
            $icon = 'bxs-megaphone';
            $icon_bg = 'rgba(239, 68, 68, 0.1)';
            $icon_color = '#EF4444';
            $badge_bg = 'rgba(239, 68, 68, 0.1)';
            $badge_color = '#EF4444';
        } else {
            $icon = 'bx-info-circle';
            $icon_bg = 'rgba(98, 75, 255, 0.1)';
            $icon_color = 'var(--primary-purple)';
            $badge_bg = 'rgba(98, 75, 255, 0.1)';
            $badge_color = 'var(--primary-purple)';
        }

        $ts = strtotime($row['created_at']);
        $full_desc = $row['message'];
        $desc = mb_strlen($full_desc) > 80 ? mb_substr($full_desc, 0, 80) . '...' : $full_desc;

        $notices[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'category' => $category,
            'icon' => $icon,
            'icon_bg' => $icon_bg,
            'icon_color' => $icon_color,
            'badge_bg' => $badge_bg,
            'badge_color' => $badge_color,
            'desc' => $desc,
            'full_desc' => $full_desc,
            'date' => date('d M Y', $ts),
            'time' => date('h:i A', $ts),
            'is_new' => true
        ];
    }
}

// Calculate KPIs
$total_notices = count($notices);
$new_notices = 0;
$this_month_notices = 0;
$important_notices = 0;

$now = time();
$current_month = date('Y-m');
$current_month_name = date('M Y');

foreach ($notices as $n) {
    // $n['date'] is in 'd M Y' format, which strtotime handles well
    $ts = strtotime($n['date']);
    if (($now - $ts) <= 7 * 86400) {
        $new_notices++;
    }
    if (date('Y-m', $ts) === $current_month) {
        $this_month_notices++;
    }
    if ($n['category'] === 'Important') {
        $important_notices++;
    }
}

// Update unread_count header badge to reflect new notices


// Pagination logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 7;
$total_items = count($notices);
$total_pages = ceil($total_items / $items_per_page);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $items_per_page;
$paginated_notices = array_slice($notices, $offset, $items_per_page);

$start_item = $offset + 1;
$end_item = min($offset + $items_per_page, $total_items);

// Handle AJAX Request for details
if (isset($_GET['ajax_id'])) {
    $id = (int)$_GET['ajax_id'];
    $selected = $notices[0];
    foreach($notices as $n) {
        if ($n['id'] === $id) {
            $selected = $n; break;
        }
    }
    
    // Output HTML for the detail pane only
    ?>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
        <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #EF4444; display: flex; align-items: center; gap: 6px;">
            <i class='bx bx-star'></i> Important Notice
        </h4>
    </div>
    
    <div style="text-align: center; margin-bottom: 32px;">
        <div style="width: 80px; height: 80px; border-radius: 20px; background: <?php echo $selected['icon_bg']; ?>; color: <?php echo $selected['icon_color']; ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px;">
            <i class='bx <?php echo $selected['icon']; ?>'></i>
        </div>
        <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 800; color: var(--text-dark);"><?php echo htmlspecialchars($selected['title']); ?></h2>
        <div style="font-size: 13px; font-weight: 600; color: var(--text-gray); display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span><?php echo $selected['date']; ?></span>
            <span style="width: 4px; height: 4px; border-radius: 50%; background: var(--border);"></span>
            <span><?php echo $selected['time']; ?></span>
        </div>
    </div>
    
    <div style="padding-top: 24px; border-top: 1px dashed var(--border); font-size: 14px; line-height: 1.8; color: var(--text-dark); flex: 1;">
        <?php echo nl2br(htmlspecialchars($selected['full_desc'])); ?>
    </div>
    
    <?php
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Notices | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <script>
        window.HOUSE_NAME = <?php echo json_encode(HOUSE_NAME); ?>;
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    
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
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
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

        .app-container { display: flex; min-height: 100vh; }

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
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: var(--white); padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .btn-download:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .main-content {
            flex: 1; margin-left: 230px; padding: 32px 40px; max-width: calc(100% - 230px); box-sizing: border-box;
        }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }
        
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: var(--white);
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        
        .page-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: var(--white);
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .page-btn:hover:not(:disabled) { background: #f8fafc; border-color: rgba(98, 75, 255, 0.3); color: var(--primary-purple); }
        .page-btn.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
        
        .btn-outline {
            border: 1px solid rgba(98, 75, 255, 0.15); background: var(--white); color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap; cursor: pointer;
        }
        .btn-outline:hover { background: rgba(98, 75, 255, 0.02); }

        /* KPI Grid */
        .kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 20px; box-shadow: var(--card-shadow); display: flex; flex-direction: column; gap: 16px; min-height: 120px; }
        .kpi-card-top { display: flex; align-items: center; gap: 16px; }
        .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .kpi-info h4 { margin: 0 0 4px 0; font-size: 13px; color: #64748b; font-weight: 700; }
        .kpi-info h2 { margin: 0; font-size: 24px; color: var(--text-dark); font-weight: 800; line-height: 1; }
        .kpi-badge-wrap { display: flex; justify-content: center; width: 100%; margin-top: auto; }
        .kpi-badge { display: inline-flex; align-items: center; justify-content: center; padding: 6px 20px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }

        /* Notices Layout */
        .notice-layout {
            display: block;
        }
        .list-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 24px; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column; min-height: 600px; width: 100%;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: 0.3s;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active {
            opacity: 1; pointer-events: auto;
        }
        .detail-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 24px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 700px; max-width: 92%; max-height: 85vh; overflow-y: auto;
            transform: translateY(20px) scale(0.95); transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative;
            display: flex; flex-direction: column;
        }
        .modal-overlay.active .detail-card {
            transform: translateY(0) scale(1);
        }
        
        /* Modern Scrollbar for Modal */
        .detail-card::-webkit-scrollbar {
            width: 6px;
        }
        .detail-card::-webkit-scrollbar-track {
            background: transparent;
            margin: 10px 0;
        }
        .detail-card::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.2);
            border-radius: 10px;
        }
        .detail-card::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.4);
        }

        .modal-close {
            position: absolute; top: 24px; right: 24px;
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; background: var(--bg-main); color: var(--text-gray); transition: 0.2s;
            z-index: 10;
        }
        .modal-close:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; transform: rotate(90deg); }

        .tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; flex: 1; scrollbar-width: none; -ms-overflow-style: none; }
        .tabs::-webkit-scrollbar { display: none; }
        .tab {
            padding: 0 4px 12px 4px; font-size: 14px; font-weight: 700; color: var(--text-gray);
            cursor: pointer; position: relative; white-space: nowrap; transition: 0.2s;
        }
        .tab:hover { color: var(--primary-purple); }
        .tab.active { color: var(--primary-purple); }
        .tab.active::after {
            content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 3px;
            background: var(--primary-purple); border-radius: 3px 3px 0 0;
        }

        /* Notice List Item */
        .notice-item {
            display: flex; align-items: flex-start; padding: 16px 20px; border-radius: 16px;
            gap: 16px; cursor: pointer; transition: 0.2s; position: relative; margin-bottom: 8px;
        }
        .notice-item:hover { background: rgba(98, 75, 255, 0.02); }
        .notice-item.active { background: #F8FAFC; border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        
        .ni-dot {
            width: 6px; height: 6px; border-radius: 50%; background: var(--primary-purple);
            position: absolute; left: 6px; top: 50%; transform: translateY(-50%); opacity: 0;
        }
        .notice-item.unread .ni-dot { opacity: 1; }
        
        .ni-icon {
            width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 22px;
        }
        .ni-details { flex: 1; min-width: 0; }
        .ni-header { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
        .ni-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ni-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .ni-desc { margin: 0; font-size: 13px; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
        
        .ni-meta { text-align: right; min-width: 90px; display: flex; flex-direction: column; align-items: flex-end; justify-content: flex-start; gap: 8px; }
        .ni-meta .date { font-size: 12px; font-weight: 600; color: var(--text-dark); margin: 0; }
        .ni-meta .time { font-size: 11px; color: var(--text-gray); font-weight: 500; margin: 0; }
        
        .ni-new-badge {
            background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);
            padding: 4px 16px; border-radius: 12px; font-size: 11px; font-weight: 800;
            min-width: 48px; text-align: center; display: inline-block;
        }
                    .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
      @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    
    /* Standardized Notification Dropdown CSS */
    .notification-wrapper { position: relative; }
    #notifDropdown { 
        position: absolute; 
        top: 110%; 
        right: 0; 
        width: 340px; 
        background: var(--white); 
        border-radius: 16px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
        border: 1px solid var(--border); 
        z-index: 99999; 
        overflow: hidden; 
        text-align: left;
    }


        /* Comprehensive Dark Mode Overrides for Notices Page */
        .dark-theme .kpi-card,
        .dark-theme .list-card,
        .dark-theme .detail-card,
        .dark-theme .page-btn,
        .dark-theme .btn-outline,
        .dark-theme .header-actions .icon-btn {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .notice-item:hover {
            background: rgba(255, 255, 255, 0.03) !important;
        }
        .dark-theme .notice-item.active {
            background: rgba(139, 92, 246, 0.12) !important;
            border-color: rgba(139, 92, 246, 0.3) !important;
        }
        .dark-theme select {
            background-color: var(--bg-main) !important;
            color: var(--text-dark) !important;
            border-color: var(--border) !important;
        }
        .dark-theme .tab {
            color: var(--text-gray);
        }
        .dark-theme .tab.active {
            color: var(--primary-purple) !important;
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
<body style="display: block;">
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
            <a href="electricity-record.php" class="nav-item">
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
            <a href="notices.php" class="nav-item active">
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

    <main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/notices_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/notices_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/notices_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/notices_desktop.php'; ?>
        </div>
</main>
</div>
<!-- Modal Overlay -->
<div class="modal-overlay" id="notice-modal" onclick="if(event.target===this) closeModal()">
    <div class="detail-card" onclick="event.stopPropagation()">
        <div class="modal-close" onclick="closeModal()"><i class='bx bx-x' style="font-size: 24px;"></i></div>
        <div id="detail-pane" style="display: flex; flex-direction: column; flex: 1;">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('notice-modal');
    const detailPane = document.getElementById('detail-pane');
    
    function closeModal() {
        modal.classList.remove('active');
    }
    
    function loadDetails(id) {
        detailPane.innerHTML = `<div style="padding: 40px; text-align: center; color: var(--text-gray);"><i class='bx bx-loader-alt bx-spin' style="font-size: 32px;"></i></div>`;
        modal.classList.add('active');
        fetch('notices.php?ajax_id=' + id)
            .then(res => res.text())
            .then(html => {
                detailPane.innerHTML = html;
            });
    }
    
    // Handle read state from localStorage
    const readNotices = JSON.parse(localStorage.getItem('readNotices') || '[]');

    // Add click listeners to items
    document.querySelectorAll('.notice-item').forEach(item => {
        const noticeId = item.dataset.id;
        
        // Hide badge on page load if already read
        if (readNotices.includes(noticeId)) {
            item.classList.remove('unread');
            const newBadge = item.querySelector('.ni-new-badge');
            if (newBadge) newBadge.style.display = 'none';
        }
        
        item.addEventListener('click', function() {
            document.querySelectorAll('.notice-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Mark as read visually
            this.classList.remove('unread');
            const newBadge = this.querySelector('.ni-new-badge');
            if (newBadge) newBadge.style.display = 'none';
            
            // Persist to localStorage
            if (!readNotices.includes(noticeId)) {
                readNotices.push(noticeId);
                localStorage.setItem('readNotices', JSON.stringify(readNotices));
            }
            
            // Open modal
            loadDetails(noticeId);
        });
    });
</script>
<script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('click', function(event) { const dropdown = document.getElementById('notifDropdown'); const bell = document.querySelector('.bell-icon'); if (dropdown && dropdown.style.display === 'block') { if (!dropdown.contains(event.target) && !bell.contains(event.target)) { dropdown.style.display = 'none'; } } });
</script>
</body>
</html>
