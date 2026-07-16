<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'renter';
$_SESSION['theme'] = 'light-theme';
ob_start();
require 'renter/profile.php';
$html = ob_get_clean();
file_put_contents('render_out.html', $html);
echo "Rendered successfully!";
?>
