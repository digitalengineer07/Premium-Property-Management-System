<?php
require_once "db.php";
mysqli_query($conn, "UPDATE announcements SET title = REPLACE(title, '&#039;', '\''), message = REPLACE(message, '&#039;', '\'')");
echo "Fixed DB";
?>
