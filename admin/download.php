<?php
// admin/download.php
require_once "../db.php";
session_start();
require_once "../audit.php";
if (!isset($_SESSION['admin_id'])) { header("HTTP/1.1 403 Forbidden"); exit; }

$type = $_GET['type'] ?? '';
if ($type === 'aadhaar') {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($user_id <= 0) { header("HTTP/1.1 400 Bad Request"); exit; }
    $stmt = mysqli_prepare($conn, "SELECT aadhaar_file, name, username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if (!$row || empty($row['aadhaar_file'])) { header("HTTP/1.1 404 Not Found"); exit; }
    $rel = $row['aadhaar_file'];
    $logLabel = "Viewed Aadhaar for user_id {$user_id}";
} elseif ($type === 'bill') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { header("HTTP/1.1 400 Bad Request"); exit; }
    $stmt = mysqli_prepare($conn, "SELECT bill_file, user_id FROM electricity WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if (!$row || empty($row['bill_file'])) { header("HTTP/1.1 404 Not Found"); exit; }
    $rel = $row['bill_file'];
    $logLabel = "Viewed bill for electricity id {$id}";
} elseif ($type === 'electricity') {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($user_id <= 0) { header("HTTP/1.1 400 Bad Request"); exit; }
    $stmt = mysqli_prepare($conn, "SELECT electricity_document, name, username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if (!$row || empty($row['electricity_document'])) { header("HTTP/1.1 404 Not Found"); exit; }
    $rel = $row['electricity_document'];
    $logLabel = "Viewed Electricity Document for user_id {$user_id}";
} else {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

/* sanitize path: must be inside project and start with uploads/ */
$rel = str_replace(['../','./'], '', $rel);
$full = __DIR__ . "/../" . $rel;
if (!file_exists($full)) { header("HTTP/1.1 404 Not Found"); exit; }

/* log admin viewing */
logAction($conn, "admin", $_SESSION['admin_id'], $logLabel);

/* serve file with correct headers */
$mime = mime_content_type($full);
$basename = basename($full);
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
