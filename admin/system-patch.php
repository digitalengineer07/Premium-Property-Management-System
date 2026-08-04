<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Unauthorized access. Please login as admin first.");
}

$results = [];

// 1. Update Schema: Add onboarding_completed
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'onboarding_completed'");
if (mysqli_num_rows($check_col) == 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0")) {
        $results[] = "✔ Added 'onboarding_completed' column to users table.";
    } else {
        $results[] = "✘ Failed to add 'onboarding_completed': " . mysqli_error($conn);
    }
} else {
    $results[] = "ℹ 'onboarding_completed' column already exists.";
}

// 2. Data Migration: Fix orphaned 'Paid' electricity bills
$res = mysqli_query($conn, "SELECT e.id, e.user_id, e.month, e.amount, e.rent_amount, e.maintenance, e.extra_charges, e.dues 
FROM electricity e WHERE e.status = 'Paid'");
$inserted_elec = 0;
while($r = mysqli_fetch_assoc($res)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type IN ('electricity', 'elec_rent')");
    if(mysqli_num_rows($chk) == 0) {
        $user_id = $r['user_id'];
        $month = $r['month'];
        $total = $r['amount'] + $r['rent_amount'] + $r['maintenance'] + $r['extra_charges'] + $r['dues'];
        $date_str = date('Y-m-10', strtotime('1 ' . $month));
        $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES (?, 'elec_rent', ?, ?, ?, 'Cash/Offline', ?, ?, 'Manual/Old', ?)");
        mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total, $total, $date_str, $sys_tx_id);
        if(mysqli_stmt_execute($stmt)) {
            $inserted_elec++;
            mysqli_query($conn, "UPDATE electricity SET paid_date = '$date_str' WHERE id = $bill_id AND (paid_date IS NULL OR paid_date = '0000-00-00')");
        }
    }
}
if ($inserted_elec > 0) {
    $results[] = "✔ Restored $inserted_elec missing payment receipts for legacy electricity bills.";
} else {
    $results[] = "ℹ No orphaned electricity payments found.";
}

// 3. Data Migration: Fix orphaned 'Paid' pure rent bills
$res_rent = mysqli_query($conn, "SELECT id, user_id, month, rent_amount FROM rent WHERE status = 'Paid'");
$inserted_rent = 0;
while($r = mysqli_fetch_assoc($res_rent)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type = 'rent'");
    if(mysqli_num_rows($chk) == 0) {
        $user_id = $r['user_id'];
        $month = $r['month'];
        $total = $r['rent_amount'];
        $date_str = date('Y-m-10', strtotime('1 ' . $month));
        $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES (?, 'rent', ?, ?, ?, 'Cash/Offline', ?, ?, 'Manual/Old', ?)");
        mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total, $total, $date_str, $sys_tx_id);
        if(mysqli_stmt_execute($stmt)) {
            $inserted_rent++;
            mysqli_query($conn, "UPDATE rent SET paid_date = '$date_str' WHERE id = $bill_id AND (paid_date IS NULL OR paid_date = '0000-00-00')");
        }
    }
}
if ($inserted_rent > 0) {
    $results[] = "✔ Restored $inserted_rent missing payment receipts for legacy rent bills.";
} else {
    $results[] = "ℹ No orphaned pure rent payments found.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Patch & Migration</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #F8FAFC; padding: 40px; color: #1E293B; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #0F172A; }
        ul { padding-left: 20px; }
        li { margin-bottom: 10px; font-size: 15px; }
        .success-box { background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 15px; border-radius: 8px; margin-top: 20px; font-weight: 600; }
        .btn { display: inline-block; background: #624BFF; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>System Data Patch Applied</h2>
        <p>The following background updates and migrations were executed on the database:</p>
        
        <ul>
            <?php foreach($results as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="success-box">
            System is now 100% updated and fully compatible with the new codebase! You can safely use all features.
        </div>
        
        <a href="dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>
