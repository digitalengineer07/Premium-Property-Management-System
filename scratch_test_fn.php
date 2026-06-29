<?php
require_once "db.php";
$_SESSION['user_id'] = 2; // rinku202
require "renter/fetch_notifications.php";
echo "Unread count: $unread_count\n";
unlink('scratch_test_fn.php');
?>
