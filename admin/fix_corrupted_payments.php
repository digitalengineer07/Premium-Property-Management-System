<?php
// admin/fix_corrupted_payments.php
$_SERVER['SERVER_NAME'] = 'localhost';
require_once "../db.php";
require_once "allocate_payment.php";

$q = mysqli_query($conn, "SELECT p.id as payment_id, p.*, e.amount as elec_part, (e.rent_amount + e.maintenance + e.dues + e.extra_charges) as rent_part FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type = 'elec_rent'");

$count = 0;
while($r = mysqli_fetch_assoc($q)) {
    $rent_part = (float)$r['rent_part'];
    $paid = (float)$r['paid_amount'];
    
    // Only fix if paid amount includes electricity part
    if ($paid > $rent_part) {
        $elec_excess = $paid - $rent_part;
        $p_id = $r['payment_id'];
        
        // Update original to just rent_part
        mysqli_query($conn, "UPDATE payments SET paid_amount=$rent_part, total_amount=$rent_part WHERE id=$p_id");
        
        // Insert new electricity payment
        $uid = $r['user_id'];
        $bid = $r['bill_id'];
        $month = $r['month'];
        $pmode = $r['payment_mode'];
        $tx = $r['transaction_id'];
        $stx = $r['sys_tx_id'];
        $vhash = generate_payment_hash($uid, $elec_excess, $stx);
        
        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id, verification_hash) VALUES ($uid, 'electricity', $bid, '$month', $elec_excess, '$pmode', $elec_excess, CURDATE(), '$tx', '$stx', '$vhash')");
        
        // Recalculate bill status
        recalculate_bill_status($conn, 'electricity', $bid);
        $count++;
    }
}
echo "Fixed $count corrupted payments.\n";

// Fix any payments that were completely inserted as elec_rent but actually were electricity payments
// For instance, the two separate rows with elec_rent.
// We can just find all electricity components that are Due, and if there is an overpayment in elec_rent, we already fixed it above.
// What about the cases where there were two rows in payments, one for 2496 and one for 11000, both elec_rent?
// If one row is 2496 (which is NOT > rent_part), it wasn't caught by the above!
// So let's write a second pass: for any electricity bill, check if there are multiple elec_rent payments, and if their SUM > rent_part.

$q2 = mysqli_query($conn, "SELECT e.id, e.amount as elec_part, (e.rent_amount + e.maintenance + e.dues + e.extra_charges) as rent_part, SUM(p.paid_amount) as total_rpaid FROM electricity e JOIN payments p ON e.id = p.bill_id WHERE p.bill_type='elec_rent' GROUP BY e.id HAVING total_rpaid > rent_part");
while ($r = mysqli_fetch_assoc($q2)) {
    $bid = $r['id'];
    $rent_part = (float)$r['rent_part'];
    $elec_part = (float)$r['elec_part'];
    $total_rpaid = (float)$r['total_rpaid'];
    
    // There are multiple payments that add up to > rent_part.
    // Let's find the one that matches elec_part exactly and switch it!
    $qp = mysqli_query($conn, "SELECT id, paid_amount FROM payments WHERE bill_id=$bid AND bill_type='elec_rent'");
    while ($pr = mysqli_fetch_assoc($qp)) {
        if (abs((float)$pr['paid_amount'] - $elec_part) < 0.01) {
            $pid = $pr['id'];
            mysqli_query($conn, "UPDATE payments SET bill_type='electricity' WHERE id=$pid");
            recalculate_bill_status($conn, 'electricity', $bid);
            $count++;
        }
    }
}
echo "Pass 2 Fixed $count total corrupted payments.\n";
?>
