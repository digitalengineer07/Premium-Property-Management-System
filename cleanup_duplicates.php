<?php
require 'db.php';

// I need to delete the duplicates I just inserted.
// The duplicates have transaction_id = 'Legacy Paid Bill'

$q = mysqli_query($conn, "DELETE FROM payments WHERE transaction_id = 'Legacy Paid Bill'");
echo "Deleted " . mysqli_affected_rows($conn) . " duplicate backfilled payments.\n";

// Let me also rewrite the backfill script correctly so it doesn't insert duplicates for 'monthly' payments.
