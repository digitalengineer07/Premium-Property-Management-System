<?php
// admin/get-last-reading.php - Fetch last meter reading for a renter
require_once "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get the most recent electricity bill for this user
$stmt = mysqli_prepare($conn, "SELECT current_reading, month FROM electricity WHERE user_id = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Get user profile fields
$u_stmt = mysqli_prepare($conn, "SELECT base_reading, pending_adjustment, fixed_rent, fixed_maintenance FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$u_row = mysqli_fetch_assoc($u_res);

if ($row) {
    echo json_encode([
        'success' => true,
        'last_reading' => (float)$row['current_reading'],
        'last_month' => $row['month'],
        'pending_adjustment' => (float)($u_row['pending_adjustment'] ?? 0),
        'fixed_rent' => (float)($u_row['fixed_rent'] ?? 0),
        'fixed_maintenance' => (float)($u_row['fixed_maintenance'] ?? 0),
        'is_base' => false
    ]);
} else {
    echo json_encode([
        'success' => true,
        'last_reading' => (float)($u_row['base_reading'] ?? 0),
        'pending_adjustment' => (float)($u_row['pending_adjustment'] ?? 0),
        'fixed_rent' => (float)($u_row['fixed_rent'] ?? 0),
        'fixed_maintenance' => (float)($u_row['fixed_maintenance'] ?? 0),
        'is_base' => true
    ]);
}

mysqli_stmt_close($u_stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
