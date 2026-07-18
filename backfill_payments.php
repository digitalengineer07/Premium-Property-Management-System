<?php
require 'db.php';

// Backfill for electricity bills
$q_elec = mysqli_query($conn, "
    SELECT e.* 
    FROM electricity e 
    WHERE e.status = 'Paid' 
    AND NOT EXISTS (
        SELECT 1 FROM payments p 
        WHERE p.user_id = e.user_id 
        AND (
            (p.bill_type IN ('electricity', 'total', 'elec_rent') AND p.bill_id = e.id)
            OR 
            (p.bill_type = 'monthly' AND p.month = e.month)
        )
    )
");

$elec_count = 0;
while ($e = mysqli_fetch_assoc($q_elec)) {
    $user_id = $e['user_id'];
    $bill_id = $e['id'];
    $month = $e['month'];
    $total_amount = $e['total_amount'] ?? ($e['amount'] + $e['rent_amount'] + $e['maintenance'] + $e['dues']);
    
    // If we have created_at, use it, else current date
    $payment_date = !empty($e['created_at']) ? date('Y-m-d', strtotime($e['created_at'] . ' + 1 day')) : date('Y-m-d');
    
    $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, adjustment_amount, adjustment_type, payment_date, transaction_id) VALUES (?, 'electricity', ?, ?, ?, 'Manual', ?, 0, '', ?, 'SYS_BACKFILL')");
    mysqli_stmt_bind_param($stmt, "iisdds", $user_id, $bill_id, $month, $total_amount, $total_amount, $payment_date);
    if(mysqli_stmt_execute($stmt)) {
        $elec_count++;
    }
}

// Backfill for purely rent bills
$q_rent = mysqli_query($conn, "
    SELECT r.* 
    FROM rent r 
    WHERE r.status = 'Paid' 
    AND NOT EXISTS (
        SELECT 1 FROM payments p 
        WHERE p.user_id = r.user_id 
        AND (
            (p.bill_type = 'rent' AND p.bill_id = r.id)
            OR 
            (p.bill_type = 'monthly' AND p.month = r.month)
        )
    )
");

$rent_count = 0;
while ($r = mysqli_fetch_assoc($q_rent)) {
    $user_id = $r['user_id'];
    $bill_id = $r['id'];
    $month = $r['month'];
    $total_amount = $r['rent_amount'];
    
    // Use paid_date or due_date or current date
    $payment_date = !empty($r['paid_date']) ? $r['paid_date'] : (!empty($r['due_date']) ? $r['due_date'] : date('Y-m-d'));
    
    $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, adjustment_amount, adjustment_type, payment_date, transaction_id) VALUES (?, 'rent', ?, ?, ?, 'Manual', ?, 0, '', ?, 'SYS_BACKFILL')");
    mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total_amount, $total_amount, $payment_date);
    if(mysqli_stmt_execute($stmt)) {
        $rent_count++;
    }
}

echo "Backfill complete. Inserted $elec_count electricity transaction records and $rent_count rent transaction records.";
?>
