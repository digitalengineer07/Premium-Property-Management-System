<?php
require_once "db.php";
$q = mysqli_query($conn, "SELECT id, username, name, must_change_password FROM users WHERE must_change_password = 1");
if($q) {
    while($row = mysqli_fetch_assoc($q)) { print_r($row); }
} else { echo mysqli_error($conn); }
?>
