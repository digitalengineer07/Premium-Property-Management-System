<?php
// admin/auto-reminders.php
require_once "../db.php";
require_once "utils_mailer.php";
session_start();

$provided_key = $_GET['key'] ?? '';
$is_cron = (!empty(CRON_KEY) && $provided_key === CRON_KEY);

if (!isset($_SESSION['admin']) && !$is_cron) {
    header("Location: login.php");
    exit;
}

// Allow manual run or triggered via task
$day = (int)date('d');
$is_force = isset($_GET['force']);
$error_msg = "";
$sent_count = 0;
$process_done = false;

if ($day < 20 && !$is_force) {
    $error_msg = "Automated reminders are scheduled to trigger only after the 20th of each month (Current Day: $day).";
} else {
    // 1. Process Rent Reminders
    $rent_q = mysqli_query($conn, "SELECT r.*, u.name, u.email FROM rent r JOIN users u ON r.user_id = u.id WHERE r.status = 'Due' AND r.reminder_status = 'Enabled'");
    while ($r = mysqli_fetch_assoc($rent_q)) {
        $check = mysqli_query($conn, "SELECT id FROM payment_reminders WHERE bill_id = {$r['id']} AND bill_type='Rent' AND sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY)");
        if (mysqli_num_rows($check) == 0 && !empty($r['email'])) {
            $details = ["Rent for " . $r['month']];
            if (send_payment_reminder_email($r['email'], $r['name'], $details, $r['rent_amount'])) {
                log_reminder($conn, $r['user_id'], $r['id'], 'Rent', $r['month'], 'Auto', 'Sent');
                $sent_count++;
            }
        }
    }

    // 2. Process Electricity Reminders
    $elec_q = mysqli_query($conn, "SELECT e.*, u.name, u.email FROM electricity e JOIN users u ON e.user_id = u.id WHERE e.status = 'Due' AND e.reminder_status = 'Enabled'");
    while ($e = mysqli_fetch_assoc($elec_q)) {
        $check = mysqli_query($conn, "SELECT id FROM payment_reminders WHERE bill_id = {$e['id']} AND bill_type='Electricity' AND sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY)");
        if (mysqli_num_rows($check) == 0 && !empty($e['email'])) {
            $details = ["Monthly Rent & Electricity Bill for " . $e['month']];
            $pdf_path = !empty($e['bill_file']) ? $e['bill_file'] : null;
            if (send_payment_reminder_email($e['email'], $e['name'], $details, $e['total_amount'], $pdf_path)) {
                log_reminder($conn, $e['user_id'], $e['id'], 'Electricity', $e['month'], 'Auto', 'Sent');
                $sent_count++;
            }
        }
    }
    $process_done = true;
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => $sent_count]);
    exit;
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto-Reminders Process | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <header class="header hide-desktop" style="height: 60px;"></header> <!-- Spacer for mobile toggle -->
    <div style="min-height: calc(100vh - 100px); display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 500px; width: 100%; text-align: center; padding: 40px; margin: 0 auto;">
            <?php if($error_msg): ?>
                <div style="background: rgba(245, 158, 11, 0.1); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class='bx bx-time-five' style="font-size: 40px; color: #F59E0B;"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 12px;">Not Yet Scheduled</h2>
                <p style="color: var(--text-gray); margin-bottom: 30px; line-height: 1.6;"><?php echo $error_msg; ?></p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="auto-reminders.php?force=1" class="btn-primary" style="justify-content: center; background: #624BFF;">
                        <i class='bx bx-play-circle'></i> Run Anyway (Force)
                    </a>
                    <a href="manage-reminders.php" class="btn-outline" style="justify-content: center;">
                        <i class='bx bx-arrow-back'></i> Cancel & Go Back
                    </a>
                </div>
            <?php else: ?>
                <div style="background: rgba(16, 185, 129, 0.1); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class='bx bx-check-double' style="font-size: 40px; color: #10B981;"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 12px;">Process Complete</h2>
                <p style="color: var(--text-gray); margin-bottom: 30px; line-height: 1.6;">
                    Successfully scanned the database and deployed notifications.
                </p>
                <div style="background: var(--bg-main); padding: 20px; border-radius: 16px; margin-bottom: 30px;">
                    <div style="font-size: 32px; font-weight: 800; color: var(--primary-purple);"><?php echo $sent_count; ?></div>
                    <div style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px;">Reminders Sent</div>
                </div>
                <a href="manage-reminders.php" class="btn-primary" style="width: 100%; justify-content: center;">
                    <i class='bx bx-arrow-back'></i> Back to Reminders
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
    }
</script>

</body>
</html>
