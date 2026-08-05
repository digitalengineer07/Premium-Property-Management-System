<?php
$_SERVER['SERVER_NAME'] = 'localhost';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'renter';
$_SESSION['room_no'] = '101';
ob_start();
require 'profile.php';
$html = ob_get_clean();
file_put_contents('profile_test_output.html', $html);
echo "Done";
