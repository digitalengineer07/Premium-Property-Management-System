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

// Find all numbers in the output
preg_match_all('/(?:&#8377;|Rs\.?|\$|₹)\s*([\d,]+\.?\d*)/i', $out, $matches);
print_r($matches[1]);
