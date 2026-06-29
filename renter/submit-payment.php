<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'];
$bill_id = (int)$_POST['bill_id'];
$amount = (float)$_POST['amount'];
$ref = trim($_POST['upi_txn_ref'] ?? '');

/* Upload screenshot */
$uploadDir = "../uploads/payments/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = time() . "_" . basename($_FILES['screenshot']['name']);
$path = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $path)) {
    chmod($path, 0644);
}

/* Insert into payment_requests */
$stmt = mysqli_prepare($conn,
    "INSERT INTO payment_requests 
     (user_id, bill_type, bill_id, amount, upi_txn_ref, screenshot)
     VALUES (?,?,?,?,?,?)"
);

mysqli_stmt_bind_param($stmt, "isidss",
    $user_id, $type, $bill_id, $amount, $ref, $path
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

/* Redirect */
header("Location: dashboard.php?payment=sent");
exit;
