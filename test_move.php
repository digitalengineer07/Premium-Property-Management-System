<?php
session_start();
$_SESSION['admin'] = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['user_id'] = 401; // Just to see error without breaking anything, let's use an invalid id if we want to bypass, or use an id that we won't actually move out.
// Let's use an id that exists. To bypass the actual MOVE_OUT, I will comment out the `mysqli_stmt_execute($stmt)` in `ajax_move_out.php` temporarily!
?>
