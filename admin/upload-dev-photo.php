<?php
ob_start();
require_once "../db.php";
session_start();

function outputJson($data) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['admin'])) {
    outputJson(['success' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    outputJson(['success' => false, 'message' => 'Invalid request']);
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    outputJson(['success' => false, 'message' => 'No file uploaded or upload error occurred. PHP Error Code: ' . ($_FILES['photo']['error'] ?? 'None')]);
}

$file = $_FILES['photo'];
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'application/octet-stream'];

if (!in_array($file['type'], $allowed_types)) {
    // If it comes through as octet-stream we can double check the extension, but typical blobs are png/jpeg
    outputJson(['success' => false, 'message' => 'Invalid file type. Found: ' . $file['type']]);
}

$target_path = "../assets/img/nikhil.png";

// The frontend Cropper.js already crops and processes the image into a high-quality PNG.
// We can safely move the uploaded blob directly, avoiding dependency on the PHP GD extension.
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    outputJson(['success' => true, 'message' => 'Photo updated successfully', 'url' => '../assets/img/nikhil.png?v=' . time()]);
} else {
    outputJson(['success' => false, 'message' => 'Failed to save the uploaded photo to the server.']);
}
