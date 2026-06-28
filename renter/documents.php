<?php
// renter/documents.php - Redesigned with Unified SaaS UI
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

// Mock Data for Design
$documents = [
    [
        'name' => 'Aadhar Card', 'desc' => 'Identity Proof', 'category' => 'Identity', 'cat_color' => '#3B82F6', 'cat_bg' => 'rgba(59, 130, 246, 0.1)',
        'date' => '12 May 2026', 'time' => '10:30 AM', 'status' => 'Verified', 'size' => '1.2 MB', 'icon' => 'bx-id-card'
    ],
    [
        'name' => 'Agreement Copy', 'desc' => 'Rental Agreement', 'category' => 'Agreement', 'cat_color' => '#8B5CF6', 'cat_bg' => 'rgba(139, 92, 246, 0.1)',
        'date' => '05 May 2026', 'time' => '04:15 PM', 'status' => 'Verified', 'size' => '2.4 MB', 'icon' => 'bx-file'
    ]
];
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
        }

        .app-container { display: flex; min-height: 100vh; }

        /* Sidebar Styles */
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
        
        .btn-outline {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap; cursor: pointer;
        }
        .btn-outline:hover { background: rgba(98, 75, 255, 0.02); }

        /* KPI Grid */
        .kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 20px; }
        .kpi-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
        .kpi-info h4 { margin: 0 0 6px 0; font-size: 13px; color: var(--text-gray); font-weight: 600; }
        .kpi-info h2 { margin: 0 0 4px 0; font-size: 28px; color: var(--text-dark); font-weight: 800; line-height: 1; }
        .kpi-info p { margin: 0; font-size: 11px; font-weight: 600; color: var(--text-gray); }

        /* Documents Layout */
        .docs-layout {
            display: grid; grid-template-columns: minmax(0, 1fr) 350px; gap: 24px; align-items: start;
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

        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); display: inline-flex; align-items: center; justify-content: center; color: var(--primary-purple); cursor: pointer; background: white; transition: 0.2s; margin-right: 4px; }
        .action-btn:hover { background: rgba(98, 75, 255, 0.05); border-color: var(--primary-purple); }

        .cat-list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .cat-list-item:last-child { border-bottom: none; padding-bottom: 0; }
        .cat-left { display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); }
        .cat-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .cat-right { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--text-gray); }

        .tips-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
        .tips-list li { display: flex; align-items: start; gap: 10px; font-size: 13px; color: var(--text-dark); line-height: 1.5; font-weight: 500; }
        .tips-list li i { color: #10B981; font-size: 16px; margin-top: 2px; }
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
            <a href="notices.php" class="nav-item"><i class='bx bx-bell'></i><span>Notices</span></a>
            <a href="documents.php" class="nav-item active"><i class='bx bx-folder'></i><span>Documents</span></a>
            <a href="profile.php" class="nav-item"><i class='bx bx-user-circle'></i><span>Profile Settings</span></a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;"><i class='bx bx-log-out'></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div>
                <h1 style="font-size: 28px; font-weight: 800; letter-spacing: -1px; margin: 0 0 4px 0; color: var(--text-dark);">
                    Documents
                </h1>
                <p style="font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;">
                    Manage and access all your important documents in one place.
                </p>
            </div>
            
            <div class="header-actions">
                <div style="position: relative; cursor: pointer;">
                    <i class='bx bx-bell' style="font-size: 24px; color: var(--text-gray);"></i>
                    <span style="position: absolute; top: -2px; right: -2px; background: #EF4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; border: 2px solid var(--bg-main);">2</span>
                </div>
                <div style="cursor: pointer;" onclick="document.documentElement.classList.toggle('dark-theme'); localStorage.setItem('theme', document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light');">
                    <i class='bx bx-moon' style="font-size: 24px; color: var(--text-gray);"></i>
                </div>
                <a href="#" class="btn-outline"><i class='bx bx-help-circle'></i> Help & Support</a>
                
                <div style="display: flex; align-items: center; gap: 12px; margin-left: 8px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                        <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></div>
                        <div style="font-size: 12px; font-weight: 500; color: var(--text-gray);">Room <?php echo htmlspecialchars($_SESSION['room_no'] ?? '201'); ?> <i class='bx bx-chevron-down'></i></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid-4">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                    <i class='bx bx-folder'></i>
                </div>
                <div class="kpi-info">
                    <h4>Total Documents</h4>
                    <h2>18</h2>
                    <p>All documents</p>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-check-shield'></i>
                </div>
                <div class="kpi-info">
                    <h4>Verified Documents</h4>
                    <h2>12</h2>
                    <p>Approved & verified</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-time'></i>
                </div>
                <div class="kpi-info">
                    <h4>Pending Documents</h4>
                    <h2>3</h2>
                    <p>Awaiting verification</p>
                </div>
            </div>

            <div class="kpi-card" style="align-items: start;">
                <div class="kpi-icon" style="background: rgba(14, 165, 233, 0.1); color: #0EA5E9;">
                    <i class='bx bx-cloud-upload'></i>
                </div>
                <div class="kpi-info" style="flex: 1; width: 100%;">
                    <h4>Storage Used</h4>
                    <h2>245 MB</h2>
                    <p style="margin-bottom: 12px;">of 1 GB used</p>
                    
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="flex: 1; height: 6px; background: rgba(98, 75, 255, 0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: 24%; background: var(--primary-purple); border-radius: 4px;"></div>
                        </div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-dark);">24%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Layout -->
        <div class="docs-layout">
            <!-- Left: List -->
            <div class="list-card">
                <!-- Filters -->
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 16px;">
                    <select style="padding: 10px 32px 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 12px center; background-size: 14px;">
                        <option>All Categories</option>
                    </select>
                    <select style="padding: 10px 32px 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 12px center; background-size: 14px;">
                        <option>All Status</option>
                    </select>
                    
                    <div style="position: relative; flex: 1;">
                        <i class='bx bx-search' style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 18px;"></i>
                        <input type="text" placeholder="Search documents..." style="width: 100%; padding: 10px 16px 10px 42px; border: 1px solid var(--border); border-radius: 8px; background: rgba(0,0,0,0.01); outline: none; font-size: 13px; font-weight: 500; font-family: inherit; color: var(--text-dark); box-sizing: border-box;">
                    </div>
                    
                    <button class="btn-outline" style="margin: 0; border-radius: 8px; padding: 10px 20px;"><i class='bx bx-filter'></i> Filter</button>
                </div>

                <!-- Table -->
                <div style="width: 100%; overflow-x: auto;">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Category</th>
                                <th>Uploaded On</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 16px;">
                                        <div style="width: 44px; height: 44px; border-radius: 12px; background: <?php echo $doc['cat_bg']; ?>; color: <?php echo $doc['cat_color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                                            <i class='bx <?php echo $doc['icon']; ?>'></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px; white-space: nowrap;"><?php echo $doc['name']; ?></div>
                                            <div style="font-size: 11px; font-weight: 500; color: var(--text-gray); white-space: nowrap;"><?php echo $doc['desc']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 11px; font-weight: 700; color: <?php echo $doc['cat_color']; ?>; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); white-space: nowrap;">
                                        <?php echo $doc['category']; ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 2px;"><?php echo $doc['date']; ?></div>
                                    <div style="font-size: 11px; font-weight: 500; color: var(--text-gray);"><?php echo $doc['time']; ?></div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $doc['status']; ?>">
                                        <?php if($doc['status'] == 'Verified'): ?> <i class='bx bx-check-circle'></i> 
                                        <?php elseif($doc['status'] == 'Pending'): ?> <i class='bx bx-time'></i>
                                        <?php else: ?> <i class='bx bx-x-circle'></i> <?php endif; ?>
                                        <?php echo $doc['status']; ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-dark);"><?php echo $doc['size']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex;">
                                        <button class="action-btn"><i class='bx bx-show'></i></button>
                                        <button class="action-btn"><i class='bx bx-download'></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Widgets -->
            <div>
                <!-- Upload Widget -->
                <div class="side-widget">
                    <h3 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: var(--text-dark);">Upload Identity Proof</h3>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">Document Type</label>
                        <select style="width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; font-size: 13px; font-family: inherit; color: var(--text-dark); appearance: none; background: #F8FAFC url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>') no-repeat right 12px center; background-size: 14px;">
                            <option value="aadhar">Aadhar Card</option>
                        </select>
                    </div>
                    <div class="upload-area">
                        <div class="upload-icon">
                            <i class='bx bx-cloud-upload'></i>
                        </div>
                        <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: var(--text-dark);">Drag and drop your Aadhar Card here<br>or click to browse</h4>
                        <p style="margin: 0 0 24px 0; font-size: 11px; font-weight: 500; color: var(--text-gray);">Supports: PDF, JPG, PNG (Max. 10MB)</p>
                        <button class="btn-primary">Choose File</button>
                    </div>
                </div>

                <!-- Tips Widget (Moved outside docs-layout) -->
            </div>
        </div>

        <!-- Full Width Tips Widget -->
        <div class="side-widget" style="background: #FAFBFC; margin-top: 24px;">
            <h3 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-bulb' style="color: #F59E0B; font-size: 20px;"></i> Important Tips
            </h3>
            <ul class="tips-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <li><i class='bx bx-check'></i> Upload clear and valid documents for quick verification.</li>
                <li><i class='bx bx-check'></i> Supported formats: PDF, JPG, PNG</li>
                <li><i class='bx bx-check'></i> Max file size: 10MB per document</li>
                <li><i class='bx bx-check'></i> Keep your documents up to date</li>
            </ul>
        </div>
    </main>
</div>

</body>
</html>
