<?php
require_once "../db.php";
mysqli_query($conn, "DELETE FROM payment_notifications WHERE id = 34");
echo "Deleted ID 34";
?>
