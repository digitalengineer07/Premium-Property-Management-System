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

$fileName = "";
$path = "";

if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['screenshot']['tmp_name'];
    $fileSize = $_FILES['screenshot']['size'];
    
    // Max 5MB
    if ($fileSize > 5 * 1024 * 1024) {
        die("File size exceeds 5MB limit.");
    }
    
    // Secure extension generation based on MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    
    if (!isset($allowedMimes[$mime])) {
        die("Invalid file format. Only JPG, PNG, WebP, and PDF are allowed.");
    }
    
    $ext = $allowedMimes[$mime];
    $fileName = time() . "_" . $user_id . "_payment." . $ext;
    $path = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $path)) {
        chmod($path, 0644);
    } else {
        $path = "";
    }
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
