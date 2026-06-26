<?php
require '../db.php';
$r = mysqli_query($conn, "SELECT DISTINCT month FROM rent");
while($row=mysqli_fetch_assoc($r)) echo $row['month'].', ';
