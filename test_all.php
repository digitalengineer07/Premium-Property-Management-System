<?php
require_once "db.php";

$q = mysqli_query($conn, "SELECT id, name FROM users");
while ($u = mysqli_fetch_assoc($q)) {
    $uid = $u['id'];
    $name = $u['name'];
    
    // elec due
    $res = mysqli_query($conn, "SELECT SUM(amount) as elec_sum FROM electricity WHERE user_id = $uid AND (elec_status IN ('Due', 'Partial') OR elec_status = '' OR elec_status IS NULL) AND status IN ('Due', 'Partial')");
    $elec_sum = mysqli_fetch_assoc($res)['elec_sum'] ?? 0;
    
    $res = mysqli_query($conn, "SELECT SUM(paid_amount) as elec_paid FROM payments WHERE user_id = $uid AND bill_type='electricity'");
    $elec_paid = mysqli_fetch_assoc($res)['elec_paid'] ?? 0;
    
    // rent due
    $res = mysqli_query($conn, "SELECT SUM(rent_amount + maintenance + dues + extra_charges) as rent_sum FROM electricity WHERE user_id = $uid AND (rent_status IN ('Due', 'Partial') OR rent_status = '' OR rent_status IS NULL) AND status IN ('Due', 'Partial')");
    $rent_sum = mysqli_fetch_assoc($res)['rent_sum'] ?? 0;
    
    $res = mysqli_query($conn, "SELECT SUM(paid_amount) as rent_paid FROM payments WHERE user_id = $uid AND bill_type='elec_rent'");
    $rent_paid = mysqli_fetch_assoc($res)['rent_paid'] ?? 0;
    
    $res = mysqli_query($conn, "SELECT pending_adjustment FROM users WHERE id = $uid");
    $adj = mysqli_fetch_assoc($res)['pending_adjustment'] ?? 0;
    
    $e_due = max(0, $elec_sum - $elec_paid);
    $r_due = max(0, $rent_sum - $rent_paid);
    
    $total = $e_due + $r_due - $adj;
    
    if ($total > 0 || $total < 0) {
        echo "[$uid] $name - Total: $total (Elec Due: $e_due, Rent Due: $r_due, Adj: $adj)\n";
    }
}
echo "Done.\n";
