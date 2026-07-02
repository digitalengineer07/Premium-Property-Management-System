<?php
// renter/documents.php - Redesigned with Unified SaaS UI
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


// Handle Aadhar Upload
$upload_msg = "";
$upload_err = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['aadhar_file'])) {
    $file = $_FILES['aadhar_file'];
    $is_ajax = isset($_POST['ajax_upload']);
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/documents/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $new_name = 'aadhar_' . $user_id . '_' . time() . '.' . $ext;
            $db_path = 'uploads/documents/' . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET aadhaar_file = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $db_path, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $upload_msg = "Identity Proof uploaded successfully!";
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'msg' => $upload_msg, 'url' => '../' . $db_path]);
                    exit;
                }
            } else {
                $upload_err = "Failed to save the file.";
            }
        } else {
            $upload_err = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
        }
    } else {
        $upload_err = "An error occurred during upload.";
    }
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $upload_err]);
        exit;
    }
}

// Fetch User Documents
$stmt = mysqli_prepare($conn, "SELECT aadhaar_file, agreement_document, agreement_upload_date, electricity_document, electricity_upload_date FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_docs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$documents = [];

if (!empty($user_docs['aadhaar_file'])) {
    $aadhaar_url = (strpos($user_docs['aadhaar_file'], 'uploads/') === 0) ? '../' . $user_docs['aadhaar_file'] : '../uploads/aadhaar/' . $user_docs['aadhaar_file'];
    $documents[] = [
        'name' => 'Aadhar Card', 'desc' => 'Identity Proof', 'category' => 'Identity', 'cat_color' => '#3B82F6', 'cat_bg' => 'rgba(59, 130, 246, 0.1)',
        'date' => 'Uploaded', 'time' => '', 'status' => 'Verified', 'size' => 'Available', 'icon' => 'bx-id-card', 'url' => $aadhaar_url
    ];
} else {
    $documents[] = [
        'name' => 'Aadhar Card', 'desc' => 'Identity Proof', 'category' => 'Identity', 'cat_color' => '#3B82F6', 'cat_bg' => 'rgba(59, 130, 246, 0.1)',
        'date' => '-', 'time' => '-', 'status' => 'Pending', 'size' => '-', 'icon' => 'bx-id-card', 'url' => ''
    ];
}

if (!empty($user_docs['agreement_document'])) {
    $date_str = date('d M Y', strtotime($user_docs['agreement_upload_date'] ?? 'now'));
    $agree_url = (strpos($user_docs['agreement_document'], 'uploads/') === 0) ? '../' . $user_docs['agreement_document'] : '../uploads/agreements/' . $user_docs['agreement_document'];
    $documents[] = [
        'name' => 'Agreement Copy', 'desc' => 'Rental Agreement', 'category' => 'Agreement', 'cat_color' => '#8B5CF6', 'cat_bg' => 'rgba(139, 92, 246, 0.1)',
        'date' => $date_str, 'time' => '', 'status' => 'Verified', 'size' => 'Available', 'icon' => 'bx-file', 'url' => $agree_url
    ];
} else {
    $documents[] = [
        'name' => 'Agreement Copy', 'desc' => 'Rental Agreement', 'category' => 'Agreement', 'cat_color' => '#8B5CF6', 'cat_bg' => 'rgba(139, 92, 246, 0.1)',
        'date' => '-', 'time' => '-', 'status' => 'Pending', 'size' => '-', 'icon' => 'bx-file', 'url' => ''
    ];
}

if (!empty($user_docs['electricity_document'])) {
    $date_str = date('d M Y', strtotime($user_docs['electricity_upload_date'] ?? 'now'));
    $elec_url = (strpos($user_docs['electricity_document'], 'uploads/') === 0) ? '../' . $user_docs['electricity_document'] : '../uploads/documents/' . $user_docs['electricity_document'];
    $documents[] = [
        'name' => 'Electricity Copy', 'desc' => 'Utility Document', 'category' => 'Utility', 'cat_color' => '#10B981', 'cat_bg' => 'rgba(16, 185, 129, 0.1)',
        'date' => $date_str, 'time' => '', 'status' => 'Verified', 'size' => 'Available', 'icon' => 'bx-bolt-circle', 'url' => $elec_url
    ];
} else {
    $documents[] = [
        'name' => 'Electricity Copy', 'desc' => 'Utility Document', 'category' => 'Utility', 'cat_color' => '#10B981', 'cat_bg' => 'rgba(16, 185, 129, 0.1)',
        'date' => '-', 'time' => '-', 'status' => 'Pending', 'size' => '-', 'icon' => 'bx-bolt-circle', 'url' => ''
    ];
}

$verified_count = (!empty($user_docs['aadhaar_file']) ? 1 : 0) + (!empty($user_docs['agreement_document']) ? 1 : 0) + (!empty($user_docs['electricity_document']) ? 1 : 0);
$pending_count = 3 - $verified_count;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Documents | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <script>
        window.HOUSE_NAME = <?php echo json_encode(HOUSE_NAME ?? 'Premium Renter'); ?>;
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
        
        .btn-outline {
            border: 1px solid rgba(98, 75, 255, 0.15); background: var(--white); color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap; cursor: pointer;
        }
        .btn-outline:hover { background: rgba(98, 75, 255, 0.02); }

        /* KPI Grid */
        .kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 32px 20px; box-shadow: var(--card-shadow); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; transition: 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .kpi-icon { width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 30px; flex-shrink: 0; }
        .kpi-info h4 { margin: 0 0 8px 0; font-size: 14px; color: var(--text-gray); font-weight: 600; }
        .kpi-info h2 { margin: 0; font-size: 32px; color: var(--text-dark); font-weight: 800; line-height: 1; }
        .kpi-subtext { margin: 0; font-size: 12px; font-weight: 600; color: var(--text-gray); text-align: center; }

        /* Documents Layout */
        .docs-layout {
            display: grid; grid-template-columns: 1fr; gap: 24px; align-items: start;
        }

        .list-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 0; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column; overflow: hidden;
        }

        .side-widget {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 24px; box-shadow: var(--card-shadow);
            margin-bottom: 24px;
        }

        /* Upload Area */
        .upload-area {
            border: 2px dashed rgba(98, 75, 255, 0.2); border-radius: 16px; padding: 32px 20px; text-align: center;
            background: rgba(98, 75, 255, 0.02); transition: 0.2s; cursor: pointer;
        }
        .upload-area:hover { background: rgba(98, 75, 255, 0.05); border-color: var(--primary-purple); }
        .upload-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 16px; }

        .btn-primary { background: var(--primary-purple); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; width: 100%; transition: 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(98,75,255,0.3); }

        /* Table Styles */
        .docs-table { width: 100%; border-collapse: collapse; }
        .docs-table th { text-align: left; padding: 16px 24px; font-size: 12px; color: var(--text-gray); font-weight: 600; border-bottom: 1px solid var(--border); }
        .docs-table td { padding: 16px 24px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .docs-table tr:last-child td { border-bottom: none; }
        .docs-table tr:hover td { background: rgba(0,0,0,0.01); }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .status-Verified { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .status-Pending { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .status-Rejected { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); display: inline-flex; align-items: center; justify-content: center; color: var(--primary-purple); cursor: pointer; background: var(--white); transition: 0.2s; margin-right: 4px; }
        .action-btn:hover { background: rgba(98, 75, 255, 0.05); border-color: var(--primary-purple); }

        .cat-list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .cat-list-item:last-child { border-bottom: none; padding-bottom: 0; }
        .cat-left { display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); }
        .cat-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .cat-right { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--text-gray); }

        .tips-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
        .tips-list li { display: flex; align-items: start; gap: 10px; font-size: 13px; color: var(--text-dark); line-height: 1.5; font-weight: 500; }
        .tips-list li i { color: #10B981; font-size: 16px; margin-top: 2px; }
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


        /* Comprehensive Dark Mode Overrides for documents.php */
        .dark-theme .kpi-card,
        .dark-theme .list-card,
        .dark-theme .detail-card,
        .dark-theme .card,
        .dark-theme .panel,
        .dark-theme .page-btn,
        .dark-theme .btn-outline,
        .dark-theme .header-actions .icon-btn,
        .dark-theme .side-widget {
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


        .dark-theme .side-widget {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .tips-list li {
            color: var(--text-dark) !important;
        }
        .dark-theme .upload-area {
            background: rgba(255, 255, 255, 0.02) !important;
            border-color: var(--border) !important;
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

<div class="app-container" style="width: 100%;">
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
            <a href="notices.php" class="nav-item">
                <i class='bx bx-bell'></i>
                <span>Notices</span>
            </a>
            <a href="documents.php" class="nav-item active">
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
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/documents_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/documents_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/documents_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/documents_desktop.php'; ?>
        </div>
</main>
</div>

<script>
    function handleAjaxUpload(input) {
        if(!input.files.length) return;
        
        let formData = new FormData();
        formData.append('aadhar_file', input.files[0]);
        formData.append('ajax_upload', '1');
        
        let btn = document.getElementById('choose-file-btn');
        let originalText = btn.innerHTML;
        btn.innerHTML = "<i class='bx bx-loader bx-spin' style='margin-right: 8px;'></i> Uploading...";
        btn.disabled = true;
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Update Widget UI Seamlessly
                document.getElementById('upload-widget-container').innerHTML = `
                    <div class="side-widget" style="text-align: center; padding: 60px 24px; animation: fadeIn 0.5s;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: #10B981; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 24px;"><i class='bx bx-check-shield'></i></div>
                        <h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 800; color: var(--text-dark);">Identity Verified</h3>
                        <p style="margin: 0; font-size: 14px; font-weight: 500; color: var(--text-gray); line-height: 1.6;">Your Aadhar Card has been securely uploaded and verified.<br>You cannot overwrite a verified document.</p>
                    </div>
                `;
                
                // Update KPI Cards
                let kpiH2s = document.querySelectorAll('.kpi-card h2');
                if(kpiH2s.length >= 3) {
                    kpiH2s[1].innerText = parseInt(kpiH2s[1].innerText) + 1; // Verified
                    kpiH2s[2].innerText = parseInt(kpiH2s[2].innerText) - 1; // Pending
                }
                
                // Update Table Row
                let tableRows = document.querySelectorAll('.docs-table tbody tr');
                for (let tr of tableRows) {
                    if(tr.innerText.includes('Aadhar Card')) {
                        let cells = tr.querySelectorAll('td');
                        if (cells.length >= 6) {
                            cells[2].innerHTML = `<div style="font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 2px;">Uploaded</div><div style="font-size: 11px; font-weight: 500; color: var(--text-gray);"></div>`;
                            cells[3].innerHTML = `<span class="status-badge status-Verified"><i class='bx bx-check-circle'></i> Verified</span>`;
                            cells[4].innerHTML = `<span style="font-size: 12px; font-weight: 600; color: var(--text-dark);">Available</span>`;
                            cells[5].innerHTML = `
                                <div style="display: flex;">
                                    <a href="${data.url}" target="_blank" class="action-btn" style="text-decoration: none;" title="View"><i class='bx bx-show'></i></a>
                                    <a href="${data.url}" download class="action-btn" style="text-decoration: none;" title="Download"><i class='bx bx-download'></i></a>
                                </div>
                            `;
                            tr.style.animation = "fadeIn 1s";
                        }
                    }
                }
                
                // Add simple fade in animation to page
                let style = document.createElement('style');
                style.innerHTML = '@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }';
                document.head.appendChild(style);
                
            } else {
                alert("Upload Error: " + data.msg);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert("Upload failed due to network error.");
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>

<script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#upload-widget-container') {
        setTimeout(function() {
            const el = document.getElementById('upload-widget-container');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.transition = 'box-shadow 0.5s ease, transform 0.5s ease';
                el.style.boxShadow = '0 0 0 4px rgba(98, 75, 255, 0.4), 0 10px 25px rgba(98, 75, 255, 0.15)';
                el.style.borderRadius = '20px';
                setTimeout(() => { 
                    el.style.boxShadow = 'none'; 
                }, 2500);
            }
        }, 300);
    }
});
</script>
</body>
</html>
