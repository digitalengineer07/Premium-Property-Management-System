<?php
// admin/mark-paid.php
require_once "../db.php";
session_start();
require_once "../audit.php";
require_once "utils_mailer.php";

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

// Payment Mode Fields
$payment_mode = $_POST['payment_mode'] ?? 'Online';
$payment_date = $_POST['payment_date'] ?? date("Y-m-d");
$payment_time = $_POST['payment_time'] ?? date("H:i:s");
$datetime = $payment_date . " " . $payment_time;

if ($type !== 'advance' && $id <= 0) {
    die("Invalid request");
}

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
    // For advance, $id is the user_id
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

if ($type === 'advance') {
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as total_paid FROM payments WHERE bill_type='advance' AND user_id=" . $bill['user_id']);
} else {
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as total_paid FROM payments WHERE bill_type='$type' AND bill_id=$id");
}
$already_paid = (float)(mysqli_fetch_assoc($qPaid)['total_paid'] ?? 0);
$remaining_amount = max(0, $bill_amount - $already_paid);

$paid_amount = $remaining_amount; // Default
if (isset($_POST['paid_amount']) && is_numeric($_POST['paid_amount'])) {
    $paid_amount = (float)$_POST['paid_amount'];
}

/* 2. Calculate Adjustment */
if ($already_paid == 0) {
    $delta = $paid_amount - $bill_amount;
} else {
    $delta = $paid_amount;
}
$new_total_paid = $already_paid + $paid_amount;

$adj_type = null;
if ($delta > 0) $adj_type = 'extra';
elseif ($delta < 0) $adj_type = 'remaining';

/* 3. Record Payment */
$actual_bill_id = ($type === 'advance') ? 0 : $id; // For advance, there is no specific bill_id
$stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, adjustment_amount, adjustment_type, payment_date, payment_time, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isissddssssi", 
    $bill['user_id'], 
    $type, 
    $actual_bill_id, 
    $bill['month'], 
    $bill_amount, 
    $payment_mode, 
    $paid_amount, 
    $delta, 
    $adj_type, 
    $payment_date, 
    $payment_time,
    $admin_id
);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_error($stmt)) {
    die("Payment INSERT Error: " . mysqli_stmt_error($stmt));
}
mysqli_stmt_close($stmt);

/* 4. Update User's Pending Adjustment */
if ($delta != 0) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET pending_adjustment = pending_adjustment + ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $delta, $bill['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/* 5. Mark Bill as Paid or Partial */
$new_status = ($new_total_paid >= $bill_amount - 0.01) ? 'Paid' : 'Partial';
if ($type === 'rent') {
    $stmt = mysqli_prepare($conn, "UPDATE rent SET status=? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} elseif ($type === 'electricity') {
    $stmt = mysqli_prepare($conn, "UPDATE electricity SET status=? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Send Email Receipt
$qUser = mysqli_query($conn, "SELECT name, email FROM users WHERE id = " . $bill['user_id']);
if ($uRow = mysqli_fetch_assoc($qUser)) {
    if (!empty($uRow['email'])) {
        $details = [ucfirst($type) . " for " . $bill['month'] . " via " . $payment_mode];
        $pdf_path = ($type === 'electricity' && !empty($bill['bill_file'])) ? $bill['bill_file'] : null;
        send_payment_receipt_email($uRow['email'], $uRow['name'], $details, $paid_amount, $pdf_path);
    }
}

logAction($conn, "admin", $admin_id, "Marked {$type} id {$actual_bill_id} (user {$bill['user_id']}) as Paid via {$payment_mode} (Paid: ₹{$paid_amount})");

// Redirect
$redirect = ($type === 'rent' || $type === 'advance') ? "view-renter.php?id=" . $bill['user_id'] : "electricity-list.php";
if (isset($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'view-renter.php') !== false) {
        $redirect = "view-renter.php?id=" . $bill['user_id'];
    } elseif (strpos($_SERVER['HTTP_REFERER'], 'dashboard.php') !== false) {
        $redirect = "dashboard.php";
    }
}
header("Location: " . $redirect);
exit;

