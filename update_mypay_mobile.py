import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\my-payments.php'

with open(filepath, 'r') as f:
    content = f.read()


target = """// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.status, p.adjustment_amount, p.adjustment_type,
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $total_paid = (float)$row['total_paid'];
    $rent_maint_amt = (float)$row['rent_amount'] + (float)$row['maintenance'];

    $rem = max(0, $rent_maint_amt - $total_paid);
    $r_status = $row['status'];
    if ($row['status'] == 'Partial' && $rem == 0) $r_status = 'Paid';
    
    $row['amount'] = $rent_maint_amt;
    $row['remaining_amount'] = $rem;
    $row['status'] = $r_status;
    $row['source'] = 'elec_table';
    $row['split_type'] = 'combined';
    $merged_rents[] = $row;
}"""

replacement = """// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, e.status, p.adjustment_amount, p.adjustment_type,
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0 OR e.extra_charges > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $total_paid = (float)$row['total_paid'];
    $extra_charges = (float)$row['extra_charges'];
    $rent_maint_amt = (float)$row['rent_amount'] + (float)$row['maintenance'] + (float)$row['dues'];
    
    if ($extra_charges > 0) {
        $extra_remaining = max(0, $extra_charges - $total_paid);
        $paid_for_rent = max(0, $total_paid - $extra_charges);
        $rent_remaining = max(0, $rent_maint_amt - $paid_for_rent);
        
        $r1_status = $row['status'];
        if ($row['status'] == 'Partial' && $rent_remaining == 0) $r1_status = 'Paid';
        
        $row1 = $row;
        $row1['amount'] = $rent_maint_amt;
        $row1['remaining_amount'] = $rent_remaining;
        $row1['status'] = $r1_status;
        $row1['source'] = 'elec_table';
        $row1['split_type'] = 'combined';
        $merged_rents[] = $row1;
        
        $r2_status = $row['status'];
        if ($row['status'] == 'Partial' && $extra_remaining == 0) $r2_status = 'Paid';
        
        $row2 = $row;
        $row2['amount'] = $extra_charges;
        $row2['remaining_amount'] = $extra_remaining;
        $row2['status'] = $r2_status;
        $row2['source'] = 'elec_table';
        $row2['split_type'] = 'dues_only'; // Keep 'dues_only' string since mobile views might rely on it for styling
        $merged_rents[] = $row2;
        
    } else {
        $rem = max(0, $rent_maint_amt - $total_paid);
        $r_status = $row['status'];
        if ($row['status'] == 'Partial' && $rem == 0) $r_status = 'Paid';
        
        $row['amount'] = $rent_maint_amt;
        $row['remaining_amount'] = $rem;
        $row['status'] = $r_status;
        $row['source'] = 'elec_table';
        $row['split_type'] = 'combined';
        $merged_rents[] = $row;
    }
}"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-payments.php")
else:
    print("Target not found")
