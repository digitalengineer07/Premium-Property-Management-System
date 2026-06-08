<?php
// admin/download-agreement.php
require_once "../db.php";
session_start();

// Accessible by Admin or the Resident themselves
if (!isset($_SESSION['admin']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid ID");

// Security: If renter is logged in (but not admin), they can only access their own document
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin'])) {
    if ($id !== (int)$_SESSION['user_id']) {
        die("Access Denied: You can only view your own documents.");
    }
}

$stmt = mysqli_prepare($conn, "SELECT agreement_document FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user_data || empty($user_data['agreement_document'])) {
    die("Agreement document not found for this user.");
}

$file_path = "../uploads/agreements/" . $user_data['agreement_document'];

if (file_exists($file_path)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    // Set headers to serve the file
    header("Content-Type: " . $mime_type);
    header("Content-Length: " . filesize($file_path));
    
    // Use inline for viewing in browser tab
    header("Content-Disposition: inline; filename=\"" . basename($file_path) . "\"");
    
    // Clear buffer and read file
    if (ob_get_length()) ob_clean();
    flush();
    readfile($file_path);
    exit;
} else {
    die("Error: File not found on the server. Please contact administrator.");
}
?>
