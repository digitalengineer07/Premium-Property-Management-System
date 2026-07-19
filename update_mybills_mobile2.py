import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\my-bills.php'

with open(filepath, 'r') as f:
    content = f.read()


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

        // Component 1: Rent + Maint (goes to 'Rent' tab)
        $r1 = $row;
        if ($row['status'] == 'Paid') {
            $r1['amount'] = $rent_maint_amt;
            $r1['status'] = 'Paid';
        } else {
            $r1['amount'] = $rent_maint_amt;
            $r1['status'] = ($rent_remaining == 0) ? 'Paid' : 'Partial';
        }
        $r1['source'] = 'elec_table';
        $r1['split_type'] = 'rent_only';
        $merged_rents[] = $r1;

        // Component 2: Other charges (goes to 'Other' tab)
        $r2 = $row;
        if ($row['status'] == 'Paid') {
            $r2['amount'] = $extra_charges;
            $r2['status'] = 'Paid';
        } else {
            $r2['amount'] = $extra_charges;
            $r2['status'] = ($extra_remaining == 0) ? 'Paid' : 'Partial';
        }
        $r2['source'] = 'elec_table';
        $r2['split_type'] = 'dues_only'; // We keep 'dues_only' for CSS classes relying on it
        $merged_rents[] = $r2;

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

# regex replacement
pattern = re.compile(r'// Get rent portions from electricity bills \(slips\).*?\$merged_rents\[\] = \$row;\s*\}\s*\}', re.DOTALL)
content = re.sub(pattern, replacement, content)

with open(filepath, 'w') as f:
    f.write(content)
print("Updated my-bills.php")
