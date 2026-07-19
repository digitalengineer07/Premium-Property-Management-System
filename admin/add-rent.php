<?php
// admin/add-rent.php - Unified SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) {
    header("Location: manage-renters.php");
    exit;
}
$error = "";

// Fetch renter info
$u_stmt = mysqli_prepare($conn, "SELECT name, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$user = mysqli_fetch_assoc($u_res);
mysqli_stmt_close($u_stmt);

if (!$user) {
    header("Location: manage-renters.php");
    exit;
}

if (isset($_POST['save'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $month = $_POST['month'];
        $amount = $_POST['rent_amount'];
        $status = $_POST['status'];

    $i_stmt = mysqli_prepare($conn, "INSERT INTO rent (user_id, month, rent_amount, status, due_date) VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 10 DAY))");
    mysqli_stmt_bind_param($i_stmt, "isss", $user_id, $month, $amount, $status);
    mysqli_stmt_execute($i_stmt);
    mysqli_stmt_close($i_stmt);

    if ($status === 'Due') {
        require_once "utils_mailer.php";
        $email_query = mysqli_query($conn, "SELECT email, name FROM users WHERE id = $user_id");
        if ($email_query && mysqli_num_rows($email_query) > 0) {
            $u_data = mysqli_fetch_assoc($email_query);
            if (!empty($u_data['email'])) {
                send_new_bill_notification($u_data['email'], $u_data['name'], $month, $amount);
            }
        }
        $msg_safe = mysqli_real_escape_string($conn, "A new rent bill for $month (₹" . number_format((float)$amount, 2) . ") has been assigned to you.");
        mysqli_query($conn, "INSERT INTO app_notifications (user_id, title, message, type) VALUES ($user_id, 'New Bill Assigned', '$msg_safe', 'bill')");
    }

    // --- NEW: Enterprise Auto-Credit Application ---
    $qAdv = mysqli_query($conn, "SELECT advance_payment FROM users WHERE id = $user_id");
    if ($qAdv && $rowAdv = mysqli_fetch_assoc($qAdv)) {
        $adv = (float)$rowAdv['advance_payment'];
        if ($adv > 0) {
            // Temporarily zero the advance so the allocator can redistribute it without doubling
            mysqli_query($conn, "UPDATE users SET advance_payment = 0 WHERE id = $user_id");
            require_once "allocate_payment.php";
            $sys_id = 'SYS-CREDIT-' . time() . '-' . rand(100,999);
            allocate_bulk_payment($conn, $user_id, $adv, 'Advance Credit', $sys_id, $sys_id, null, true);
        }
    }
    // -----------------------------------------------

    header("Location: view-renter.php?id=$user_id");
    exit;
    }
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Add Rent Record | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>New Rent Record</h1>
        <p>Record monthly rent for <?php echo htmlspecialchars($user['name']); ?></p>
        <?php if (!empty($error)): ?>
            <div style="background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-top: 10px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid-70 animate-up" style="margin-top: 30px; grid-template-columns: 1fr;">
        <div style="max-width: 600px; margin: 0 auto; width: 100%;">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Rent Transaction</h2>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                    <div class="form-group">
                        <label>Month (e.g. March 2026)</label>
                        <input type="text" name="month" required placeholder="March 2026">
                    </div>

                    <div class="form-group">
                        <label>Rent Amount</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                            <input type="number" name="rent_amount" required placeholder="0" style="padding-left: 40px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Due">Due</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <button type="submit" name="save" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px; margin-top: 10px;">
                        <i class='bx bx-save'></i> Save Rent Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>
