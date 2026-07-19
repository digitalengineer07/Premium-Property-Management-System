<?php
$cwd = getcwd();
chdir('admin');
$_SESSION['admin'] = 'admin';
$_SESSION['admin_id'] = 1;
$_GET['id'] = 9;
ob_start();
include "view-renter.php";
$out = ob_get_clean();
chdir($cwd);
file_put_contents('view_renter_dump.txt', strip_tags($out));
