<?php
session_start();
if (isset($_SESSION['login_log_id'])) {
    require_once "db.php";
    $log_id = (int)$_SESSION['login_log_id'];
    @mysqli_query($conn, "UPDATE login_logs SET logout_time = NOW() WHERE id = $log_id");
}
session_destroy();
header("Location: login.php");
exit;
