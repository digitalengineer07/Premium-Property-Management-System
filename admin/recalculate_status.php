<?php
function recalculate_user_status($conn, $user_id) {
    // 1. Recalculate Rent table
    mysqli_query($conn, "
        UPDATE rent r
        SET status = CASE 
            WHEN (r.rent_amount - IFNULL((SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'rent' AND bill_id = r.id), 0)) <= 0 THEN 'Paid'
            WHEN (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'rent' AND bill_id = r.id) > 0 THEN 'Partial'
            ELSE 'Due'
        END
        WHERE r.user_id = $user_id
    ");

    // 2. Recalculate Electricity table (elec_status)
    mysqli_query($conn, "
        UPDATE electricity e
        SET elec_status = CASE 
            WHEN (e.amount - IFNULL((SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = e.id), 0)) <= 0 THEN 'Paid'
            WHEN (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = e.id) > 0 THEN 'Partial'
            ELSE 'Due'
        END
        WHERE e.user_id = $user_id
    ");

    // 3. Recalculate Electricity table (rent_status)
    mysqli_query($conn, "
        UPDATE electricity e
        SET rent_status = CASE 
            WHEN ((e.rent_amount + e.maintenance + e.dues) - IFNULL((SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'elec_rent' AND bill_id = e.id), 0)) <= 0 THEN 'Paid'
            WHEN (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'elec_rent' AND bill_id = e.id) > 0 THEN 'Partial'
            ELSE 'Due'
        END
        WHERE e.user_id = $user_id
    ");
    
    // 4. Recalculate Electricity table (overall status)
    mysqli_query($conn, "
        UPDATE electricity 
        SET status = CASE 
            WHEN elec_status = 'Paid' AND rent_status = 'Paid' THEN 'Paid'
            WHEN elec_status = 'Due' AND rent_status = 'Due' THEN 'Due'
            ELSE 'Partial'
        END
        WHERE user_id = $user_id
    ");
    
    // Also set paid_date to CURDATE() if status changed to Paid today
    mysqli_query($conn, "UPDATE rent SET paid_date = CURDATE() WHERE user_id = $user_id AND status = 'Paid' AND paid_date IS NULL");
    mysqli_query($conn, "UPDATE electricity SET paid_date = CURDATE() WHERE user_id = $user_id AND status = 'Paid' AND paid_date IS NULL");
}

function allocate_lump_sum_payment($conn, $user_id, $amount, $transaction_id, $payment_mode, $month) {
    $remaining = $amount;

    $unpaid_bills = [];

    // Rent bills
    $q_rent = mysqli_query($conn, "SELECT id, 'rent' as type, rent_amount as total_due, (SELECT IFNULL(SUM(paid_amount), 0) FROM payments WHERE bill_type = 'rent' AND bill_id = rent.id) as paid_so_far, month, 'rent' as original_table FROM rent WHERE user_id = $user_id AND status != 'Paid' ORDER BY STR_TO_DATE(CONCAT('1 ', month), '%d %M %Y') ASC");
    if($q_rent) {
        while ($r = mysqli_fetch_assoc($q_rent)) {
            if ($r['total_due'] > $r['paid_so_far']) $unpaid_bills[] = $r;
        }
    }

    // Elec Rent bills
    $q_erent = mysqli_query($conn, "SELECT id, 'elec_rent' as type, (rent_amount + maintenance + dues) as total_due, (SELECT IFNULL(SUM(paid_amount), 0) FROM payments WHERE bill_type = 'elec_rent' AND bill_id = electricity.id) as paid_so_far, month, 'electricity' as original_table FROM electricity WHERE user_id = $user_id AND rent_status != 'Paid' ORDER BY id ASC");
    if($q_erent) {
        while ($r = mysqli_fetch_assoc($q_erent)) {
            if ($r['total_due'] > $r['paid_so_far']) $unpaid_bills[] = $r;
        }
    }

    // Elec Usage bills
    $q_elec = mysqli_query($conn, "SELECT id, 'electricity' as type, amount as total_due, (SELECT IFNULL(SUM(paid_amount), 0) FROM payments WHERE bill_type = 'electricity' AND bill_id = electricity.id) as paid_so_far, month, 'electricity' as original_table FROM electricity WHERE user_id = $user_id AND elec_status != 'Paid' ORDER BY id ASC");
    if($q_elec) {
        while ($r = mysqli_fetch_assoc($q_elec)) {
            if ($r['total_due'] > $r['paid_so_far']) $unpaid_bills[] = $r;
        }
    }

    // Allocate payment
    foreach ($unpaid_bills as $bill) {
        if ($remaining <= 0) break;
        $due = $bill['total_due'] - $bill['paid_so_far'];
        if ($due <= 0) continue;

        $pay_amt = min($remaining, $due);
        $remaining -= $pay_amt;

        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id) VALUES ($user_id, '{$bill['type']}', {$bill['id']}, '{$bill['month']}', {$bill['total_due']}, '$payment_mode', $pay_amt, CURDATE(), '$transaction_id')");
    }

    // If still remaining, dump it as advance
    if ($remaining > 0) {
        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id) VALUES ($user_id, 'advance', 0, '$month', $remaining, '$payment_mode', $remaining, CURDATE(), '$transaction_id')");
    }
}
?>
