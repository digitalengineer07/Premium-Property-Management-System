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

        .history-item { 
            display: flex; 
            align-items: flex-start; 
            gap: 16px; 
            padding-bottom: 24px; 
            position: relative;
        }
        .history-item:last-child {
            padding-bottom: 0;
        }
        .history-item::before {
            content: '';
            position: absolute;
            left: 21px; /* Center of 44px icon */
            top: 44px; /* Start below icon */
            bottom: 0; /* Stretch to bottom of padding */
            width: 2px;
            background: #E2E8F0;
            z-index: 0;
        }
        .history-item:last-child::before {
            display: none;
        }

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
            
            .action-row { flex-direction: column !important; gap: 16px !important; align-items: flex-start !important; }
            .action-row > div:last-child { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <!-- DEPLOYED_V2_FIX -->

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
                <div class="panel-header" style="margin-bottom: 24px;">
                    <h2 style="font-size: 18px; font-weight: 700;">Pending Dues & Reminders</h2>
                    <span class="badge" style="background: #FEF2F2; color: #EF4444; font-weight: 700; padding: 6px 12px; border-radius: 8px; font-size: 12px; border: none;"><?php echo count($dues); ?> Unpaid Bills</span>
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
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: <?php echo $d['type'] == 'Rent' ? 'rgba(98,75,255,0.1); color: #624BFF;' : 'rgba(245,158,11,0.1); color: #F59E0B;'; ?>">
                                    <i class='bx <?php echo $d['type'] == 'Rent' ? 'bx-home-smile' : 'bx-bulb'; ?>'></i>
                                </div>
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 800; color: var(--text-dark); margin: 0 0 6px 0; display: flex; align-items: center; gap: 8px;">
                                        <?php echo htmlspecialchars($d['name']); ?>
                                        <span style="font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; <?php echo $d['type'] == 'Rent' ? 'background: #EEF2FF; color: #4F46E5;' : 'background: #FFF7ED; color: #F59E0B;'; ?>">
                                            <?php echo strtoupper($d['type']); ?>
                                        </span>
                                    </h3>
                                    <div style="color: var(--text-gray); font-size: 14px; font-weight: 600;">Room <?php echo $d['room_no']; ?> • <?php echo $d['month']; ?></div>
                                </div>
                            </div>
                            
                            <div style="text-align: right; background: #F8FAFC; padding: 10px 16px; border-radius: 12px; border: 1px solid #F1F5F9;">
                                <div style="font-size: 11px; color: var(--text-gray); font-weight: 700; margin-bottom: 2px; letter-spacing: 0.5px;">DUE AMOUNT</div>
                                <div style="font-size: 18px; font-weight: 800; color: #EF4444;">₹<?php echo number_format($d['type'] == 'Rent' ? $d['rent_amount'] : $d['total_amount'], 2); ?></div>
                            </div>
                        </div>

                        <div class="action-row">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--text-gray);">
                                <i class='bx bx-time-five' style="font-size: 16px;"></i>
                                <?php if ($d['last_reminder'] != 'Never'): ?>
                                    Last Reminded: <span style="color: var(--text-dark);"><?php echo date('M d, H:i', strtotime($d['last_reminder'])); ?></span>
                                <?php else: ?>
                                    Never Reminded
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 12px;">
                                <?php if ($isDisabled): ?>
                                    <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Enabled" class="btn-outline" style="padding: 8px 16px; font-size: 13px; border-radius: 10px; color: #10B981; border-color: #10B981; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 600;">
                                        <i class='bx bx-play-circle' style="font-size: 18px;"></i> Resume
                                    </a>
                                <?php else: ?>
                                    <a href="manage-reminders.php?action=toggle&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>&status=Disabled" class="btn-outline" style="padding: 8px 16px; font-size: 13px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 600;">
                                        <i class='bx bx-pause-circle' style="font-size: 18px;"></i> Pause
                                    </a>
                                <?php endif; ?>
                                
                                <a href="manage-reminders.php?action=remind&id=<?php echo $d['id']; ?>&type=<?php echo $d['type']; ?>" class="btn-primary" style="padding: 8px 20px; font-size: 13px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 600; <?php echo $isDisabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                                    <i class='bx bx-send' style="font-size: 16px;"></i> Send Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="right-col">
            <div class="panel" style="height: 100%;">
                <div class="panel-header" style="margin-bottom: 24px;">
                    <h2 style="font-size: 18px; font-weight: 700;">Reminders History</h2>
                </div>
                <div>
                    <?php while($h = mysqli_fetch_assoc($history)): ?>
                        <div class="history-item">
                            <div class="history-icon-wrapper">
                                <div style="background: <?php echo $h['remind_type'] == 'Manual' ? 'rgba(98, 75, 255, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; z-index: 2; position: relative;">
                                    <i class='bx <?php echo $h['remind_type'] == 'Manual' ? 'bx-user' : 'bx-bot'; ?>' style="color: <?php echo $h['remind_type'] == 'Manual' ? 'var(--primary-purple)' : '#10B981'; ?>; font-size: 22px;"></i>
                                </div>
                            </div>
                            <div style="flex: 1; padding-top: 2px;">
                                <div style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($h['renter_name']); ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-gray); font-weight: 600; margin-bottom: 6px;">
                                    <?php echo $h['bill_type']; ?> Reminder (<?php echo $h['month']; ?>)
                                </div>
                                <div style="display: inline-block; font-size: 11px; color: var(--text-gray); font-weight: 700; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 4px 10px; border-radius: 6px;">
                                    <i class='bx bx-time-five' style="margin-right: 2px;"></i> <?php echo date('M d', strtotime($h['sent_at'])); ?> • <?php echo date('g:i A', strtotime($h['sent_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="margin-top: 32px; padding: 16px; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1; text-align: center;">
                    <p style="font-size: 12px; color: var(--text-gray); font-weight: 600; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class='bx bx-info-circle' style="font-size: 18px;"></i>
                        Auto-Reminders run after the 20th of each month for all enabled bills.
                    </p>
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
