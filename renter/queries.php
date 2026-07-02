<?php
// renter/queries.php - Redesigned with Unified SaaS UI
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
require_once "fetch_notifications.php";
$success = "";
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Your query has been submitted successfully.";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success = "Query deleted successfully.";
}
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
    $category = $_POST['category'] ?? 'Other';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO queries (user_id, category, subject, message) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $category, $subject, $message);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: queries.php?success=1");
            exit;
        } else {
            $error = "Failed to submit query. Please try again.";
        }
        if (isset($stmt)) mysqli_stmt_close($stmt);
    }
}

// Handle query deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int) $_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM queries WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $del_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: queries.php?deleted=1");
        exit;
    } else {
        $error = "Failed to delete query.";
    }
    if (isset($stmt)) mysqli_stmt_close($stmt);
}

// Fetch user queries
$stmt = mysqli_prepare($conn, "SELECT * FROM queries WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$queries = [];
$total_queries = 0;
$open_queries = 0;
$resolved_queries = 0;
$closed_queries = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $total_queries++;
    
    // Normalize status for UI
    $st = strtolower($row['status'] ?? 'pending');
    if ($st === 'pending' || $st === 'open') {
        $open_queries++;
        $row['ui_status'] = 'Open';
    } elseif ($st === 'in progress') {
        $open_queries++;
        $row['ui_status'] = 'In Progress';
    } elseif ($st === 'resolved' || $st === 'completed') {
        $resolved_queries++;
        $row['ui_status'] = 'Resolved';
    } elseif ($st === 'closed') {
        $closed_queries++;
        $row['ui_status'] = 'Closed';
    } else {
        $open_queries++;
        $row['ui_status'] = 'Open';
    }

    $queries[] = $row;
}
mysqli_stmt_close($stmt);

// User Profile for Header
$stmt = mysqli_prepare($conn, "SELECT username, name, profile_pic, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$usr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$display_name = $usr['name'] ?: $usr['username'];
$profile_pic = $usr['profile_pic'] ?: "assets/img/default-avatar.png";
mysqli_stmt_close($stmt);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raise Query | <?php echo HOUSE_NAME; ?></title>
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
    <link rel="manifest" href="../manifest.json">
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

        .app-container {
            display: flex;
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
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: var(--white); padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
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
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;
        }
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
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline:hover { background: rgba(98, 75, 255, 0.02); }
        .user-profile {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
            white-space: nowrap;
        }
        .user-info span:first-child { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info span:last-child { font-size: 11px; color: var(--text-gray); margin: 0; }
        
        /* KPI Grid */
        .kpi-grid-4 {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;
        }
        .kpi-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; padding: 16px 20px; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column; gap: 16px; min-height: 120px;
        }
        .kpi-card-top { display: flex; align-items: center; gap: 16px; }
        .kpi-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .kpi-info h4 { margin: 0 0 4px 0; font-size: 13px; color: #64748b; font-weight: 700; }
        .kpi-info h2 { margin: 0; font-size: 24px; color: var(--text-dark); font-weight: 800; line-height: 1; }
        .kpi-badge-wrap { display: flex; justify-content: center; width: 100%; margin-top: auto; }
        .kpi-badge {
            display: inline-flex; align-items: center; justify-content: center; padding: 6px 20px; border-radius: 20px;
            font-size: 11px; font-weight: 700; white-space: nowrap; 
        }

        /* 2-Column Layout */
        .query-layout {
            display: grid; grid-template-columns: 380px minmax(0, 1fr); gap: 24px; align-items: stretch;
        }

        /* Form Card */
        .form-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 20px 24px; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column;
        }
        .form-title { margin: 0 0 16px 0; font-size: 16px; font-weight: 800; color: var(--text-dark); }
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; margin-bottom: 4px; font-weight: 600; color: var(--text-gray); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control {
            width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid var(--border);
            background: var(--bg-main); color: var(--text-dark); font-family: inherit; font-size: 13px;
            transition: all 0.2s; box-sizing: border-box;
        }
        .form-control:focus { outline: none; border-color: var(--primary-purple); background: var(--white); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); }
        
        .upload-box {
            border: 2px dashed rgba(98, 75, 255, 0.3); border-radius: 12px; padding: 16px;
            text-align: center; background: rgba(98, 75, 255, 0.03); cursor: pointer;
            transition: all 0.2s; margin-bottom: 12px;
        }
        .upload-box:hover { background: rgba(98, 75, 255, 0.08); border-color: var(--primary-purple); }
        .upload-box i { font-size: 18px; color: var(--primary-purple); margin-bottom: 4px; }
        .upload-box h5 { margin: 0 0 2px 0; font-size: 13px; color: var(--primary-purple); font-weight: 600; }
        .upload-box p { margin: 0; font-size: 11px; color: var(--text-gray); }

        .btn-primary {
            width: 100%; background: var(--primary-purple); color: white; border: none;
            padding: 12px; border-radius: 10px; font-weight: 700; font-size: 14px;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.2s; box-shadow: 0 4px 15px rgba(98, 75, 255, 0.2); margin-top: 16px;
        }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* List Card */
        .list-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 20px 24px; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column;
        }
        .list-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;
        }
        .list-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--text-dark); }
        
        .query-row { border-bottom: 1px solid var(--border); }
        .query-row:last-child { border-bottom: none; }
        .query-item {
            display: flex; align-items: center; padding: 12px 0; gap: 16px;
        }
        
        .qi-icon {
            width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 24px;
        }
        .qi-details { flex: 1; min-width: 0; }
        .qi-details h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .qi-details .category { font-size: 12px; font-weight: 700; color: var(--text-gray); margin-bottom: 4px; display: block; }
        .qi-details p { margin: 0; font-size: 13px; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .qi-status {
            padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; text-align: center;
        }
        .qi-meta { text-align: right; margin-right: 16px; min-width: 80px; }
        .qi-meta .date { display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; white-space: nowrap; }
        .qi-meta .qid { font-size: 12px; color: var(--text-gray); font-weight: 500; }
        
        .qi-action {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; color: var(--primary-purple);
            cursor: pointer; transition: all 0.2s; background: var(--white);
        }
        .qi-action:hover { border-color: var(--primary-purple); background: rgba(98,75,255,0.05); }
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


        /* Comprehensive Dark Mode Overrides for queries.php */
        .dark-theme .kpi-card,
        .dark-theme .list-card,
        .dark-theme .detail-card,
        .dark-theme .card,
        .dark-theme .panel,
        .dark-theme .page-btn,
        .dark-theme .btn-outline,
        .dark-theme .header-actions .icon-btn {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme select, .dark-theme input, .dark-theme textarea {
            background-color: var(--bg-main) !important;
            color: var(--text-dark) !important;
            border-color: var(--border) !important;
        }
        .dark-theme tr:hover td, .dark-theme .item:hover {
            background: rgba(255, 255, 255, 0.03) !important;
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


        /* GLOBAL DESKTOP RESTRICTION - ZERO DESKTOP IMPACT */
        .mobile-only-header,
        .mobile-only-dashboard,
        .mobile-only-payments,
        .mobile-only-view,
        .mobile-bottom-nav {
            display: none !important;
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
            <a href="queries.php" class="nav-item active">
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
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/queries_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/queries_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/queries_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/queries_desktop.php'; ?>
        </div>
</main>
</div>
<script>
function toggleDetails(index) {
    const detailsDiv = document.getElementById('details-' + index);
    const btn = document.getElementById('btn-' + index);
    if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
        detailsDiv.style.display = 'block';
        btn.style.transform = 'rotate(90deg)';
        btn.style.background = 'var(--primary-purple)';
        btn.style.color = 'white';
    } else {
        detailsDiv.style.display = 'none';
        btn.style.transform = 'rotate(0deg)';
        btn.style.background = '#F1F5F9';
        btn.style.color = 'var(--text-gray)';
    }
}
// Polling for instant updates
setInterval(() => {
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // For each query row in the fetched document
            doc.querySelectorAll('.query-row').forEach(newRow => {
                const qid = newRow.getAttribute('data-id');
                const oldRow = document.querySelector(`.query-row[data-id="${qid}"]`);
                if (oldRow) {
                    // Check if status changed
                    const oldStatus = oldRow.querySelector('.qi-status');
                    const newStatus = newRow.querySelector('.qi-status');
                    if (oldStatus.innerHTML !== newStatus.innerHTML) {
                        oldStatus.innerHTML = newStatus.innerHTML;
                        oldStatus.style.background = newStatus.style.background;
                        oldStatus.style.color = newStatus.style.color;
                    }
                    
                    // Check if details (admin reply) changed
                    // The details div has an id like details-0, details-1... which might shift if list order changes, 
                    // but since we're staying on the same page, the index mapping should be consistent unless new queries are inserted.
                    // Instead, let's select the details div inside the row.
                    const oldDetails = oldRow.querySelector('div[id^="details-"]');
                    const newDetails = newRow.querySelector('div[id^="details-"]');
                    if (oldDetails && newDetails && oldDetails.innerHTML !== newDetails.innerHTML) {
                        // We only want to update the contents of the details, not its display style (which controls accordion visibility)
                        const isExpanded = oldDetails.style.display !== 'none';
                        oldDetails.innerHTML = newDetails.innerHTML;
                        // re-apply display state in case innerHTML replacement affected it (though it shouldn't)
                        oldDetails.style.display = isExpanded ? 'block' : 'none';
                    }
                }
            });
            
            // We could also check if the number of rows changed to trigger a full refresh, 
            // but for simple status updates, updating existing rows is sufficient.
        });
}, 5000); // 5 seconds

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.page-btn');
    if (btn && btn.tagName === 'A') {
        e.preventDefault();
        const url = btn.href;
        
        // Add a subtle opacity to indicate loading
        const listCard = document.querySelector('.list-card');
        listCard.style.opacity = '0.5';
        listCard.style.pointerEvents = 'none';
        
        fetch(url)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newList = doc.querySelector('.list-card').innerHTML;
                listCard.innerHTML = newList;
                
                // Restore opacity
                listCard.style.opacity = '1';
                listCard.style.pointerEvents = 'auto';
                
                // Update URL without scrolling
                window.history.pushState({path: url}, '', url);
            });
    }
});
</script>

    <script>
                currentX = e.touches[0].clientX;
                let diff = currentX - startX;
                if (diff < 0) { // Only allow swiping left
                    content.style.transform = `translateX(${diff}px)`;
                }
            }, {passive: true});
            
            item.addEventListener('touchend', e => {
                let diff = currentX - startX;
                content.style.transition = 'transform 0.2s ease-out';
                if (diff < -80) { // threshold
                    content.style.transform = `translateX(-100%)`;
                    setTimeout(() => {
                        dismissNotification(item.getAttribute('data-id'), item);
                    }, 200);
                } else {
                    content.style.transform = `translateX(0)`;
                }
            });
        });
    </script>
    <script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>

<script>
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {

    </script>
<script>
document.addEventListener('click', function(event) { const dropdown = document.getElementById('notifDropdown'); const bell = document.querySelector('.bell-icon'); if (dropdown && dropdown.style.display === 'block') { if (!dropdown.contains(event.target) && !bell.contains(event.target)) { dropdown.style.display = 'none'; } } });

</script>
</body>
</html>
