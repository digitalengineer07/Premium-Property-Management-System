<?php
// admin/repair-ledger.php - Diagnostic & Repair Tool for Live Database
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$action = $_POST['action'] ?? '';

$message = '';

if ($action === 'repair_orphans') {
    // Delete payments that have no bill_type (caused by the old renter API bug)
    $stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE bill_type = '' OR bill_type IS NULL");
    mysqli_stmt_execute($stmt);
    $deleted = mysqli_stmt_affected_rows($stmt);
    $message .= "<div class='alert success'>Successfully deleted $deleted broken/orphaned payment records.</div>";
    mysqli_stmt_close($stmt);
}

if ($action === 'repair_duplicates') {
    // Find and delete duplicate advance payments (same user, same date, same amount)
    // We keep the one with the lowest ID (the original).
    $query = "
        DELETE p1 FROM payments p1
        INNER JOIN payments p2 
        WHERE p1.id > p2.id 
        AND p1.user_id = p2.user_id 
        AND p1.bill_type = 'advance' 
        AND p2.bill_type = 'advance'
        AND p1.paid_amount = p2.paid_amount 
        AND p1.payment_date = p2.payment_date
    ";
    if (mysqli_query($conn, $query)) {
        $deleted = mysqli_affected_rows($conn);
        $message .= "<div class='alert success'>Successfully removed $deleted duplicate advance payment records.</div>";
    } else {
        $message .= "<div class='alert error'>Failed to remove duplicates: " . mysqli_error($conn) . "</div>";
    }
}

if ($action === 'repair_adjustments') {
    // Fix any broken negative adjustment amounts
    $query = "UPDATE payments SET adjustment_amount = 0 WHERE adjustment_amount < 0 AND adjustment_type = 'None'";
    if (mysqli_query($conn, $query)) {
        $fixed = mysqli_affected_rows($conn);
        $message .= "<div class='alert success'>Successfully reset $fixed broken negative adjustments to zero.</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Repair Tool - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-dark); margin: 0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: var(--white); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        h1 { margin-top: 0; color: #0F172A; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background: #ECFDF5; color: #10B981; border: 1px solid #A7F3D0; }
        .alert.error { background: #FEF2F2; color: #EF4444; border: 1px solid #FECACA; }
        .card { border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .card-info h3 { margin: 0 0 8px 0; color: var(--text-dark); }
        .card-info p { margin: 0; color: var(--text-gray); font-size: 14px; line-height: 1.5; }
        .btn { background: #624BFF; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn:hover { background: #4F39F6; transform: translateY(-2px); }
        .btn.danger { background: #EF4444; }
        .btn.danger:hover { background: #DC2626; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Ledger Repair Tool</h1>
        <a href="index.php" style="color: var(--text-gray); text-decoration: none; font-weight: 500;">&larr; Back to Dashboard</a>
    </div>

    <p style="color: var(--text-gray); margin-bottom: 30px;">Use these diagnostic tools to clean up any database anomalies caused by old bugs before the system was patched.</p>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-info">
            <h3>1. Clean Orphaned / Blank Records</h3>
            <p>Deletes any payment records that have no bill type assigned (caused by the old renter portal bug).<br>These records are invisible to the main ledger but take up database space.</p>
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete all orphaned records?');">
            <input type="hidden" name="action" value="repair_orphans">
            <button type="submit" class="btn danger">Clean Orphans</button>
        </form>
    </div>

    <div class="card">
        <div class="card-info">
            <h3>2. Remove Duplicate Advance Payments</h3>
            <p>Scans the database for multiple advance payments recorded on the exact same date for the exact same amount by the exact same user, and safely deletes the duplicates while keeping the original.</p>
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to remove duplicate advance payments?');">
            <input type="hidden" name="action" value="repair_duplicates">
            <button type="submit" class="btn danger">Fix Duplicates</button>
        </form>
    </div>

    <div class="card">
        <div class="card-info">
            <h3>3. Reset Negative Adjustments</h3>
            <p>Resets any broken negative adjustment amounts to 0. (Legitimate manual deductions from advance wallets will not be affected).</p>
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to reset broken adjustments?');">
            <input type="hidden" name="action" value="repair_adjustments">
            <button type="submit" class="btn">Reset Adjustments</button>
        </form>
    </div>

</div>

</body>
</html>
