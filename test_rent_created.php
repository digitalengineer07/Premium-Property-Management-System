<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
$res = mysqli_query($conn, "SELECT id, month, due_date, created_at FROM rent WHERE id = 5");
$row = mysqli_fetch_assoc($res);
print_r($row);
?>
