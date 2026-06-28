<?php
// renter/notices.php - Redesigned with Unified SaaS UI
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// User Profile for Header
$stmt = mysqli_prepare($conn, "SELECT username, name, profile_pic FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$usr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$display_name = $usr['name'] ?: $usr['username'];
$profile_pic = $usr['profile_pic'] ?: "assets/img/default-avatar.png";
mysqli_stmt_close($stmt);

$unread_count = 2;

// Mock Data for Design Purposes
$notices = [
    [
        'id' => 1,
        'title' => 'Maintenance Work in Building',
        'category' => 'Important',
        'icon' => 'bx-megaphone',
        'icon_bg' => 'rgba(98, 75, 255, 0.1)',
        'icon_color' => 'var(--primary-purple)',
        'badge_bg' => 'rgba(239, 68, 68, 0.1)',
        'badge_color' => '#EF4444',
        'desc' => 'Scheduled maintenance work will be carried out in the building.',
        'full_desc' => "Dear Residents,\n\nPlease be informed that scheduled maintenance work will be carried out in the building from 25th May 2026 to 27th May 2026.\n\nDuring this time, you may experience minor inconveniences. We apologize for any trouble caused.\n\nThank you for your cooperation.\n\n- Madhav Kunj Management",
        'date' => '24 May 2026',
        'time' => '10:30 AM',
        'is_new' => true
    ],
    [
        'id' => 2,
        'title' => 'Power Backup Maintenance',
        'category' => 'Maintenance',
        'icon' => 'bx-bolt',
        'icon_bg' => 'rgba(245, 158, 11, 0.1)',
        'icon_color' => '#F59E0B',
        'badge_bg' => 'rgba(245, 158, 11, 0.1)',
        'badge_color' => '#F59E0B',
        'desc' => 'Power backup system maintenance on 26th May.',
        'full_desc' => 'There will be a brief disruption to the power backup system...',
        'date' => '23 May 2026',
        'time' => '05:45 PM',
        'is_new' => true
    ],
    [
        'id' => 3,
        'title' => 'Society Meeting Notice',
        'category' => 'General',
        'icon' => 'bx-group',
        'icon_bg' => 'rgba(16, 185, 129, 0.1)',
        'icon_color' => '#10B981',
        'badge_bg' => 'rgba(16, 185, 129, 0.1)',
        'badge_color' => '#10B981',
        'desc' => 'Monthly society meeting will be held on 28th May.',
        'full_desc' => 'Join us for the monthly meeting in the clubhouse...',
        'date' => '22 May 2026',
        'time' => '11:00 AM',
        'is_new' => false
    ],
    [
        'id' => 4,
        'title' => 'Water Supply Interruption',
        'category' => 'Maintenance',
        'icon' => 'bx-water',
        'icon_bg' => 'rgba(139, 92, 246, 0.1)',
        'icon_color' => '#8B5CF6',
        'badge_bg' => 'rgba(245, 158, 11, 0.1)',
        'badge_color' => '#F59E0B',
        'desc' => 'Water supply will be interrupted on 25th May.',
        'full_desc' => 'Water supply will be off between 2 PM and 4 PM...',
        'date' => '21 May 2026',
        'time' => '09:15 AM',
        'is_new' => false
    ],
    [
        'id' => 5,
        'title' => 'Community Event - Summer Fiesta',
        'category' => 'Events',
        'icon' => 'bx-gift',
        'icon_bg' => 'rgba(59, 130, 246, 0.1)',
        'icon_color' => '#3B82F6',
        'badge_bg' => 'rgba(59, 130, 246, 0.1)',
        'badge_color' => '#3B82F6',
        'desc' => 'Join us for the Summer Fiesta on 30th May!',
        'full_desc' => 'Fun for the whole family...',
        'date' => '20 May 2026',
        'time' => '04:30 PM',
        'is_new' => false
    ],
    [
        'id' => 6,
        'title' => 'Garbage Collection Schedule Change',
        'category' => 'General',
        'icon' => 'bx-trash',
        'icon_bg' => 'rgba(245, 158, 11, 0.1)',
        'icon_color' => '#F59E0B',
        'badge_bg' => 'rgba(16, 185, 129, 0.1)',
        'badge_color' => '#10B981',
        'desc' => 'Garbage collection timing has been changed.',
        'full_desc' => 'New timings are 8 AM daily...',
        'date' => '19 May 2026',
        'time' => '08:00 AM',
        'is_new' => false
    ],
    [
        'id' => 7,
        'title' => 'Parking Rules Reminder',
        'category' => 'Important',
        'icon' => 'bx-parking',
        'icon_bg' => 'rgba(239, 68, 68, 0.1)',
        'icon_color' => '#EF4444',
        'badge_bg' => 'rgba(239, 68, 68, 0.1)',
        'badge_color' => '#EF4444',
        'desc' => 'Please follow the parking rules for everyone\'s convenience.',
        'full_desc' => 'Do not park in visitor slots...',
        'date' => '18 May 2026',
        'time' => '02:20 PM',
        'is_new' => false
    ]
];

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
    
    <div style="margin-top: 32px; background: rgba(98, 75, 255, 0.04); border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(98, 75, 255, 0.1);">
        <div style="display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; color: var(--text-dark);">
            <i class='bx bx-bell' style="color: var(--primary-purple); font-size: 20px;"></i>
            Don't miss any important updates!
        </div>
        <button class="btn-outline" style="margin: 0;"><i class='bx bx-x'></i></button>
    </div>
    <div style="margin-top: 12px; text-align: center;">
        <button class="btn-outline" style="border-color: var(--primary-purple); background: white;">Enable Notifications</button>
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
        }

        .app-container { display: flex; min-height: 100vh; }

        /* Sidebar Styles (Matching queries.php) */
        .sidebar {
            width: 230px; background: var(--sidebar-bg); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; padding: 24px 20px; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100;
        }
        .sidebar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding: 0 10px; }
        .sidebar-logo {
            width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6);
            display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; box-shadow: 0 4px 12px rgba(98,75,255,0.3);
        }
        .sidebar-brand h2 { margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .sidebar-brand p { margin: 0; font-size: 11px; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s;
        }
        .nav-item i { font-size: 20px; transition: all 0.2s; }
        .nav-item:hover { background: rgba(98, 75, 255, 0.05); color: var(--primary-purple); }
        .nav-item:hover i { transform: scale(1.1); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2); }
        
        .main-content {
            flex: 1; margin-left: 230px; padding: 32px 40px; max-width: calc(100% - 230px); box-sizing: border-box; overflow-y: auto; min-height: 100vh;
        }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        
        .page-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .page-btn:hover:not(:disabled) { background: #f8fafc; border-color: rgba(98, 75, 255, 0.3); color: var(--primary-purple); }
        .page-btn.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
        
        .btn-outline {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
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

        .tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--border); overflow-x: auto; flex: 1; }
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
            padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 800;
        }

    </style>
</head>
<body>
<div class="app-container">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class='bx bx-home-heart'></i></div>
            <div class="sidebar-brand">
                <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
                <p>Resident Dashboard</p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class='bx bx-grid-alt'></i><span>Dashboard</span></a>
            <a href="my-payments.php" class="nav-item"><i class='bx bx-wallet'></i><span>My Payments</span></a>
            <a href="electricity-record.php" class="nav-item"><i class='bx bx-bolt-circle'></i><span>Electricity Record</span></a>
            <a href="my-bills.php" class="nav-item"><i class='bx bx-receipt'></i><span>My Bills</span></a>
            <a href="queries.php" class="nav-item"><i class='bx bx-message-square-dots'></i><span>Raise Query</span></a>
            <a href="notices.php" class="nav-item active"><i class='bx bx-bell'></i><span>Notices</span></a>
            <a href="documents.php" class="nav-item"><i class='bx bx-folder'></i><span>Documents</span></a>
            <a href="profile.php" class="nav-item"><i class='bx bx-user-circle'></i><span>Profile Settings</span></a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;"><i class='bx bx-log-out'></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -1px; margin: 0 0 8px 0; color: var(--text-dark);">Notices</h1>
                <p style="font-size: 15px; color: var(--text-gray); font-weight: 500; margin: 0;">Stay informed about important updates and announcements.</p>
            </div>
            
            <div class="header-actions">
                <div class="icon-btn" style="width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; color: var(--text-dark); font-size: 20px;">
                    <i class='bx bx-bell'></i>
                    <?php if ($unread_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-btn" onclick="document.documentElement.classList.toggle('dark-theme');" style="width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-dark); font-size: 20px;">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="#" class="btn-outline" style="border-radius: 20px;"><i class='bx bx-help-circle'></i> Help & Support</a>
                
                <div style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-left: 8px;">
                    <div style="width: 38px; height: 38px; border-radius: 12px; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                        <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></span>
                        <span style="font-size: 11px; color: var(--text-gray);">Room <?php echo htmlspecialchars($_SESSION['room_no'] ?? '201'); ?></span>
                    </div>
                    <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid-4">
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                        <i class='bx bx-megaphone'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Total Notices</h4>
                        <h2>24</h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">All time</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                        <i class='bx bx-file'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>New Notices</h4>
                        <h2>5</h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Unread</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                        <i class='bx bx-calendar'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>This Month</h4>
                        <h2>6</h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">May 2026</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                        <i class='bx bx-map-pin'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Important Notices</h4>
                        <h2>7</h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">High Priority</span>
                </div>
            </div>
        </div>

        <!-- 2-Column Layout -->
        <div class="notice-layout">
            <!-- Left: List -->
            <div class="list-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div class="tabs">
                        <div class="tab active">All Notices</div>
                        <div class="tab">Important</div>
                        <div class="tab">General</div>
                        <div class="tab">Maintenance</div>
                        <div class="tab">Events</div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-left: 16px;">
                        <select style="padding: 8px 32px 8px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 10px center; background-size: 14px;">
                            <option>All Categories</option>
                        </select>
                        <button class="btn-outline" style="padding: 8px 16px; border-radius: 8px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;" id="notice-list-container">
                    <?php foreach($notices as $i => $n): ?>
                    <div class="notice-item <?php echo $i===0 ? 'active' : ''; ?> <?php echo $n['is_new'] ? 'unread' : ''; ?>" data-id="<?php echo $n['id']; ?>">
                        <div class="ni-dot"></div>
                        <div class="ni-icon" style="background: <?php echo $n['icon_bg']; ?>; color: <?php echo $n['icon_color']; ?>;">
                            <i class='bx <?php echo $n['icon']; ?>'></i>
                        </div>
                        <div class="ni-details">
                            <div class="ni-header">
                                <h4><?php echo htmlspecialchars($n['title']); ?></h4>
                                <span class="ni-badge" style="background: <?php echo $n['badge_bg']; ?>; color: <?php echo $n['badge_color']; ?>;">
                                    <?php echo htmlspecialchars($n['category']); ?>
                                </span>
                            </div>
                            <p class="ni-desc"><?php echo htmlspecialchars($n['desc']); ?></p>
                        </div>
                        <div class="ni-meta">
                            <p class="date"><?php echo htmlspecialchars($n['date']); ?></p>
                            <p class="time"><?php echo htmlspecialchars($n['time']); ?></p>
                            <?php if($n['is_new']): ?>
                            <div style="margin-top: auto;">
                                <span class="ni-new-badge">New</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer Pagination -->
                <div style="margin-top: auto; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                    <span>Showing 1 to 7 of 24 notices</span>
                    <div style="display: flex; gap: 8px;">
                        <button class="page-btn"><i class='bx bx-chevron-left'></i></button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn"><i class='bx bx-chevron-right'></i></button>
                    </div>
                </div>
            </div>
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
        detailPane.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-gray);"><i class=\'bx bx-loader-alt bx-spin\' style="font-size: 32px;"></i></div>';
        modal.classList.add('active');
        fetch('notices.php?ajax_id=' + id)
            .then(res => res.text())
            .then(html => {
                detailPane.innerHTML = html;
            });
    }
    
    // Add click listeners to items
    document.querySelectorAll('.notice-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.notice-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Mark as read visually
            this.classList.remove('unread');
            const newBadge = this.querySelector('.ni-new-badge');
            if (newBadge) newBadge.style.display = 'none';
            
            loadDetails(this.dataset.id);
        });
    });
</script>
</body>
</html>
