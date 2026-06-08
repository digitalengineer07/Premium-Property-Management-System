<?php
// admin/reset-password.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed']);
        exit;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $new_pass = $_POST['new_password'] ?? '';

    if ($id <= 0 || empty($new_pass)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $hashed, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
}
?>
