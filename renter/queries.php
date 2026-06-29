<?php
// renter/queries.php - Redesigned with Unified SaaS UI
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
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

$unread_count = 1; // Match mockup notification count
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
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
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
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .page-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .page-btn:hover:not(:disabled) { background: #f8fafc; border-color: rgba(98, 75, 255, 0.3); color: var(--primary-purple); }
        .page-btn.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
        .btn-outline {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
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
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-message-square-dots' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0;">Raise Query</h1>
                    <p style="margin: 0;">Submit a request or report an issue.</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="icon-btn">
                    <i class='bx bx-bell'></i>
                    <?php if ($unread_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-btn" onclick="document.documentElement.classList.toggle('dark-theme'); localStorage.setItem('theme', document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light');">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="#" class="btn-outline" style="width: auto; padding: 10px 20px; border-radius: 12px;"><i class='bx bx-help-circle'></i> Help & Support</a>
                <div style="position: relative;">
                    <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                        <div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">
<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>
</div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                        </div>
                        <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                    </div>
                    
                    <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                            <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                        </a>
                        <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 14px; font-weight: 500; transition: 0.2s;">
                            <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid-4">
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                        <i class='bx bx-message-rounded-dots'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Total Queries</h4>
                        <h2><?php echo $total_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">All time</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                        <i class='bx bx-time'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Open Queries</h4>
                        <h2><?php echo $open_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Awaiting response</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Resolved Queries</h4>
                        <h2><?php echo $resolved_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Successfully resolved</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                        <i class='bx bx-x-circle'></i>
                    </div>
                    <div class="kpi-info">
                        <h4>Closed Queries</h4>
                        <h2><?php echo $closed_queries; ?></h2>
                    </div>
                </div>
                <div class="kpi-badge-wrap">
                    <span class="kpi-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">Closed by you</span>
                </div>
            </div>
        </div>

        <?php if($success): ?>
            <div id="successMsgAlert" style="padding: 16px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: opacity 0.5s ease-out;">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i> <?php echo $success; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('successMsgAlert');
                    if(el) {
                        el.style.opacity = '0';
                        setTimeout(() => el.style.display = 'none', 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>
        <?php if($error): ?>
            <div id="errorMsgAlert" style="padding: 16px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: opacity 0.5s ease-out;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo $error; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('errorMsgAlert');
                    if(el) {
                        el.style.opacity = '0';
                        setTimeout(() => el.style.display = 'none', 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>

        <!-- 2-Column Layout -->
        <div class="query-layout">
            <!-- Left: Form -->
            <div class="form-card">
                <h3 class="form-title">Submit a New Query</h3>
                <form method="POST" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="form-group">
                        <label class="form-label">Query Category</label>
                        <select name="category" class="form-control" required style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px;">
                            <option value="">Select Category</option>
                            <option value="Plumbing">Plumbing</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Housekeeping">Housekeeping</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Enter a short subject" required>
                    </div>
                    <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                        <label class="form-label">Description</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Describe your issue or request in detail..." required style="resize: none; flex: 1; min-height: 80px;"></textarea>
                        <div style="text-align: right; margin-top: 8px; font-size: 11px; color: var(--text-gray); font-weight: 500;">0/500</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Upload Image (Optional)</label>
                        <div class="upload-box" onclick="document.getElementById('fileUpload').click();">
                            <i class='bx bx-upload'></i>
                            <h5>Click to upload <span style="color: var(--text-gray); font-weight: 400;">or drag and drop</span></h5>
                            <p>PNG, JPG, JPEG up to 5MB</p>
                        </div>
                        <input type="file" id="fileUpload" style="display: none;" accept="image/png, image/jpeg, image/jpg">
                    </div>

                    <button type="submit" name="submit_query" class="btn-primary" style="margin-top: auto;">
                        <i class='bx bx-send'></i> Submit Query
                    </button>
                </form>
            </div>

            <!-- Right: List -->
            <div class="list-card">
                <div class="list-header">
                    <h3>My Queries</h3>
                    <div style="display: flex; gap: 12px;">
                        <?php $current_filter = $_GET['status'] ?? 'All Status'; ?>
                        <select class="form-control" onchange="window.location.href='?status=' + this.value;" style="padding: 8px 36px 8px 16px; width: auto; font-weight: 600; font-size: 13px; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;">
                            <option value="All Status" <?php echo $current_filter === 'All Status' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Open" <?php echo $current_filter === 'Open' ? 'selected' : ''; ?>>Open</option>
                            <option value="Resolved" <?php echo $current_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Closed" <?php echo $current_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button class="btn-outline" style="width: auto; padding: 8px 16px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;">
                    <?php 
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = 5;
                    
                    // Filter logic
                    $filter_status = $_GET['status'] ?? 'All Status';
                    $filtered_queries = [];
                    foreach ($queries as $q) {
                        if ($filter_status === 'All Status' || $q['ui_status'] === $filter_status) {
                            $filtered_queries[] = $q;
                        }
                    }
                    
                    $total_filtered = count($filtered_queries);
                    $total_pages = $total_filtered > 0 ? ceil($total_filtered / $limit) : 1;
                    if ($page > $total_pages) $page = $total_pages;
                    $offset = ($page - 1) * $limit;
                    
                    if(empty($filtered_queries)) {
                        echo '<div style="padding: 40px; text-align: center; color: var(--text-gray);">No queries found for this status.</div>';
                    }
                    $paginated_queries = array_slice($filtered_queries, $offset, $limit);
                    foreach($paginated_queries as $index => $q): 
                        // Map categories to icons and colors
                        $cat = strtolower($q['category']);
                        if (strpos($cat, 'plumbing') !== false) {
                            $icon = 'bx-water'; $bg = 'rgba(245, 158, 11, 0.1)'; $col = '#F59E0B';
                        } elseif (strpos($cat, 'elect') !== false) {
                            $icon = 'bx-bolt-circle'; $bg = 'rgba(59, 130, 246, 0.1)'; $col = '#3B82F6';
                        } elseif (strpos($cat, 'housekeep') !== false || strpos($cat, 'clean') !== false) {
                            $icon = 'bx-brush'; $bg = 'rgba(16, 185, 129, 0.1)'; $col = '#10B981';
                        } elseif (strpos($cat, 'maintain') !== false || strpos($cat, 'maintenance') !== false) {
                            $icon = 'bx-wrench'; $bg = 'rgba(98, 75, 255, 0.1)'; $col = 'var(--primary-purple)';
                        } elseif (strpos($cat, 'parking') !== false) {
                            $icon = 'bx-car'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        } elseif (strpos($cat, 'general') !== false) {
                            $icon = 'bx-category'; $bg = 'rgba(139, 92, 246, 0.1)'; $col = '#8B5CF6';
                        } else {
                            $icon = 'bx-info-circle'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        }

                        // Map Status
                        $st = strtolower($q['ui_status']);
                        if ($st == 'open') {
                            $s_bg = 'rgba(245, 158, 11, 0.1)'; $s_col = '#F59E0B';
                        } elseif ($st == 'in progress') {
                            $s_bg = 'rgba(59, 130, 246, 0.1)'; $s_col = '#3B82F6';
                        } elseif ($st == 'resolved') {
                            $s_bg = 'rgba(16, 185, 129, 0.1)'; $s_col = '#10B981';
                        } else {
                            $s_bg = 'rgba(239, 68, 68, 0.1)'; $s_col = '#EF4444'; // Closed
                        }

                        $date_formatted = date('d M Y', strtotime($q['created_at']));
                        $qid_formatted = '#QRY-' . str_pad($q['id'], 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="query-row" data-id="<?php echo $q['id']; ?>">
                        <div class="query-item" onclick="toggleDetails(<?php echo $index; ?>)" style="cursor: pointer;">
                            <div class="qi-icon" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                                <i class='bx <?php echo $icon; ?>'></i>
                            </div>
                            <div class="qi-details">
                                <h4><?php echo htmlspecialchars($q['subject']); ?></h4>
                                <span class="category"><?php echo htmlspecialchars($q['category']); ?></span>
                                <p><?php echo htmlspecialchars($q['message']); ?></p>
                            </div>
                            <div class="qi-status" style="background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>;">
                                <?php echo htmlspecialchars($q['ui_status']); ?>
                            </div>
                            <div class="qi-meta">
                                <span class="date"><?php echo $date_formatted; ?></span>
                                <span class="qid"><?php echo $qid_formatted; ?></span>
                            </div>
                            <button class="qi-action" id="btn-<?php echo $index; ?>" style="transition: transform 0.3s;"><i class='bx bx-chevron-right'></i></button>
                        </div>
                        <div id="details-<?php echo $index; ?>" style="display: none; padding: 0 0 20px 64px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
                                <p style="font-size: 14px; color: var(--text-dark); margin: 0; line-height: 1.6;"><strong>Full Message:</strong><br><span style="color: var(--text-gray); font-size: 13px;"><?php echo nl2br(htmlspecialchars($q['message'])); ?></span></p>
                                <a href="?action=delete&id=<?php echo $q['id']; ?>" onclick="return confirm('Are you sure you want to delete this query?');" style="color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; flex-shrink: 0; background: rgba(239, 68, 68, 0.05);"><i class='bx bx-trash'></i> Delete</a>
                            </div>
                            <?php if(!empty($q['admin_remark'])): ?>
                                <div style="padding: 16px; background: rgba(98, 75, 255, 0.05); border-left: 4px solid var(--primary-purple); border-radius: 8px;">
                                    <p style="font-size: 13px; color: var(--primary-purple); margin: 0; line-height: 1.5;"><strong>Admin Reply:</strong><br><?php echo nl2br(htmlspecialchars($q['admin_remark'])); ?></p>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 13px; color: var(--text-gray); margin: 0; font-style: italic;">No response from admin yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer Pagination -->
                <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                    <?php 
                    $start_idx = $total_filtered > 0 ? $offset + 1 : 0;
                    $end_idx = min($offset + $limit, $total_filtered);
                    ?>
                    <span>Showing <?php echo $start_idx; ?> to <?php echo $end_idx; ?> of <?php echo $total_filtered; ?> queries</span>
                    <div style="display: flex; gap: 8px;">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                        <?php else: ?>
                            <button class="page-btn" style="opacity: 0.5; cursor: not-allowed;" disabled><i class='bx bx-chevron-left'></i></button>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <button class="page-btn active"><?php echo $i; ?></button>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page-btn"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                        <?php else: ?>
                            <button class="page-btn" style="opacity: 0.5; cursor: not-allowed;" disabled><i class='bx bx-chevron-right'></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
</body>
</html>
