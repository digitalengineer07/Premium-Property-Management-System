<?php
require_once '../db.php';
require_once 'allocate_payment.php';

// 1. Find all SYS_ADV negative payments to refund to security deposit
$q = mysqli_query($conn, "SELECT user_id, paid_amount FROM payments WHERE transaction_id LIKE 'SYS_ADV_%' AND bill_type='advance' AND paid_amount < 0");

$refunds = [];
while($r = mysqli_fetch_assoc($q)) {
    $uid = $r['user_id'];
    $amt = abs($r['paid_amount']);
    $refunds[$uid] = ($refunds[$uid] ?? 0) + $amt;
}

// Apply refunds
foreach($refunds as $uid => $amt) {
    mysqli_query($conn, "UPDATE users SET security_deposit = security_deposit + $amt WHERE id = $uid");
    echo "Refunded $amt to security_deposit for user $uid\n";
}

// 2. Find affected bill IDs before deleting payments
$q2 = mysqli_query($conn, "SELECT DISTINCT bill_id, bill_type FROM payments WHERE transaction_id LIKE 'SYS_ADV_%' AND bill_id > 0 AND bill_type != 'advance'");
$affected_bills = [];
while($r2 = mysqli_fetch_assoc($q2)) {
    $affected_bills[] = $r2;
}

// 3. Delete the SYS_ADV payments
mysqli_query($conn, "DELETE FROM payments WHERE transaction_id LIKE 'SYS_ADV_%'");
echo "Deleted SYS_ADV payments\n";

// 4. Recalculate bill statuses
foreach($affected_bills as $b) {
    if ($b['bill_type'] == 'electricity' || $b['bill_type'] == 'elec_rent') {
        recalculate_bill_status($conn, 'electricity', $b['bill_id']);
    }
}
echo "Recalculated bill statuses.\n";
?>
