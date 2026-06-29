<?php
require 'db.php';
// For older fully paid bills, mark both paid
mysqli_query($conn, "UPDATE electricity SET elec_status='Paid', rent_status='Paid' WHERE status='Paid' AND id != 63");
// For ID 63 (where user only paid electricity), mark elec paid and rent due
mysqli_query($conn, "UPDATE electricity SET elec_status='Paid', rent_status='Due', status='Partial' WHERE id=63");

echo "Cleaned up test data.\n";
unlink('scratch_check.php');
?>
