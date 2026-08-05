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

$file_param = basename($_GET['file'] ?? ''); // Prevent directory traversal
$folder_param = basename($_GET['folder'] ?? 'documents');

$allowed_folders = ['documents', 'aadhaar', 'agreements', 'bills', 'queries'];
if (!in_array($folder_param, $allowed_folders) || empty($file_param)) {
    http_response_code(400);
    die("Bad Request: Invalid folder or no file specified.");
}

$physical_path = __DIR__ . '/uploads/' . $folder_param . '/' . $file_param;

if (!file_exists($physical_path)) {
    http_response_code(404);
    die("Not Found: File does not exist.");
}

// Ownership Verification
if (!$is_admin) {
    if ($folder_param === 'aadhaar' || strpos($file_param, 'aadhar_') === 0) {
        $q = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id AND aadhaar_file LIKE '%$file_param%'");
        if (mysqli_num_rows($q) == 0) {
            http_response_code(403);
            die("Forbidden: You do not have permission to view this Aadhar card.");
        }
    } else if ($folder_param === 'agreements') {
        $q = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id AND agreement_document LIKE '%$file_param%'");
        if (mysqli_num_rows($q) == 0) {
            http_response_code(403);
            die("Forbidden: You do not have permission to view this agreement.");
        }
    } else if ($folder_param === 'documents' || $folder_param === 'bills') {
        $q = mysqli_query($conn, "SELECT id FROM documents WHERE user_id = $user_id AND file_path LIKE '%$file_param%'");
        $q2 = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id AND electricity_document LIKE '%$file_param%'");
        if (mysqli_num_rows($q) == 0 && mysqli_num_rows($q2) == 0) {
            http_response_code(403);
            die("Forbidden: You do not have permission to view this document.");
        }
    } else if ($folder_param === 'queries') {
        $q = mysqli_query($conn, "SELECT id FROM queries WHERE user_id = $user_id AND attachment LIKE '%$file_param%'");
        if (mysqli_num_rows($q) == 0) {
            http_response_code(403);
            die("Forbidden: You do not have permission to view this query attachment.");
        }
    }
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
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $file_param . '"');
header('Content-Length: ' . filesize($physical_path));
readfile($physical_path);
exit;
?>
