<?php
require 'db.php';
mysqli_query($conn, "UPDATE electricity SET status='Paid', elec_status='Paid', rent_status='Paid' WHERE user_id=8 AND month='July 2026'");
mysqli_query($conn, "UPDATE rent SET status='Paid' WHERE user_id=8 AND month='July 2026'");
echo "Fixed DB records for user 8 July 2026.\n";
