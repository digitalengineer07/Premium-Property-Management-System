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
$history = mysqli_query($conn, "SELECT h.*, u.name as renter_name FROM payment_reminders h JOIN users u ON h.user_id = u.id ORDER BY h.sent_at DESC LIMIT 10");

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
        .reminder-card { background: var(--white); border-radius: 20px; padding: 24px; box-shadow: var(--card-shadow); margin-bottom: 20px; border-left: 5px solid var(--primary-purple); }
        .reminder-card.disabled { border-left-color: #cbd5e1; opacity: 0.8; }
        .history-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-bottom: 1px solid var(--border); }
        .history-item:last-child { border-bottom: none; }

        /* Premium Mobile Overrides */
        @media (max-width: 768px) {
            .welcome { text-align: center !important; margin-bottom: 25px !important; margin-top: 15px !important; }
            .welcome h1 { font-size: 24px !important; }
            
            .dashboard-grid-70 {
                display: flex !important;
                flex-direction: column !important;
                gap: 24px !important;
            }
            .left-col, .right-col { width: 100% !important; }

            .reminder-card { padding: 20px !important; border-radius: 20px !important; }
            .reminder-card-header { flex-direction: column !important; align-items: flex-start !important; text-align: left !important; gap: 15px !important; }
            .reminder-card-header > div:last-child { text-align: left !important; border-top: 1px solid var(--border); width: 100%; padding-top: 15px; }
            
            .reminder-actions { flex-direction: column !important; gap: 12px !important; }
            .reminder-actions a { width: 100% !important; justify-content: center !important; padding: 12px !important; font-size: 14px !important; }
            
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>Payment Reminders</h1>
        <p>Manage automatic and manual billing alerts</p>
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

    <div class="dashboard-grid-70 animate-up" style="align-items: stretch;">
        <div class="left-col">
            <div class="panel" style="height: 100%;">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Pending Dues & Reminders</h2>
                    <span class="badge" style="background: #FEE2E2; color: #EF4444;"><?php echo count($dues); ?> Unpaid Bills</span>
                </div>
                
                <?php if(empty($dues)): ?>
                    <div style="text-align: center; padding: 60px; color: var(--text-gray);">
                        <i class='bx bx-smile' style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;"></i>
                        <p>All clear! No pending dues found.</p>
                    </div>
                <?php else: foreach($dues as $d): 
                    $isDisabled = ($d['reminder_status'] == 'Disabled');
                ?>
                    <div class="reminder-card <?php echo $isDisabled ? 'disabled' : ''; ?>">
                        <div class="reminder-card-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div>
                                <span class="badge" style="display: inline-block; background: <?php echo $d['type'] == 'Rent' ? '#EFF6FF; color: #3B82F6;' : '#FFF7ED; color: #F59E0B;'; ?> font-size: 10px; margin-bottom: 8px; letter-spacing: 0.5px; padding: 6px 12px;">
                                    <?php echo strtoupper($d['type']); ?>
                                </span>
                                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-dark); margin: 0 0 10px 0; letter-spacing: -0.3px; padding-top: 4px;">
                                    <?php echo htmlspecialchars($d['name']); ?> <small style="color: var(--text-gray); font-weight: 400; font-size: 14px; margin-left: 4px;">(Room <?php echo $d['room_no']; ?>)</small>
                                </h3>
                                <div style="font-size: 14px; color: var(--text-gray); line-height: 1.6; display: flex; flex-wrap: wrap; gap: 15px;">
                                    <span>Month: <strong style="color: var(--text-dark);"><?php echo $d['month']; ?></strong></span>
                                    <span style="color: var(--border);">|</span>
                                    <span>Amount: <strong style="color: #ef4444;">₹<?php echo number_format($d['type'] == 'Rent' ? $d['rent_amount'] : $d['total_amount'], 2); ?></strong></span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; color: var(--text-gray); margin-bottom: 5px;">Last Reminded</div>
                                <div style="font-size: 13px; font-weight: 600;">
                                    <?php echo ($d['last_reminder'] != 'Never') ? date('M d, H:i', strtotime($d['last_reminder'])) : 'No reminders yet'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="reminder-actions" style="display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border);">
                            <a href="manage-reminders.php?action=remind&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>" 
                               class="btn-primary" style="padding: 8px 16px; font-size: 12px; <?php echo $isDisabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                                <i class='bx bx-send'></i> Send Now
                            </a>
                            
                            <?php if ($isDisabled): ?>
                                <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Enabled" 
                                   class="btn-outline" style="padding: 8px 16px; font-size: 12px; color: #10B981;">
                                    <i class='bx bx-play-circle'></i> Enable Reminders
                                </a>
                            <?php else: ?>
                                <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Disabled" 
                                   class="btn-outline" style="padding: 8px 16px; font-size: 12px; color: #EF4444;">
                                    <i class='bx bx-stop-circle'></i> Stop Reminders
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="right-col">
            <div class="panel" style="height: 100%;">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Reminders History</h2>
                </div>
                <div style="margin-top: 15px;">
                    <?php while($h = mysqli_fetch_assoc($history)): ?>
                        <div class="history-item" style="padding: 16px 12px;">
                            <div style="background: <?php echo $h['remind_type'] == 'Manual' ? 'rgba(98, 75, 255, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class='bx <?php echo $h['remind_type'] == 'Manual' ? 'bx-user' : 'bx-bot'; ?>' style="color: <?php echo $h['remind_type'] == 'Manual' ? 'var(--primary-purple)' : '#10B981'; ?>; font-size: 18px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;"><?php echo htmlspecialchars($h['renter_name']); ?></div>
                                <div style="font-size: 11px; color: var(--text-gray); font-weight: 500;"><?php echo $h['bill_type']; ?> Reminder (<?php echo $h['month']; ?>)</div>
                            </div>
                            <div style="font-size: 11px; color: var(--text-gray); text-align: right; font-weight: 600;">
                                <?php echo date('M d', strtotime($h['sent_at'])); ?><br>
                                <span style="opacity: 0.6; font-weight: 400;"><?php echo date('H:i', strtotime($h['sent_at'])); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <p style="font-size: 11px; color: var(--text-gray);">Auto-Reminders run after the 20th of each month for all enabled bills.</p>
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
