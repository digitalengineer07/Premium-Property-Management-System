<?php
// views/desktop/payment-approvals_desktop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approvals - <?php echo htmlspecialchars(HOUSE_NAME); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            box-sizing: border-box;
            transition: all 0.3s ease;
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
            color: white; font-size: 20px;
        }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }

        .nav-menu { display: flex; flex-direction: column; gap: 4px; flex: 1;  overflow-y: auto;}
        .nav-menu::-webkit-scrollbar { width: 4px; }
        .nav-menu::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none;
            font-weight: 600; font-size: 13px; transition: all 0.2s ease;
        }
        .nav-item:hover { background: rgba(98, 75, 255, 0.05); color: var(--primary-purple); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98,75,255,0.2); }
        
        .main-content {
            flex: 1;
           
            padding: 32px 40px;
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
            .nav-item { justify-content: center; padding: 8px 16px; }
            .nav-item i { font-size: 24px; }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : ''; ?>">

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
            <a href="dashboard.php" class="nav-item"><i class='bx bx-grid-alt'></i><span>Overview</span></a>
            <a href="my-bills.php" class="nav-item"><i class='bx bx-receipt'></i><span>My Bills</span></a>
            <a href="my-payments.php" class="nav-item"><i class='bx bx-wallet'></i><span>My Payments</span></a>
            <a href="payment-history.php" class="nav-item"><i class='bx bx-history'></i><span>Payment History</span></a>
            <a href="payment-approvals.php" class="nav-item active"><i class='bx bx-check-shield'></i><span>Approvals</span></a>
            <a href="electricity-record.php" class="nav-item"><i class='bx bx-bulb'></i><span>Electricity</span></a>
            <a href="documents.php" class="nav-item"><i class='bx bx-folder'></i><span>Documents</span></a>
            <a href="queries.php" class="nav-item"><i class='bx bx-message-square-dots'></i><span>Queries</span></a>
            <a href="notices.php" class="nav-item"><i class='bx bx-bell'></i><span>Notices</span></a>
            <a href="profile.php" class="nav-item"><i class='bx bx-user'></i><span>Profile</span></a></nav>
        <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border, #E2E8F0);">
            <a href="../logout.php" class="nav-item" style="  color: var(--danger);"><i class='bx bx-log-out'></i><span>Logout</span></a>
        
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <div class="header-title">
                <h1>Payment Approvals</h1>
                <p>Track your submitted payment verifications</p>
            </div>
            
            <button class="btn-primary" onclick="openPaymentModal(0, 'Advance / General Payment', 'general', 0, 'Advance/General')">
                <i class='bx bx-plus'></i> Apply for Approval
            </button>
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
                        <th>Date Submitted</th>
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
                            <div style="font-weight: 800; color: var(--primary-purple);">&#8377;<?php echo number_format($ap['amount'], 2); ?></div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                                <?php if (strtolower($ap['payment_method']) === 'upi'): ?>
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px;"> UPI
                                <?php else: ?>
                                    <i class='bx bx-money' style="color: #10B981; font-size: 18px;"></i> Cash
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($ap['transaction_id'])): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; font-size: 13px; color: var(--text-gray);">
                                    <?php echo htmlspecialchars($ap['transaction_id']); ?>
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

    <?php include "payment_modal.php"; ?>
</body>
</html>
