<?php
require_once "db.php";
$q = mysqli_query($conn, "SELECT id, username, name, must_change_password FROM users");
while($row = mysqli_fetch_assoc($q)) {
    print_r($row);
}
echo "done";
?>
