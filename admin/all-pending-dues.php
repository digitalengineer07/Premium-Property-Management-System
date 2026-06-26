<?php
// admin/all-pending-dues.php
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
            $success_msg = "Manual reminder sent to " . htmlspecialchars($bill['name']);
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
$res1 = mysqli_query($conn, "SELECT r.*, u.name, u.room_no, 'Rent' as type FROM rent r JOIN users u ON r.user_id = u.id WHERE r.status = 'Due' AND u.status = 'active'");
while($row = mysqli_fetch_assoc($res1)) {
    $rem = mysqli_query($conn, "SELECT sent_at FROM payment_reminders WHERE bill_id = {$row['id']} AND bill_type='Rent' ORDER BY sent_at DESC LIMIT 1");
    $row['last_reminder'] = mysqli_fetch_assoc($rem)['sent_at'] ?? 'Never';
    $dues[] = $row;
}

$res2 = mysqli_query($conn, "SELECT e.*, u.name, u.room_no, 'Electricity' as type FROM electricity e JOIN users u ON e.user_id = u.id WHERE e.status = 'Due' AND u.status = 'active'");
while($row = mysqli_fetch_assoc($res2)) {
    $rem = mysqli_query($conn, "SELECT sent_at FROM payment_reminders WHERE bill_id = {$row['id']} AND bill_type='Electricity' ORDER BY sent_at DESC LIMIT 1");
    $row['last_reminder'] = mysqli_fetch_assoc($rem)['sent_at'] ?? 'Never';
    $dues[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Pending Dues | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        .reminder-card {
            background: #ffffff; 
            border-radius: 16px; 
            padding: 24px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.02); 
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
        .dues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 24px;
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <a href="manage-reminders.php" style="width: 48px; height: 48px; border-radius: 12px; background: #fff; border: 1px solid #E2E8F0; color: var(--text-dark); display: flex; align-items: center; justify-content: center; font-size: 24px; text-decoration: none; transition: all 0.2s;">
                <i class='bx bx-arrow-back'></i>
            </a>
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class='bx bx-wallet'></i>
            </div>
            <div>
                <h1 style="margin-bottom: 2px;">All Pending Dues</h1>
                <p style="margin: 0; color: var(--text-gray); font-size: 14px; font-weight: 600;">Full list of residents with unpaid bills</p>
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

    <div class="dues-grid animate-up">
        <?php if(empty($dues)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: #fff; border-radius: 16px; border: 1px solid #E2E8F0;">
                <i class='bx bx-smile' style="font-size: 48px; color: var(--text-gray); opacity: 0.3; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; color: var(--text-dark); margin-bottom: 8px;">All Clear!</h3>
                <p style="font-size: 14px; color: var(--text-gray);">No pending dues found across all active residents.</p>
            </div>
        <?php else: foreach($dues as $d): 
            $isDisabled = ($d['reminder_status'] == 'Disabled');
            
            // Mock days logic for demonstration of the design
            $days = ($d['id'] * 3) % 15;
            if ($days == 0) $days = 3;
            $isOverdue = ($d['id'] % 2 == 0);
        ?>
            <div class="reminder-card <?php echo $isDisabled ? 'disabled' : ''; ?>" style="margin-bottom: 0;">
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
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 600; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Room <?php echo htmlspecialchars($d['room_no']); ?> • <?php echo htmlspecialchars($d['month']); ?></div>
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

                <div class="action-row">
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
                            <a href="?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Enabled" class="btn-outline" style="padding: 6px 12px; font-size: 11px; border-radius: 8px; color: #10B981; border-color: #10B981; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                                <i class='bx bx-play-circle' style="font-size: 16px;"></i> Resume
                            </a>
                        <?php else: ?>
                            <a href="?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Disabled" class="btn-outline" style="padding: 6px 12px; font-size: 11px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                                <i class='bx bx-pause-circle' style="font-size: 16px;"></i> Pause
                            </a>
                        <?php endif; ?>
                        
                        <a href="?action=remind&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>" class="btn-primary" style="padding: 6px 16px; font-size: 11px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600; <?php echo $isDisabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                            <i class='bx bx-send' style="font-size: 14px;"></i> Send Now
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>
<script>
    // Include active states for sidebar
    const currentUrl = 'manage-reminders.php'; // Keep sidebar active on Reminders
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        if (link.getAttribute('href') === currentUrl) {
            link.classList.add('active');
        }
    });
</script>
</body>
</html>
