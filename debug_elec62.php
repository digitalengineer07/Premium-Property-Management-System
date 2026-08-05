<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM electricity WHERE id = 62");
print_r(mysqli_fetch_assoc($q));
?>
