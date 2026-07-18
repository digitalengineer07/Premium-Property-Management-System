<?php
require_once "c:/xampp/htdocs/renter-system/db.php";
mysqli_query($conn, "DELETE FROM payment_notifications WHERE id = 32");
echo "Deleted duplicate Pending row.\n";
