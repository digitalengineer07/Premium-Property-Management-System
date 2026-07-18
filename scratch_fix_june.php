<?php
require 'db.php';
// Mark user 8 June 2026 as Paid
mysqli_query($conn, "UPDATE electricity SET status='Paid', elec_status='Paid', rent_status='Paid' WHERE user_id=8 AND month='June 2026'");
echo "Fixed";
