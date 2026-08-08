<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT bill_type, bill_id, COUNT(*) as c, MIN(id) as keep_id FROM payments WHERE transaction_id='Manual/Old' GROUP BY bill_type, bill_id HAVING c > 1");
$dups = 0;
while($r = mysqli_fetch_assoc($q)) {
    $bill_id = $r['bill_id'];
    $bill_type = $r['bill_type'];
    $keep = $r['keep_id'];
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id='Manual/Old' AND bill_type='$bill_type' AND bill_id=$bill_id AND id != $keep");
    $dups++;
}
echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h3>Cleanup Complete</h3>";
echo "Cleaned duplicate records for <b>$dups</b> bills successfully!<br><br>";
echo "Ab aap is <code>dedup_live.php</code> file ko server se delete kar sakte hain.";
echo "</div>";
?>
