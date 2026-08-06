<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require '../db.php';
require 'allocate_payment.php';

// Fix Anurag's payment from elec_rent to electricity
mysqli_query($conn, "UPDATE payments SET bill_type = 'electricity' WHERE id = 69 AND user_id = 7 AND paid_amount = 1688");

// Recalculate bill
recalculate_bill_status($conn, 'electricity', 77);
recalculate_bill_status($conn, 'rent', 77); // just to be safe
echo "Done";
