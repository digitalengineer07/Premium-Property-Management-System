<?php
require 'db.php';
$res = mysqli_query($conn, "SELECT e.id, e.user_id, e.month, e.amount, e.rent_amount, e.maintenance, e.extra_charges, e.dues 
FROM electricity e 
WHERE e.status = 'Paid'");

$inserted = 0;
while($r = mysqli_fetch_assoc($res)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type IN ('electricity', 'elec_rent')");
    if(mysqli_num_rows($chk) == 0) {
        $user_id = $r['user_id'];
        $month = $r['month'];
        $total = $r['amount'] + $r['rent_amount'] + $r['maintenance'] + $r['extra_charges'] + $r['dues'];
        
        // Construct a payment date (10th of the bill month)
        $date_str = date('Y-m-10', strtotime('1 ' . $month));
        $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Insert into payments
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES (?, 'elec_rent', ?, ?, ?, 'Cash/Offline', ?, ?, 'Manual/Old', ?)");
        mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total, $total, $date_str, $sys_tx_id);
        if(mysqli_stmt_execute($stmt)) {
            $inserted++;
            // Update the paid_date in electricity table just to be safe
            mysqli_query($conn, "UPDATE electricity SET paid_date = '$date_str' WHERE id = $bill_id");
        }
    }
}
echo "Successfully generated $inserted missing payment records to restore backward compatibility.\n";
?>
