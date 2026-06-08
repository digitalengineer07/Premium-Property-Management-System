<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
$_SESSION['admin'] = 'Admin';
require 'dashboard.php';
?>
