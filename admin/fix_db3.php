<?php
// fix_db3.php
$_SERVER['SERVER_NAME'] = 'localhost';
require_once "../db.php";
require_once "allocate_payment.php";

$q2 = mysqli_query($conn, "SELECT e.id, e.user_id, e.month, e.amount as elec_part, (e.rent_amount + e.maintenance + e.dues + e.extra_charges) as rent_part, SUM(p.paid_amount) as total_rpaid FROM electricity e JOIN payments p ON e.id = p.bill_id WHERE p.bill_type='elec_rent' GROUP BY e.id HAVING total_rpaid > rent_part + 0.01");

$count = 0;
while ($r = mysqli_fetch_assoc($q2)) {
    $bid = $r['id'];
    $rent_part = (float)$r['rent_part'];
    $total_rpaid = (float)$r['total_rpaid'];
    
    $excess = $total_rpaid - $rent_part;
    
    // We need to reduce elec_rent payments by $excess, and create an electricity payment of $excess.
    // Let's find the smallest elec_rent payments that we can convert.
    $qp = mysqli_query($conn, "SELECT id, paid_amount, transaction_id, sys_tx_id, payment_mode FROM payments WHERE bill_id=$bid AND bill_type='elec_rent' ORDER BY paid_amount ASC");
    
    while ($pr = mysqli_fetch_assoc($qp)) {
        if ($excess <= 0.01) break;
        
        $pid = $pr['id'];
        $paid = (float)$pr['paid_amount'];
        
        if ($paid <= $excess + 0.01) {
            // Convert entirely
            mysqli_query($conn, "UPDATE payments SET bill_type='electricity' WHERE id=$pid");
            $excess -= $paid;
        } else {
            // Split it
            $new_rent = $paid - $excess;
            mysqli_query($conn, "UPDATE payments SET paid_amount=$new_rent, total_amount=$new_rent WHERE id=$pid");
            
            $uid = $r['user_id'];
            $month = $r['month'];
            $pmode = $pr['payment_mode'];
            $tx = $pr['transaction_id'];
            $stx = $pr['sys_tx_id'];
            $vhash = generate_payment_hash($uid, $excess, $stx);
            mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id, verification_hash) VALUES ($uid, 'electricity', $bid, '$month', $excess, '$pmode', $excess, CURDATE(), '$tx', '$stx', '$vhash')");
            $excess = 0;
        }
    }
    recalculate_bill_status($conn, 'electricity', $bid);
    $count++;
}
echo "Fixed $count combined bills with excess rent allocated.";
?>
