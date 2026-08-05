<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status = (int)($_POST['status'] ?? 1);
    
    // Ensure column exists
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0");
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET onboarding_completed = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $status, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['admin_success'] = "Onboarding status updated manually.";
    } else {
        $_SESSION['admin_error'] = "Failed to update onboarding status.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: view-renter.php?id=" . $user_id);
    exit;
}
