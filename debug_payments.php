<?php
require 'db.php';
$user_id = 7;

$q = mysqli_query($conn, "SELECT * FROM payments WHERE user_id = $user_id ORDER BY id ASC");
echo "--- PAYMENTS ---\n";
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
