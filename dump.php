<?php
// Dump user-history.php
$cwd = getcwd();
chdir('admin');
$_GET['id'] = 9;
$_SESSION['admin'] = 'admin';
$_SESSION['admin_id'] = 1;
ob_start();
include "user-history.php";
$out = ob_get_clean();
chdir($cwd);
file_put_contents('dump_user_history.html', $out);
