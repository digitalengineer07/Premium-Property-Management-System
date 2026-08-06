<?php
// admin/mark-paid.php
require_once "../db.php";
session_start();
require_once "../audit.php";
require_once "utils_mailer.php";
require_once "allocate_payment.php"; // The unified engine

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf'] ?? '')) {
    die("Security validation failed. Access denied.");
}

$type = $_POST['type'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$admin_id = $_SESSION['admin_id'] ?? 1;

$payment_mode = !empty($_POST['payment_mode']) ? $_POST['payment_mode'] : 'Cash';
$payment_date = $_POST['payment_date'] ?? date("Y-m-d");
$payment_time = $_POST['payment_time'] ?? date("H:i:s");

if (!in_array($type, ['rent', 'electricity', 'advance'])) {
    die("Invalid request");
}

/* 1. Fetch Bill details */
if ($type === 'rent') {
    $stmt = mysqli_prepare($conn, "SELECT user_id, month, rent_amount as amount, status FROM rent WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
} elseif ($type === 'electricity') {
    $stmt = mysqli_prepare($conn, "SELECT user_id, month, total_amount as amount, bill_file, status FROM electricity WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
} elseif ($type === 'advance') {
    $stmt = mysqli_prepare($conn, "SELECT id as user_id, 'Advance' as month, advance_payment as amount, '' as bill_file, 'Pending' as status FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bill = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$bill) {
    die("Record not found");
}

$bill_amount = (float)$bill['amount'];
$user_id = (int)$bill['user_id'];

// Get pending due for this specific bill
if ($type !== 'advance') {
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as total_paid FROM payments WHERE bill_type='$type' AND bill_id=$id");
    $already_paid = (float)(mysqli_fetch_assoc($qPaid)['total_paid'] ?? 0);
    $remaining_amount = max(0, $bill_amount - $already_paid);
} else {
    $remaining_amount = 0; // For advance, there is no "due" amount
}

$paid_amount = $remaining_amount; // Default
if (isset($_POST['paid_amount']) && is_numeric($_POST['paid_amount'])) {
    $paid_amount = (float)$_POST['paid_amount'];
}

if ($paid_amount <= 0) {
    $_SESSION['error'] = "Payment amount must be greater than zero.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit;
}


/* 2. Process via Unified Engine */
$sys_prefix = 'SYS_MAN_';
$usr_prefix = 'MAN-';
if ($payment_mode === 'Cash') { $sys_prefix = 'SYS_OFF_'; $usr_prefix = 'CASH-'; }
else if ($payment_mode === 'UPI') { $sys_prefix = 'SYS_UPI_'; $usr_prefix = 'UPI-'; }

$sys_tx_id = $sys_prefix . strtoupper(bin2hex(random_bytes(6)));
$transaction_id = $_POST['transaction_id'] ?? '';
if (empty(trim($transaction_id))) {
    $transaction_id = $usr_prefix . date('Ymd') . '-' . strtoupper(substr(md5($sys_tx_id), 0, 6));
} else {
    $transaction_id = trim($transaction_id);
}

if ($type === 'advance') {
    $vhash = generate_payment_hash($user_id, $paid_amount, $sys_tx_id);
    $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, payment_time, sys_tx_id, transaction_id, verification_hash) VALUES (?, 'advance', 0, 'Advance', ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iddssssss", $user_id, $paid_amount, $payment_mode, $paid_amount, $payment_date, $payment_time, $sys_tx_id, $transaction_id, $vhash);
    mysqli_stmt_execute($stmt);
    
    mysqli_query($conn, "UPDATE users SET advance_payment = advance_payment + $paid_amount WHERE id=$user_id");
} else {
    // Specific bill payment
    $excess = 0;
    $amount_to_apply = $paid_amount;
    
    if ($paid_amount > $remaining_amount) {
        $amount_to_apply = $remaining_amount;
        $excess = $paid_amount - $remaining_amount;
    }
    
    // Insert core payment
    if ($amount_to_apply > 0) {
        $vhash = generate_payment_hash($user_id, $amount_to_apply, $sys_tx_id);
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, payment_time, sys_tx_id, transaction_id, verification_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isisdsdsssss", $user_id, $type, $id, $bill['month'], $bill_amount, $payment_mode, $amount_to_apply, $payment_date, $payment_time, $sys_tx_id, $transaction_id, $vhash);
        mysqli_stmt_execute($stmt);
        
        // Use central engine to recalculate status mathematically
        recalculate_bill_status($conn, $type, $id);
    }
    
    // Deposit any excess into advance_payment automatically
    if ($excess > 0) {
        $vhash = generate_payment_hash($user_id, $excess, $sys_tx_id);
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, payment_time, sys_tx_id, transaction_id, verification_hash) VALUES (?, 'advance', 0, 'Advance', ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iddssssss", $user_id, $excess, $payment_mode, $excess, $payment_date, $payment_time, $sys_tx_id, $transaction_id, $vhash);
        mysqli_stmt_execute($stmt);
        
        mysqli_query($conn, "UPDATE users SET advance_payment = advance_payment + $excess WHERE id=$user_id");
    }
}

// Send Email Receipt
$qUser = mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id");
if ($uRow = mysqli_fetch_assoc($qUser)) {
    if (!empty($uRow['email'])) {
        $sub = "Payment Receipt - " . HOUSE_NAME;
        $msg = "Hello {$uRow['name']},<br><br>We have received a manual payment of Rs. {$paid_amount} for your {$bill['month']} " . ucfirst($type) . " bill.<br><br>Thank you!";
        @sendEmail($uRow['email'], $sub, $msg);
    }
}

$_SESSION['success'] = "Payment recorded and successfully processed by the auto-allocator.";
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit;
?>