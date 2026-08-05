<?php
// views/mobile/payment-approvals_mobile.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Approvals - <?php echo htmlspecialchars(HOUSE_NAME); ?></title>
    
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
            --primary: #624BFF;
            --bg-main: #F4F7FA;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --white: #FFFFFF;
            --border: #E2E8F0;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }

        .dark-theme {
            --bg-main: #0B0F19;
            --text-dark: #F8FAFC;
            --text-gray: #94A3B8;
            --white: #111827;
            --border: #1E293B;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            margin: 0;
            padding-bottom: 80px; /* Space for bottom nav */
            -webkit-tap-highlight-color: transparent;
        }

        /* Top Bar */
                .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .greeting { font-size: 11px; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 2px 0; }
        .name { font-size: 15px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.3px; }

        
        .content-area { padding: 20px; width: 100%; box-sizing: border-box; }

        .approval-card {
            background: var(--white);
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid var(--border);
        }

        .ac-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border);
        }
        .ac-date {
            font-size: 12px;
            color: var(--text-gray);
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
        .status-approved { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }

        .ac-amount {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .ac-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 8px;
            font-size: 13px;
            align-items: start;
        }
        .ac-details > div { display: flex; flex-direction: column; }
        .ac-details > div:nth-child(2) { text-align: center; align-items: center; }
        .ac-details > div:nth-child(3) { text-align: right; align-items: flex-end; }
        .ac-value { word-break: break-all; }
        .ac-label { color: var(--text-gray); font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .ac-value { color: var(--text-dark); font-weight: 700; }

        .ac-note {
            margin-top: 12px;
            padding: 10px;
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
            font-size: 12px;
            color: var(--text-gray);
            border-left: 3px solid var(--primary);
        }

            </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : ''; ?>">

    <header class="premium-header-pill" style="position: fixed; top: 0; left: 0;">
            <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
                <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.toggle('mobile-drawer-open'); }" style="color: white; font-size: 28px; cursor: pointer;">
                    <i class='bx bx-menu-alt-left'></i>
                </div>
                <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-check-shield' style="font-size: 24px; color: #ffffff; margin-top: 2px;"></i>
                    Approvals
                </h1>
            </div>
            
            <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 6px;">
                <div class="header-icon-btn" onclick="openApprovalModal()" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid; cursor: pointer;">
                    <i class='bx bx-plus' style="color: white;"></i>
                </div>
                <div class="header-icon-btn" onclick="if(typeof openNotif==='function') openNotif(); else alert('Notifications');" style="color: white; border-color: rgba(255,255,255,0.2); border: 1px solid; cursor: pointer; position: relative;">
                    <i class='bx bx-bell' style="color: white;"></i>
                    <?php if (isset($unread_count) && $unread_count > 0): ?>
                        <span class="m-notif-badge" style="position: absolute; top: 0; right: 0; width: 10px; height: 10px; background: #EF4444; border-radius: 50%; border: 2px solid #624BFF;"></span>
                    <?php endif; ?>
                </div>
                <a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
                    <div style="width: 100%; height: 100%; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #624BFF; font-size: 16px; font-weight: 800;">
                        <?php echo strtoupper(substr(trim($display_name ?? 'U'), 0, 1)); ?>
                    </div>
                </a>
            </div>
        </header>

    <div class="content-area" style="padding-top: 100px;">
        <?php if (!empty($payment_success)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 12px; border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 2px; font-size: 13px; font-weight: 600;">
                <i class='bx bx-check-circle' style="font-size: 18px;"></i> <?php echo $payment_success; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment_error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 12px; border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 2px; font-size: 13px; font-weight: 600;">
                <i class='bx bx-error-circle' style="font-size: 18px;"></i> <?php echo $payment_error; ?>
            </div>
        <?php endif; ?>

        <?php if (count($approvals) > 0): ?>
            <?php foreach ($approvals as $ap): ?>
            <div class="approval-card">
                <div class="ac-header">
                    <div class="ac-date"><i class='bx bx-calendar'></i> <?php echo date('d M Y, h:i A', strtotime($ap['created_at'])); ?></div>
                    <span class="status-badge status-<?php echo strtolower($ap['status']); ?>">
                        <?php echo htmlspecialchars($ap['status']); ?>
                    </span>
                </div>
                
                <div class="ac-amount">&#8377;<?php echo number_format($ap['amount'], 2); ?></div>
                
                <div class="ac-details">
                    <div>
                        <div class="ac-label">Method</div>
                        <div class="ac-value" style="display: flex; align-items: center; gap: 4px;">
                            <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                <span style="background: rgba(98, 75, 255, 0.1); color: #624BFF; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; border: 1px solid rgba(98, 75, 255, 0.2);">UPI</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.2);"><i class='bx bx-money'></i> Cash</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div class="ac-label">Bill Month</div>
                        <div class="ac-value"><?php echo !empty($ap['month']) ? htmlspecialchars($ap['month']) : '-'; ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div class="ac-label">Ref / UTR No</div>
                        <div class="ac-value" style="font-family: monospace;">
                            <?php 
                                if (!empty($ap['transaction_id'])) echo htmlspecialchars($ap['transaction_id']);
                                else if (!empty($ap['sys_tx_id'])) echo htmlspecialchars($ap['sys_tx_id']);
                                else echo 'N/A'; 
                            ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($ap['admin_note'])): ?>
                    <div class="ac-note">
                        <strong>Admin Note:</strong> <?php echo htmlspecialchars($ap['admin_note']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
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
            <div style="padding: 40px 20px; text-align: center;">
                <div style="width: 70px; height: 70px; background: rgba(98, 75, 255, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                    <i class='bx bx-check-shield' style="font-size: 32px; color: var(--primary);"></i>
                </div>
                <h3 style="margin: 0 0 8px 0; color: var(--text-dark); font-size: 16px;">No Requests Yet</h3>
                <p style="margin: 0; color: var(--text-gray); font-size: 13px;">You haven't submitted any payment verifications.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Bottom Navigation -->
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
<script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>
<?php include_once __DIR__ . '/mobile_sidebar.php'; ?>
</body>
</html>
