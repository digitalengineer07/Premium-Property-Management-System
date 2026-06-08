<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
$q = mysqli_query($conn, "DESCRIBE users");
while($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . "\n";
}
?>
