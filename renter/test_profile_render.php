<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['role'] = 'renter';
$_SESSION['room_no'] = '101';
$_SESSION['csrf'] = 'dummy';

ob_start();
try {
    require 'profile.php';
} catch (Exception $e) {
    echo $e->getMessage();
}
$html = ob_get_clean();
file_put_contents('profile_output.html', $html);
echo "Length: " . strlen($html);
