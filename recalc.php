<?php
require 'db.php';
require 'admin/allocate_payment.php';

recalculate_bill_status($conn, 'electricity', 68);
echo "Recalculated 68";
?>
