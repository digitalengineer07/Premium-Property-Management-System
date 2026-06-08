<?php
// audit.php - Audit Logging System
if (!function_exists('logAction')) {
    function logAction($conn, $user_type, $user_id, $action) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_type, user_id, action, ip_address) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siss", $user_type, $user_id, $action, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
