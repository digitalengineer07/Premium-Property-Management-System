<?php
// view_document.php - Secure File Proxy
require_once "db.php";
session_start();

$is_admin = isset($_SESSION['admin']);
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!$is_admin && !$user_id) {
    http_response_code(403);
    die("Forbidden: Not logged in.");
}

$file_param = $_GET['file'] ?? '';
$folder_param = $_GET['folder'] ?? 'documents';
$allowed_folders = ['documents', 'aadhaar', 'agreements', 'bills', 'queries'];
if (!in_array($folder_param, $allowed_folders, true) || !is_string($file_param) ||
    !preg_match('/^[A-Za-z0-9_. -]+\.(pdf|jpe?g|png)$/iD', $file_param)) {
    http_response_code(400);
    exit('Invalid document.');
}

$relative = 'uploads/' . $folder_param . '/' . $file_param;
if (!$is_admin) {
    $checks = match ($folder_param) {
        'aadhaar' => [['users', 'id', 'aadhaar_file']],
        'agreements' => [['users', 'id', 'agreement_document']],
        'queries' => [['queries', 'user_id', 'attachment']],
        'bills' => [['electricity', 'user_id', 'bill_file'], ['documents', 'user_id', 'file_path']],
        default => [
            ['documents', 'user_id', 'file_path'],
            ['users', 'id', 'electricity_document'],
            ['users', 'id', 'aadhaar_file'],
            ['users', 'id', 'agreement_document'],
        ],
    };
    $ownsDocument = false;
    foreach ($checks as [$table, $ownerColumn, $pathColumn]) {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM $table WHERE $ownerColumn=? AND BINARY $pathColumn=? LIMIT 1");
        if (!$stmt) continue;
        mysqli_stmt_bind_param($stmt, 'is', $user_id, $relative);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_get_result($stmt)->num_rows > 0) {
            $ownsDocument = true;
            mysqli_stmt_close($stmt);
            break;
        }
        mysqli_stmt_close($stmt);
    }
    if (!$ownsDocument) {
        http_response_code(404);
        exit('Document not found.');
    }
}

$directory = realpath(__DIR__ . '/uploads/' . $folder_param);
$physical_path = realpath(__DIR__ . '/' . $relative);
if (!$directory || !$physical_path || dirname($physical_path) !== $directory || !is_file($physical_path)) {
    http_response_code(404);
    exit('Document not found.');
}

// Determine mime type
$ext = strtolower(pathinfo($physical_path, PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];
$mime = $mime_types[$ext] ?? 'application/octet-stream';

// Serve the file
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $file_param . '"');
header('Content-Length: ' . filesize($physical_path));
readfile($physical_path);
exit;
?>
