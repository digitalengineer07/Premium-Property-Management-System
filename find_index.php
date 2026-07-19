<?php
$cwd = getcwd();
chdir('admin');
$_SESSION['admin'] = 'admin';
$_SESSION['admin_id'] = 1;
ob_start();
include "index.php";
$out = ob_get_clean();
chdir($cwd);
if (strpos(str_replace(',','', $out), '76360') !== false) {
    echo "YES in index.php\n";
} else {
    echo "NO in index.php\n";
}
