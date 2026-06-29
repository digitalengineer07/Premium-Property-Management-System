<?php
// renter/upload-profile-pic.php
require_once "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_pic'];
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'File too large. Maximum 5MB allowed.']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;
$db_path = 'uploads/profiles/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    exit;
}
chmod($filepath, 0644);

// Get old profile pic to delete
$stmt = mysqli_prepare($conn, "SELECT profile_pic FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$old_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Update database
$stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $db_path, $user_id);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($success) {
    // Delete old profile pic if it exists and is not default
    if ($old_data && $old_data['profile_pic'] && 
        $old_data['profile_pic'] !== 'assets/img/default-avatar.png' && 
        file_exists('../' . $old_data['profile_pic'])) {
        @unlink('../' . $old_data['profile_pic']);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile picture updated successfully',
        'profile_pic' => $db_path
    ]);
} else {
    @unlink($filepath); // Delete the uploaded file if database update failed
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
