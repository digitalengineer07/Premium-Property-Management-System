<?php
require_once "db.php";
$q = mysqli_query($conn, "SELECT id, username, name, must_change_password FROM users WHERE username LIKE '%Rinku%' OR name LIKE '%Rinku%'");
if($q) {
    while($row = mysqli_fetch_assoc($q)) { print_r($row); }
} else { echo mysqli_error($conn); }
?>
