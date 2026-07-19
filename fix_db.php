<?php
require_once "db.php";
mysqli_query($conn, "UPDATE payments SET bill_type='electricity' WHERE id=92 AND bill_id=82 AND paid_amount=712.00");
mysqli_query($conn, "UPDATE electricity SET rent_status='Due', elec_status='Partial' WHERE id=82");
echo "Done.";
