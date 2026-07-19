import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-payments.php'

target = """while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $total_paid = (float)$row['total_paid'];
    $dues_amt = (float)$row['dues'];
    $rent_maint_amt = (float)$row['rent_amount'] + (float)$row['maintenance'];

    if ($dues_amt > 0) {
        $arrears_remaining = max(0, $dues_amt - $total_paid);
        $paid_for_rent = max(0, $total_paid - $dues_amt);
        $rent_remaining = max(0, $rent_maint_amt - $paid_for_rent);

        // Component 1: Rent + Maint (goes to 'Rent' tab)
        $r1 = $row;
        $r1_status = $row['status'];
        if ($row['status'] == 'Partial' && $rent_remaining == 0) {
            $r1_status = 'Paid';
        }
        $r1['amount'] = $rent_maint_amt;
        $r1['remaining_amount'] = $rent_remaining;
        $r1['status'] = $r1_status;
        $r1['source'] = 'elec_table';
        $r1['split_type'] = 'rent_only';
        $merged_rents[] = $r1;
        
        // Component 2: Dues (goes to 'Other Charges' tab)
        $r2 = $row;
        $r2_status = $row['status'];
        if ($row['status'] == 'Partial' && $arrears_remaining == 0) {
            $r2_status = 'Paid';
        }
        $r2['amount'] = $dues_amt;
        $r2['remaining_amount'] = $arrears_remaining;
        $r2['status'] = $r2_status;
        $r2['source'] = 'elec_table';
        $r2['split_type'] = 'dues_only';
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

replacement = """while ($row = mysqli_fetch_assoc($elec_rent_res)) {
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

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")
