<?php
require_once "../../db.php";

$allowed_origins = ['https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authHeader = '';
if (isset($_SERVER['Authorization'])) {
    $authHeader = trim($_SERVER["Authorization"]);
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER["HTTP_AUTHORIZATION"]);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    if (isset($requestHeaders['Authorization'])) {
        $authHeader = trim($requestHeaders['Authorization']);
    }
}

$token = '';
if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
} else if (isset($_POST['token'])) {
    $token = trim($_POST['token']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. No token provided.", "post" => $_POST, "files" => $_FILES]);
    exit;
}
$decoded = json_decode(base64_decode($token), true);

if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'resident') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid token."]);
    exit;
}

$user_id = (int)$decoded['id'];

// Check if file is uploaded
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error occurred."]);
    exit;
}

$file = $_FILES['document'];
$fileName = basename($file['name']);
$fileSize = $file['size'];
$fileTmpName  = $file['tmp_name'];

// Validate File Size (Max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "File size exceeds 5MB limit."]);
    exit;
}

// Validate File Extension
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExts = array('jpg', 'jpeg', 'png', 'pdf');

if (!in_array($fileExt, $allowedExts)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid file format. Only JPG, PNG, and PDF are allowed."]);
    exit;
}

// Ensure Upload Directory Exists
$uploadDir = "../../uploads/documents/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename to avoid overwrites
$newFileName = "aadhar_" . $user_id . "_" . time() . "." . $fileExt;
$destPath = $uploadDir . $newFileName;
$dbPath = "uploads/documents/" . $newFileName; // Path relative to root for DB

if (move_uploaded_file($fileTmpName, $destPath)) {
    try {
        // Update database
        $stmt = mysqli_prepare($conn, "UPDATE users SET aadhaar_file = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $dbPath, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Document uploaded successfully.",
                "file_path" => $dbPath
            ]);
        } else {
            throw new Exception("Database update failed.");
        }
        mysqli_stmt_close($stmt);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update record", "error" => $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
}
?>
