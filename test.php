<?php
require 'db.php';
$result = mysqli_query($conn, "SELECT id, name, profile_pic FROM users WHERE id=1");
print_r(mysqli_fetch_assoc($result));
?>
