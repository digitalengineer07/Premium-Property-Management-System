<?php
require_once "db.php";

$user_id = 6;
$today = '2026-07-19';

// Delete the payments made today
mysqli_query($conn, "DELETE FROM payments WHERE user_id = $user_id AND payment_date = '$today'");

// Delete payment notifications made today
mysqli_query($conn, "DELETE FROM payment_notifications WHERE user_id = $user_id AND DATE(created_at) = '$today'");

// Manually recalculate if function not found
function manual_recalc($conn, $type, $id) {
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='$type' AND bill_id=$id");
    $paid = (float)(mysqli_fetch_assoc($qPaid)['tp'] ?? 0);
    
    if ($type == 'rent') {
        $qBill = mysqli_query($conn, "SELECT rent_amount FROM rent WHERE id=$id");
        $total = (float)(mysqli_fetch_assoc($qBill)['rent_amount'] ?? 0);
        $status = ($paid >= $total) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Due');
        mysqli_query($conn, "UPDATE rent SET status='$status' WHERE id=$id");
    } else if ($type == 'electricity') {
        $qBill = mysqli_query($conn, "SELECT amount FROM electricity WHERE id=$id");
        $total = (float)(mysqli_fetch_assoc($qBill)['amount'] ?? 0);
        $status = ($paid >= $total) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Due');
        mysqli_query($conn, "UPDATE electricity SET elec_status='$status' WHERE id=$id");
    } else if ($type == 'elec_rent') {
        $qBill = mysqli_query($conn, "SELECT (rent_amount + maintenance) as total FROM electricity WHERE id=$id");
        $total = (float)(mysqli_fetch_assoc($qBill)['total'] ?? 0);
        $status = ($paid >= $total) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Due');
        mysqli_query($conn, "UPDATE electricity SET rent_status='$status' WHERE id=$id");
    }
}

$q = mysqli_query($conn, "SELECT id FROM rent WHERE user_id=$user_id");
while ($r = mysqli_fetch_assoc($q)) {
    manual_recalc($conn, 'rent', $r['id']);
}

$q = mysqli_query($conn, "SELECT id FROM electricity WHERE user_id=$user_id");
while ($r = mysqli_fetch_assoc($q)) {
    manual_recalc($conn, 'electricity', $r['id']);
    manual_recalc($conn, 'elec_rent', $r['id']);
}

// Also update electricity main status based on elec_status and rent_status
mysqli_query($conn, "UPDATE electricity SET status = CASE WHEN elec_status='Paid' AND rent_status='Paid' THEN 'Paid' WHEN elec_status='Due' AND rent_status='Due' THEN 'Due' ELSE 'Partial' END WHERE user_id=$user_id");

echo "Reset payments for user 6 today.";
?>
