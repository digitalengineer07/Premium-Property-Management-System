<?php
require 'db.php';
mysqli_query($conn, "UPDATE users SET profile_pic = NULL WHERE id=1");
?>
