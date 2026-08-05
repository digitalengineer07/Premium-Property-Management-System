<?php
// views/desktop/payment-approvals_desktop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approvals - <?php echo htmlspecialchars(HOUSE_NAME); ?></title>
    
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
        :root {
            --primary-purple: #624BFF;
            --primary-purple-hover: #4F39F6;
            --sidebar-bg: #FFFFFF;
            --bg-main: #F8FAFC;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --white: #FFFFFF;
        }

        .dark-theme {
            --sidebar-bg: #111827;
            --bg-main: #0B0F19;
            --text-dark: #F8FAFC;
            --text-gray: #94A3B8;
            --border: #1E293B;
            --white: #111827;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles (Reused standard sidebar) */
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

        
        .main-content {
            flex: 1;
            margin-left: 230px;
            height: 100vh;
            overflow-y: auto;
            padding: 32px 40px;
            padding-bottom: 40px; /* Extra padding so the bottom isn't cut off */
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .header-title p { color: var(--text-gray); margin: 0; font-size: 13px; }
        
        .btn-primary {
            background: var(--primary-purple);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 2px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: var(--primary-purple-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(98,75,255,0.2);
        }

        .approvals-table-container {
            background: var(--sidebar-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(248, 250, 252, 0.5);
            padding: 16px 24px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        .dark-theme th { background: rgba(0,0,0,0.2); }

        td {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: all 0.2s; }
        tbody tr:hover { background: rgba(98, 75, 255, 0.02); }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
        .status-approved { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }

        /* Media query for smaller desktops */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span { display: none; }
            .nav-item { justify-content: center; padding: 10px 16px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; padding: 24px; }
            .approvals-table-container { overflow-x: auto; }
            table { min-width: 800px; } /* Ensures table doesn't squish unreadably */
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 16px !important; padding-bottom: 90px !important; }
            .top-header { display: none !important; }
            .mobile-header-only { display: flex !important; }
            .main-content { padding-top: 100px !important; }
            .header-actions { width: 100%; justify-content: space-between; }
            .mobile-header-only { display: none; }
        .mobile-bottom-nav { display: flex !important; }
            table { min-width: 600px; }
        }

        .mobile-header-only { display: none; }
        .mobile-bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: var(--sidebar-bg);
            justify-content: space-around; align-items: center;
            padding: 12px 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
            border-top: 1px solid var(--border);
            z-index: 1000;
        }
        .dark-theme .mobile-header-only { display: none; }
        .mobile-bottom-nav { box-shadow: 0 -4px 20px rgba(0,0,0,0.3); }
        .mb-nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            color: var(--text-gray); text-decoration: none; font-size: 10px; font-weight: 600;
            width: 20%;
        }
        .mb-nav-item i { font-size: 22px; transition: 0.2s; }
        .mb-nav-item.active { color: var(--primary-purple); }
        .mb-nav-item.active i { transform: translateY(-2px); }
        .mb-nav-center {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--primary-purple); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer; margin-top: -24px;
            border: 4px solid var(--bg-main); transition: transform 0.2s;
        }
    
        /* Top Header Styles Extracted from Dashboard */
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
        
        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 13px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
        
        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 2px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: rgba(98, 75, 255, 0.02); }

    
        /* Notification Dropdown Fix */
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
        .dark-theme #notifDropdown {
            background: var(--sidebar-bg);
            border-color: var(--border);
        }

    </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : ''; ?>">

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
            <a href="payment-approvals.php" class="nav-item active">
                <i class='bx bx-check-shield'></i>
                <span>Approvals</span>
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
            <a href="documents.php" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a></nav>
        <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border, #E2E8F0);">
            <a href="../logout.php" class="nav-item" style=" color: #FF4B6B; ">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        
        </div>
    </aside>

    <main class="main-content">
                <div class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark);">Payment Approvals</h1>
                    <p style="margin: 4px 0 0 0; color: var(--text-gray); font-size: 14px;">Track your cash and UPI payment verifications</p>
                </div>
            </div>
            <div class="header-actions" style="display: flex; align-items: center; gap: 16px;">

                <button class="btn-primary" style="display: flex; align-items: center; gap: 8px; margin-right: 12px; background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer;" onclick="openApprovalModal()">
                    <i class='bx bx-plus'></i> Apply for Approval
                </button>
            <div class="header-actions">
                <div class="notification-wrapper" style="position: relative; display: inline-block;">
                    <div class="icon-btn bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'block' : 'none';">
                        <i class='bx bx-bell'></i>
                        <?php if ($unread_count > 0): ?>
                            <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notification Dropdown -->
                    <div id="notifDropdown" style="display: none;">
                        <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark);">Notifications</h3>
                            <?php if($unread_count > 0): ?>
                                <span style="font-size: 11px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 8px; border-radius: 10px; font-weight: 600;"><?php echo $unread_count; ?> New</span>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 350px;">
                            <?php if (empty($unread_notifications)): ?>
                                <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                    <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p style="margin: 0; font-size: 13px;">You're all caught up!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($unread_notifications as $notif): ?>
                                    <div class="notif-item animate-up" data-id="<?php echo $notif['id']; ?>" style="border-bottom: 1px solid var(--border); position: relative; overflow: hidden; background: var(--white); cursor: default;">
                                        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: #EF4444; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1;">
                                            <i class='bx bx-trash'></i>
                                        </div>
                                        <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; position: relative; z-index: 2; background: var(--white); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                                <i class='bx <?php echo $notif['icon']; ?>'></i>
                                            </div>
                                            <div style="flex: 1; padding-right: 36px;">
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                                    <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--text-dark); padding-right: 8px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                    <span style="font-size: 11px; color: var(--text-gray); font-weight: 600; white-space: nowrap;"><?php echo date('M d', strtotime($notif['time'])); ?></span>
                                                </div>
                                                <p style="margin: 0; font-size: 13px; color: var(--text-gray); line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            </div>
                                            <button onclick="dismissNotification('<?php echo $notif['id']; ?>', this)" style="position: absolute; right: 12px; top: 16px; background: none; border: none; font-size: 18px; color: var(--text-gray); opacity: 0.5; cursor: pointer; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.05)'; this.style.opacity='1'" onmouseout="this.style.background='none'; this.style.opacity='0.5'" title="Dismiss">
                                                <i class='bx bx-x'></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="icon-btn" id="themeToggle" style="cursor: pointer;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i')||(this.tagName==='I'?this:null);if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
                    <i class='bx bx-moon'></i>
                </div>
                
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
                            <h4><?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                        </div>
                        <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                    </div>
                    
                    <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: var(--white); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 13px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                            <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                        </a>
                        <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 13px; font-weight: 500; transition: 0.2s;">
                            <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            </div>
        </div>


        <?php if (!empty($payment_success)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 2px; font-weight: 600;">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i> <?php echo $payment_success; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment_error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 2px; font-weight: 600;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo $payment_error; ?>
            </div>
        <?php endif; ?>

        <div class="approvals-table-container">
            <?php if (count($approvals) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Bill Month</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Ref No / UTR</th>
                        <th>Status</th>
                        <th>Admin Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvals as $ap): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?php echo date('d M Y', strtotime($ap['created_at'])); ?></div>
                            <div style="font-size: 12px; color: var(--text-gray); margin-top: 4px;"><?php echo date('h:i A', strtotime($ap['created_at'])); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-gray);">
                                <?php echo !empty($ap['month']) ? htmlspecialchars($ap['month']) : '-'; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--primary-purple);">&#8377;<?php echo number_format($ap['amount'], 2); ?></div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                                <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px; width: 40px; object-fit: contain;">
                                <?php else: ?>
                                    <i class='bx bx-money' style="color: #10B981; font-size: 18px;"></i> Cash
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($ap['transaction_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 6px; border-radius: 6px; font-size: 11px; color: var(--text-gray); white-space: nowrap; display: inline-block;">
                                    <?php echo htmlspecialchars($ap['transaction_id']); ?>
                                </span>
                            <?php elseif (!empty($ap['sys_tx_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 6px; border-radius: 6px; font-size: 11px; color: var(--text-gray); white-space: nowrap; display: inline-block;">
                                    <?php echo htmlspecialchars($ap['sys_tx_id']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($ap['status']); ?>">
                                <?php if ($ap['status'] == 'Pending'): ?>
                                    <i class='bx bx-time'></i>
                                <?php elseif ($ap['status'] == 'Approved'): ?>
                                    <i class='bx bx-check-double'></i>
                                <?php else: ?>
                                    <i class='bx bx-x'></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ap['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($ap['admin_note'])): ?>
                                <div style="font-size: 13px; color: var(--text-gray); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ap['admin_note']); ?>">
                                    "<?php echo htmlspecialchars($ap['admin_note']); ?>"
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-gray); font-style: italic;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 16px; padding: 16px 0; border-top: 1px solid var(--border);">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-left'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-left'></i></span>
                    <?php endif; ?>
                    
                    <span style="font-size: 14px; font-weight: 800; color: var(--text-dark); min-width: 24px; text-align: center;"><?php echo $page; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-right'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-right'></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php else: ?>
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: rgba(98, 75, 255, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                        <i class='bx bx-check-shield' style="font-size: 40px; color: var(--primary-purple);"></i>
                    </div>
                    <h3 style="margin: 0 0 8px 0; color: var(--text-dark); font-size: 18px;">No Approval Requests</h3>
                    <p style="margin: 0; color: var(--text-gray); font-size: 13px;">You haven't submitted any payment verifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

        <!-- Mobile Bottom Navigation (Visible only on small screens) -->
    <nav class="mobile-bottom-nav">
        <a href="dashboard.php" class="mb-nav-item">
            <i class='bx bx-home-alt-2'></i>
            <span>Home</span>
        </a>
        <a href="my-bills.php" class="mb-nav-item">
            <i class='bx bx-receipt'></i>
            <span>Bills</span>
        </a>
        <div class="mb-nav-center" onclick="openApprovalModal()">
            <i class='bx bx-plus'></i>
        </div>
        <a href="payment-approvals.php" class="mb-nav-item active">
            <i class='bx bx-check-shield'></i>
            <span>Approvals</span>
        </a>
        <a href="profile.php" class="mb-nav-item">
            <i class='bx bx-user'></i>
            <span>Profile</span>
        </a>
    </nav>
    
    <?php include "approval_modal.php"; ?>
    <script>
        function setCookie(name, value, days) {
            let expires = "";
            if (days) {
                let date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }

        function getCookie(name) {
            let nameEQ = name + "=";
            let ca = document.cookie.split(';');
            for(let i=0;i < ca.length;i++) {
                let c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        function dismissNotification(id, el) {
            let item = el.closest('.notif-item');
            if (item) {
                item.style.height = item.offsetHeight + 'px';
                item.style.transition = 'all 0.3s';
                item.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    item.style.height = '0px';
                    item.style.padding = '0px';
                    item.style.border = 'none';
                    setTimeout(() => item.remove(), 300);
                }, 300);
            }
            
            let currentStr = getCookie('dismissed_notifs');
            let currentIds = currentStr ? currentStr.split(',') : [];
            if (!currentIds.includes(id)) {
                currentIds.push(id);
                setCookie('dismissed_notifs', currentIds.join(','), 30);
            }
            
            let badge = document.querySelector('.bell-icon span');
            if (badge) {
                let count = parseInt(badge.innerText) - 1;
                if (count <= 0) {
                    badge.remove();
                    let container = document.querySelector('#notifDropdown > div:nth-child(2)');
                    if (container && document.querySelectorAll('.notif-item').length <= 1) {
                        setTimeout(() => {
                            container.innerHTML = `<div style="padding: 30px; text-align: center; color: var(--text-gray);"><i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i><p style="margin: 0; font-size: 13px;">You're all caught up!</p></div>`;
                        }, 600);
                    }
                } else {
                    badge.innerText = count;
                }
            }
            
            let countLabel = document.querySelector('#notifDropdown span[style*="background: rgba(239, 68, 68, 0.1)"]');
            if (countLabel) {
                let count = parseInt(countLabel.innerText) - 1;
                if (count <= 0) countLabel.remove();
                else countLabel.innerText = count + ' New';
            }
        }
    </script>
</body>
</html>
