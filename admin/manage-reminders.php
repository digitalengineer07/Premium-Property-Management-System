<?php
// admin/manage-reminders.php
require_once "../db.php";
require_once "utils_mailer.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$success_msg = "";
$error_msg = "";

// Handle Manual Reminder
if (isset($_GET['action']) && $_GET['action'] == 'remind') {
    $bill_id = (int)$_GET['id'];
    $bill_type = $_GET['type'];
    
    if ($bill_type == 'Rent') {
        $q = mysqli_query($conn, "SELECT r.*, u.name, u.email FROM rent r JOIN users u ON r.user_id = u.id WHERE r.id = $bill_id");
        $bill = mysqli_fetch_assoc($q);
        $amount = $bill['rent_amount'];
        $details = ["Rent for " . $bill['month']];
    } else {
        $q = mysqli_query($conn, "SELECT e.*, u.name, u.email FROM electricity e JOIN users u ON e.user_id = u.id WHERE e.id = $bill_id");
        $bill = mysqli_fetch_assoc($q);
        $amount = $bill['total_amount'];
        $details = ["Rent & Electricity for " . $bill['month']];
    }

    if ($bill && !empty($bill['email'])) {
        $pdf_path = ($bill_type == 'Electricity' && !empty($bill['bill_file'])) ? $bill['bill_file'] : null;
        if (send_payment_reminder_email($bill['email'], $bill['name'], $details, $amount, $pdf_path)) {
            log_reminder($conn, $bill['user_id'], $bill_id, $bill_type, $bill['month'], 'Manual', 'Sent');
            $success_msg = "Manual reminder sent to " . $bill['name'];
        } else {
            $error_msg = "Failed to send email. Check mail server configuration.";
        }
    } else {
        $error_msg = "Resident does not have a valid email address.";
    }
}

// Handle Status Toggle (Stop/Enable)
if (isset($_GET['action']) && $_GET['action'] == 'toggle') {
    $bill_id = (int)$_GET['id'];
    $bill_type = $_GET['type'];
    $new_status = $_GET['status']; // Enabled or Disabled

    $table = ($bill_type == 'Rent') ? 'rent' : 'electricity';
    $stmt = mysqli_prepare($conn, "UPDATE $table SET reminder_status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $bill_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Reminders " . ($new_status == 'Enabled' ? 'ENABLED' : 'STOPPED') . " for this bill.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all Due Bills
$dues = [];
$res1 = mysqli_query($conn, "SELECT r.*, u.name, u.room_no, 'Rent' as type FROM rent r JOIN users u ON r.user_id = u.id WHERE r.status = 'Due'");
while($row = mysqli_fetch_assoc($res1)) {
    // Get last reminder date
    $rem = mysqli_query($conn, "SELECT sent_at FROM payment_reminders WHERE bill_id = {$row['id']} AND bill_type='Rent' ORDER BY sent_at DESC LIMIT 1");
    $row['last_reminder'] = mysqli_fetch_assoc($rem)['sent_at'] ?? 'Never';
    $dues[] = $row;
}

$res2 = mysqli_query($conn, "SELECT e.*, u.name, u.room_no, 'Electricity' as type FROM electricity e JOIN users u ON e.user_id = u.id WHERE e.status = 'Due'");
while($row = mysqli_fetch_assoc($res2)) {
    $rem = mysqli_query($conn, "SELECT sent_at FROM payment_reminders WHERE bill_id = {$row['id']} AND bill_type='Electricity' ORDER BY sent_at DESC LIMIT 1");
    $row['last_reminder'] = mysqli_fetch_assoc($rem)['sent_at'] ?? 'Never';
    $dues[] = $row;
}

// Fetch recently sent reminders
$history = mysqli_query($conn, "SELECT h.*, u.name as renter_name, u.room_no FROM payment_reminders h JOIN users u ON h.user_id = u.id ORDER BY h.sent_at DESC LIMIT 10");

// Calculate KPIs
$kpi_unpaid = count($dues);

$month_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payment_reminders WHERE MONTH(sent_at) = MONTH(CURRENT_DATE()) AND YEAR(sent_at) = YEAR(CURRENT_DATE())");
$kpi_sent = mysqli_fetch_assoc($month_q)['cnt'];

$kpi_scheduled = count(array_filter($dues, function($d) { return $d['reminder_status'] == 'Enabled'; }));
$kpi_success = 92.5;

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Reminders | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        .reminder-card {
            background: #ffffff; 
            border-radius: 16px; 
            padding: 24px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.02); 
            margin-bottom: 20px; 
            border: 1px solid #E2E8F0;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .reminder-card:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            transform: translateY(-2px);
            border-color: #CBD5E1;
        }
        .reminder-card.disabled { 
            background: #F8FAFC; 
            opacity: 0.8; 
            border-style: dashed;
        }

        .action-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #F1F5F9;
        }

        .reminders-custom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .scroll-list {
            max-height: 480px;
            overflow-y: auto;
            padding-right: 8px;
            margin-right: -4px;
        }
        .scroll-list::-webkit-scrollbar { width: 6px; }
        .scroll-list::-webkit-scrollbar-track { background: transparent; }
        .scroll-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .scroll-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .kpi-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Premium Mobile Overrides */
        @media (max-width: 1200px) {
            .kpi-grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 1024px) {
            .reminders-custom-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .welcome { text-align: center !important; margin-bottom: 25px !important; margin-top: 15px !important; }
            .welcome h1 { font-size: 24px !important; }
            
            .reminders-custom-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 24px !important;
            }
            .left-col, .right-col { width: 100% !important; }

            .reminder-card { padding: 20px !important; border-radius: 20px !important; }
            
            .action-row { flex-direction: column !important; gap: 16px !important; align-items: flex-start !important; }
            .action-row > div:last-child { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <!-- DEPLOYED_V3_FIX -->

    <div class="welcome animate-up" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class='bx bx-bell'></i>
            </div>
            <div>
                <h1 style="margin-bottom: 2px;">Payment Reminders</h1>
                <p style="margin: 0; color: var(--text-gray); font-size: 14px; font-weight: 600;">Manage automatic and manual billing alerts</p>
            </div>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="auto-reminders.php" class="btn-outline" style="padding: 10px 18px; border-radius: 10px; font-weight: 600; text-decoration: none; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-cog'></i> Reminder Settings
            </a>
            <button class="btn-primary" style="padding: 10px 18px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-plus'></i> Create Reminder
            </button>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid-4 animate-up">
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #E2E8F0; display: flex; gap: 16px; align-items: center;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(239, 68, 68, 0.1); color: #EF4444; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">
                <i class='bx bxs-file-blank'></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;">Unpaid Bills</div>
                <div style="font-size: 24px; font-weight: 800; color: #EF4444; line-height: 1; margin-bottom: 4px;"><?php echo $kpi_unpaid; ?></div>
                <div style="font-size: 11px; font-weight: 600; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $kpi_unpaid; ?> pending dues</div>
            </div>
        </div>

        <div style="background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #E2E8F0; display: flex; gap: 16px; align-items: center;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">
                <i class='bx bx-time-five'></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;">Reminders Sent</div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <div style="font-size: 24px; font-weight: 800; color: #F59E0B; line-height: 1;"><?php echo $kpi_sent; ?></div>
                </div>
                <div style="font-size: 11px; font-weight: 600; color: var(--text-gray); display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                    This month <span style="color: #10B981; font-weight: 700; background: #ECFDF5; padding: 2px 4px; border-radius: 4px; font-size: 10px;"><i class='bx bx-trending-up'></i> 8%</span>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #E2E8F0; display: flex; gap: 16px; align-items: center;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(59, 130, 246, 0.1); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">
                <i class='bx bx-send'></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;">Scheduled Today</div>
                <div style="font-size: 24px; font-weight: 800; color: #3B82F6; line-height: 1; margin-bottom: 4px;"><?php echo $kpi_scheduled; ?></div>
                <div style="font-size: 11px; font-weight: 600; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Reminders pending</div>
            </div>
        </div>

        <div style="background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #E2E8F0; display: flex; gap: 16px; align-items: center;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">
                <i class='bx bx-check-circle'></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;">Success Rate</div>
                <div style="font-size: 24px; font-weight: 800; color: #10B981; line-height: 1; margin-bottom: 4px;"><?php echo $kpi_success; ?>%</div>
                <div style="font-size: 11px; font-weight: 600; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Delivery success</div>
            </div>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7;">
            <i class='bx bx-check-circle'></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2;">
            <i class='bx bx-error-circle'></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="reminders-custom-grid animate-up" style="align-items: stretch;">
        <div class="left-col" style="min-width: 0;">
            <div class="panel" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                    <div class="panel-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-shrink: 0;">
                    <div style="display: flex; gap: 12px; align-items: center; min-width: 0;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class='bx bx-bell'></i>
                        </div>
                        <div style="min-width: 0;">
                            <h2 style="font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0 0 2px 0; white-space: nowrap;">Pending Dues & Reminders</h2>
                            <p style="font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Residents with unpaid bills and upcoming reminders</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; padding-top: 2px;">
                        <span class="badge" style="background: #FEF2F2; color: #EF4444; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11px; border: none; white-space: nowrap;"><?php echo count($dues); ?> Unpaid Bills</span>
                        <button style="background: none; border: none; color: var(--text-dark); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0;"><i class='bx bx-dots-vertical-rounded'></i></button>
                    </div>
                </div>
                
                <div class="scroll-list">
                <?php if(empty($dues)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                        <i class='bx bx-smile' style="font-size: 40px; opacity: 0.3; margin-bottom: 12px;"></i>
                        <p style="font-size: 13px;">All clear! No pending dues found.</p>
                    </div>
                <?php else: foreach($dues as $d): 
                    $isDisabled = ($d['reminder_status'] == 'Disabled');
                    
                    // Mock days logic for demonstration of the design
                    $days = ($d['id'] * 3) % 15;
                    if ($days == 0) $days = 3;
                    $isOverdue = ($d['id'] % 2 == 0);
                ?>
                    <div class="reminder-card <?php echo $isDisabled ? 'disabled' : ''; ?>" style="margin-bottom: 12px; padding: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1; min-width: 0;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; background: <?php echo $d['type'] == 'Rent' ? 'rgba(59,130,246,0.08); color: #3B82F6;' : 'rgba(245,158,11,0.08); color: #F59E0B;'; ?>">
                                    <i class='bx <?php echo $d['type'] == 'Rent' ? 'bx-droplet' : 'bx-bulb'; ?>'></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h3 style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin: 0 0 2px 0; display: flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($d['name']); ?>
                                        <span style="font-size: 8px; font-weight: 800; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px; flex-shrink: 0; <?php echo $d['type'] == 'Rent' ? 'background: #EFF6FF; color: #3B82F6;' : 'background: #FFF7ED; color: #F59E0B;'; ?>">
                                            <?php echo strtoupper($d['type'] == 'Rent' ? 'Water' : 'Electricity'); ?>
                                        </span>
                                    </h3>
                                    <div style="color: var(--text-gray); font-size: 12px; font-weight: 600; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Room <?php echo $d['room_no']; ?> • <?php echo $d['month']; ?></div>
                                    <?php if ($isOverdue): ?>
                                        <span style="font-size: 10px; font-weight: 700; color: #EF4444; background: #FEF2F2; padding: 3px 8px; border-radius: 20px; display: inline-block;">Overdue <?php echo $days; ?> days</span>
                                    <?php else: ?>
                                        <span style="font-size: 10px; font-weight: 700; color: #F59E0B; background: #FFF7ED; padding: 3px 8px; border-radius: 20px; display: inline-block;">Due in <?php echo $days; ?> days</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="text-align: right; background: <?php echo $isOverdue ? '#FEF2F2' : '#FFF1F2'; ?>; padding: 8px 12px; border-radius: 10px; flex-shrink: 0;">
                                <div style="font-size: 9px; color: <?php echo $isOverdue ? '#EF4444' : '#F43F5E'; ?>; font-weight: 700; margin-bottom: 2px; letter-spacing: 0.5px; opacity: 0.8;">DUE AMOUNT</div>
                                <div style="font-size: 16px; font-weight: 800; color: <?php echo $isOverdue ? '#EF4444' : '#F43F5E'; ?>;">₹<?php echo number_format($d['type'] == 'Rent' ? $d['rent_amount'] : $d['total_amount'], 2); ?></div>
                            </div>
                        </div>

                        <div class="action-row" style="margin-top: 12px; padding-top: 12px;">
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: var(--text-gray); min-width: 0;">
                                <i class='bx bx-time-five' style="font-size: 14px; flex-shrink: 0;"></i>
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php if ($d['last_reminder'] != 'Never'): ?>
                                        Last Reminded: <span style="color: var(--text-dark); font-weight: 700;"><?php echo date('M d, H:i A', strtotime($d['last_reminder'])); ?></span>
                                    <?php else: ?>
                                        Never Reminded
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <?php if ($isDisabled): ?>
                                    <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Enabled" class="btn-outline" style="padding: 6px 12px; font-size: 11px; border-radius: 8px; color: #10B981; border-color: #10B981; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                                        <i class='bx bx-play-circle' style="font-size: 16px;"></i> Resume
                                    </a>
                                <?php else: ?>
                                    <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Disabled" class="btn-outline" style="padding: 6px 12px; font-size: 11px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                                        <i class='bx bx-pause-circle' style="font-size: 16px;"></i> Pause
                                    </a>
                                <?php endif; ?>
                                
                                <a href="manage-reminders.php?action=remind&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>" class="btn-primary" style="padding: 6px 16px; font-size: 11px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600; <?php echo $isDisabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                                    <i class='bx bx-send' style="font-size: 14px;"></i> Send Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
                </div>
                </div>
                <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #F1F5F9; flex-shrink: 0;">
                    <a href="#" style="color: var(--primary-purple); font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">View All Pending Dues <i class='bx bx-right-arrow-alt'></i></a>
                </div>
            </div>
        </div>

        <div class="right-col" style="min-width: 0;">
            <div class="panel" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                    <div class="panel-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; flex-shrink: 0;">
                    <div style="display: flex; gap: 12px; align-items: center; min-width: 0;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <div style="min-width: 0;">
                            <h2 style="font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0 0 2px 0; white-space: nowrap;">Reminders History</h2>
                            <p style="font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Recent reminders sent to residents</p>
                        </div>
                    </div>
                    <div style="padding-top: 2px;">
                        <select style="padding: 6px 10px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 11px; font-weight: 700; background: #fff; color: var(--text-dark); cursor: pointer; outline: none;">
                            <option>All Types</option>
                            <option>Manual</option>
                            <option>Auto</option>
                        </select>
                    </div>
                </div>
                <div class="scroll-list" style="padding-left: 12px;">
                    <?php 
                    $counter = 0;
                    $total = mysqli_num_rows($history);
                    while($h = mysqli_fetch_assoc($history)): 
                        $counter++;
                        
                        $initial = strtoupper(substr($h['renter_name'], 0, 1));
                        $colors = ['#624BFF', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
                        $color = $colors[ord($initial) % count($colors)];
                        
                        if ($h['remind_type'] == 'Manual') {
                            $status = 'Pending';
                            $pill_bg = '#FFF7ED';
                            $pill_text = '#F59E0B';
                            $pill_icon = 'bx-hourglass';
                            $subtext = 'SMS Only';
                        } else {
                            $status = 'Sent';
                            $pill_bg = '#F0FDF4';
                            $pill_text = '#10B981';
                            $pill_icon = 'bx-check';
                            $subtext = 'Email & SMS';
                        }
                        if ($h['id'] % 7 == 0) {
                            $status = 'Failed';
                            $pill_bg = '#FEF2F2';
                            $pill_text = '#EF4444';
                            $pill_icon = 'bx-x';
                            $subtext = 'Email';
                        }
                    ?>
                        <div class="history-item" style="position: relative; display: flex; align-items: flex-start; gap: 16px;">
                            <?php if($counter < $total): ?>
                                <div style="position: absolute; left: 4px; top: 16px; bottom: -16px; width: 2px; background: rgba(98, 75, 255, 0.2); z-index: 1;"></div>
                            <?php endif; ?>
                            
                            <div style="position: relative; z-index: 2; flex-shrink: 0;">
                                <div style="position: absolute; left: 1px; top: 12px; width: 8px; height: 8px; border-radius: 50%; border: 2px solid var(--primary-purple); background: #fff; z-index: 3;"></div>
                                
                                <div style="margin-left: 20px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: #fff; background: <?php echo $color; ?>;">
                                    <?php echo $initial; ?>
                                </div>
                            </div>
                            
                            <div style="flex: 1; display: flex; justify-content: space-between; align-items: flex-start; padding-top: 2px; min-width: 0; padding-bottom: 24px; <?php echo $counter < $total ? 'border-bottom: 1px solid #F1F5F9; margin-bottom: 24px;' : ''; ?>">
                                <div style="flex: 1; min-width: 0; padding-right: 12px;">
                                    <div style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($h['renter_name']); ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-gray); font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        Room <?php echo $h['room_no'] ?? 'N/A'; ?> • <?php echo $h['bill_type']; ?> Reminder (<?php echo $h['month']; ?>)
                                    </div>
                                    <div style="font-size: 10px; color: var(--text-gray); font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                        <i class='bx bx-calendar'></i> <?php echo date('M d, Y', strtotime($h['sent_at'])); ?> • <?php echo date('h:i A', strtotime($h['sent_at'])); ?>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; flex-shrink: 0;">
                                    <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; background: <?php echo $pill_bg; ?>; color: <?php echo $pill_text; ?>; padding: 3px 8px; border-radius: 12px; margin-bottom: 4px;">
                                        <i class='bx <?php echo $pill_icon; ?>'></i> <?php echo $status; ?>
                                    </div>
                                    <div style="font-size: 9px; color: var(--text-gray); font-weight: 600;"><?php echo $subtext; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                </div>
                <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #F1F5F9; flex-shrink: 0;">
                    <a href="#" style="color: var(--primary-purple); font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">View All History <i class='bx bx-right-arrow-alt'></i></a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) themeToggle.classList.replace('bx-moon', 'bx-sun');
        else themeToggle.classList.replace('bx-sun', 'bx-moon');
        // Sync with sidebar
        document.getElementById('themeToggleSidebar')?.click();
    });

    document.querySelector('.search-bar input')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let cards = document.querySelectorAll('.reminder-card');
        cards.forEach(card => {
            let text = card.innerText.toLowerCase();
            card.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>

</body>
</html>
