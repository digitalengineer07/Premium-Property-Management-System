<?php
require 'db.php';

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h3>Database Sync & Cleanup</h3>";

// 1. Add missing column if it doesn't exist
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'move_out_date'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN move_out_date DATE NULL DEFAULT NULL AFTER status");
    echo "✅ Successfully added missing 'move_out_date' column for Move-out feature.<br><br>";
} else {
    echo "✅ 'move_out_date' column already exists.<br><br>";
}

// 2. Remove duplicate legacy payments
$q = mysqli_query($conn, "SELECT bill_type, bill_id, COUNT(*) as c, MIN(id) as keep_id FROM payments WHERE transaction_id='Manual/Old' GROUP BY bill_type, bill_id HAVING c > 1");
$dups = 0;
while($r = mysqli_fetch_assoc($q)) {
    $bill_id = $r['bill_id'];
    $bill_type = $r['bill_type'];
    $keep = $r['keep_id'];
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id='Manual/Old' AND bill_type='$bill_type' AND bill_id=$bill_id AND id != $keep");
    $dups++;
}

if ($dups > 0) {
    echo "✅ Cleaned duplicate records for <b>$dups</b> bills successfully!<br><br>";
} else {
    echo "✅ No duplicate payment records found.<br><br>";
}

echo "<h3>All done!</h3>";
echo "Ab aap is <code>dedup_live.php</code> file ko server se delete kar sakte hain.";
echo "</div>";
?>
