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
            $success = "Your query has been submitted successfully.";
        } else {
            $error = "Failed to submit query. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
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
    if ($st === 'pending' || $st === 'open' || $st === 'in progress') {
        $open_queries++;
        $row['ui_status'] = 'Open';
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
$stmt = mysqli_prepare($conn, "SELECT username, name, profile_pic FROM users WHERE id = ?");
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
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }

        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: hidden; }
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 230px;
            padding: 32px 40px;
            max-width: calc(100% - 230px);
            box-sizing: border-box;
            overflow-y: auto;
            min-height: 100vh;
        }
        .top-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border);
        }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .icon-btn {
            width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: var(--text-gray); cursor: pointer;
            background: var(--white); transition: all 0.2s; position: relative;
        }
        .icon-btn:hover { color: var(--primary-purple); border-color: rgba(98, 75, 255, 0.2); }
        .user-profile {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px 8px 8px; border-radius: 24px;
            border: 1px solid var(--border); background: var(--white); cursor: pointer;
        }
        
        /* KPI Grid */
        .kpi-grid-4 {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px;
        }
        .kpi-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 20px; padding: 24px; box-shadow: var(--card-shadow);
            display: flex; align-items: flex-start; gap: 16px;
        }
        .kpi-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .kpi-info h4 { margin: 0 0 8px 0; font-size: 13px; color: var(--text-gray); font-weight: 600; }
        .kpi-info h2 { margin: 0 0 12px 0; font-size: 28px; color: var(--text-dark); font-weight: 800; }
        .kpi-badge {
            display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
        }

        /* 2-Column Layout */
        .query-layout {
            display: grid; grid-template-columns: 400px minmax(0, 1fr); gap: 32px; align-items: start;
        }

        /* Form Card */
        .form-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 24px; padding: 24px 32px; box-shadow: var(--card-shadow);
        }
        .form-title { margin: 0 0 20px 0; font-size: 18px; font-weight: 800; color: var(--text-dark); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-gray); font-size: 13px; }
        .form-control {
            width: 100%; padding: 12px 16px; border-radius: 12px; border: 1.5px solid var(--border);
            background: var(--bg-main); color: var(--text-dark); font-family: inherit; font-size: 14px;
            transition: all 0.2s; box-sizing: border-box;
        }
        .form-control:focus { outline: none; border-color: var(--primary-purple); background: var(--white); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); }
        
        .upload-box {
            border: 2px dashed rgba(98, 75, 255, 0.3); border-radius: 16px; padding: 20px;
            text-align: center; background: rgba(98, 75, 255, 0.03); cursor: pointer;
            transition: all 0.2s; margin-bottom: 16px;
        }
        .upload-box:hover { background: rgba(98, 75, 255, 0.08); border-color: var(--primary-purple); }
        .upload-box i { font-size: 20px; color: var(--primary-purple); margin-bottom: 8px; }
        .upload-box h5 { margin: 0 0 4px 0; font-size: 14px; color: var(--primary-purple); font-weight: 600; }
        .upload-box p { margin: 0; font-size: 12px; color: var(--text-gray); }

        .btn-primary {
            width: 100%; background: var(--primary-purple); color: white; border: none;
            padding: 14px; border-radius: 12px; font-weight: 700; font-size: 15px;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.2s; box-shadow: 0 8px 20px rgba(98, 75, 255, 0.2);
        }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* List Card */
        .list-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 24px; padding: 32px; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column;
        }
        .list-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
        }
        .list-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark); }
        
        .query-item {
            display: flex; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border); gap: 20px;
        }
        .query-item:last-child { border-bottom: none; }
        
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
        

    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -1px; color: var(--text-dark); margin: 0 0 8px 0;">
                    Raise Query
                </h1>
                <p style="font-size: 15px; color: var(--text-gray); font-weight: 500; margin: 0;">Report an issue or request assistance. We're here to help!</p>
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
                
                <div class="user-profile">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                        <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></span>
                        <span style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Room <?php echo htmlspecialchars($_SESSION['room_no'] ?? '201'); ?></span>
                    </div>
                    <i class='bx bx-chevron-down' style="color: var(--text-gray); margin-left: 8px;"></i>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid-4">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                    <i class='bx bx-message-rounded-dots'></i>
                </div>
                <div class="kpi-info">
                    <h4>Total Queries</h4>
                    <h2><?php echo $total_queries; ?></h2>
                    <span class="kpi-badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">All time</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-time'></i>
                </div>
                <div class="kpi-info">
                    <h4>Open Queries</h4>
                    <h2><?php echo $open_queries; ?></h2>
                    <span class="kpi-badge" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Awaiting response</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-check-circle'></i>
                </div>
                <div class="kpi-info">
                    <h4>Resolved Queries</h4>
                    <h2><?php echo $resolved_queries; ?></h2>
                    <span class="kpi-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Successfully resolved</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                    <i class='bx bx-x-circle'></i>
                </div>
                <div class="kpi-info">
                    <h4>Closed Queries</h4>
                    <h2><?php echo $closed_queries; ?></h2>
                    <span class="kpi-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">Closed by you</span>
                </div>
            </div>
        </div>

        <?php if($success): ?>
            <div style="padding: 16px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div style="padding: 16px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- 2-Column Layout -->
        <div class="query-layout">
            <!-- Left: Form -->
            <div class="form-card">
                <h3 class="form-title">Submit a New Query</h3>
                <form method="POST">
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
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Describe your issue or request in detail..." required style="resize: none;"></textarea>
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

                    <button type="submit" name="submit_query" class="btn-primary">
                        <i class='bx bx-send'></i> Submit Query
                    </button>
                </form>
            </div>

            <!-- Right: List -->
            <div class="list-card">
                <div class="list-header">
                    <h3>My Queries</h3>
                    <div style="display: flex; gap: 12px;">
                        <select class="form-control" style="padding: 8px 36px 8px 16px; width: auto; font-weight: 600; font-size: 13px; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;">
                            <option>All Status</option>
                            <option>Open</option>
                            <option>Resolved</option>
                            <option>Closed</option>
                        </select>
                        <button class="btn-outline" style="width: auto; padding: 8px 16px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;">
                    <?php 
                    if(empty($queries)) {
                        echo '<div style="padding: 40px; text-align: center; color: var(--text-gray);">No queries found.</div>';
                    }
                    foreach(array_slice($queries, 0, 5) as $index => $q): 
                        // Map categories to icons and colors
                        $cat = strtolower($q['category']);
                        if (strpos($cat, 'plumbing') !== false) {
                            $icon = 'bx-water'; $bg = 'rgba(245, 158, 11, 0.1)'; $col = '#F59E0B';
                        } elseif (strpos($cat, 'elect') !== false) {
                            $icon = 'bx-bolt'; $bg = 'rgba(59, 130, 246, 0.1)'; $col = '#3B82F6';
                        } elseif (strpos($cat, 'housekeep') !== false || strpos($cat, 'clean') !== false) {
                            $icon = 'bx-brush'; $bg = 'rgba(16, 185, 129, 0.1)'; $col = '#10B981';
                        } elseif (strpos($cat, 'maintain') !== false || strpos($cat, 'maintenance') !== false) {
                            $icon = 'bx-wrench'; $bg = 'rgba(98, 75, 255, 0.1)'; $col = 'var(--primary-purple)';
                        } elseif (strpos($cat, 'general') !== false || strpos($cat, 'parking') !== false) {
                            $icon = 'bx-parking'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        } else {
                            $icon = 'bx-info-circle'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        }

                        // Map Status
                        $st = strtolower($q['ui_status']);
                        if ($st == 'open') {
                            $s_bg = 'rgba(245, 158, 11, 0.1)'; $s_col = '#F59E0B';
                        } elseif ($st == 'resolved') {
                            $s_bg = 'rgba(16, 185, 129, 0.1)'; $s_col = '#10B981';
                        } else {
                            $s_bg = 'rgba(239, 68, 68, 0.1)'; $s_col = '#EF4444'; // Closed
                        }

                        $date_formatted = date('d M Y', strtotime($q['created_at']));
                        $qid_formatted = '#QRY-' . str_pad($q['id'], 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="query-item">
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
                        <button class="qi-action"><i class='bx bx-chevron-right'></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer Pagination -->
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                    <span>Showing 1 to <?php echo min(5, $total_queries); ?> of <?php echo $total_queries; ?> queries</span>
                    <div style="display: flex; gap: 8px;">
                        <button class="icon-btn" style="width: 32px; height: 32px;"><i class='bx bx-chevron-left'></i></button>
                        <button class="icon-btn" style="width: 32px; height: 32px; background: var(--primary-purple); color: white; border-color: var(--primary-purple);">1</button>
                        <button class="icon-btn" style="width: 32px; height: 32px;">2</button>
                        <button class="icon-btn" style="width: 32px; height: 32px;"><i class='bx bx-chevron-right'></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
