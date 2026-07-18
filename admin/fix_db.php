<?php
require_once "../db.php";
mysqli_query($conn, "ALTER TABLE payments DROP INDEX sys_tx_id");
echo mysqli_error($conn) ?: "Success";
?>
