import os

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\my-payments.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php'
]

target = """// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as amount, e.status, p.adjustment_amount, p.adjustment_type 
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $row['source'] = 'elec_table';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);"""

replacement = """// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.status, p.adjustment_amount, p.adjustment_type 
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    if ($row['dues'] > 0) {
        // Component 1: Rent + Maint (goes to 'Rent' tab)
        $r1 = $row;
        $r1['amount'] = $row['rent_amount'] + $row['maintenance'];
        $r1['source'] = 'elec_table';
        $r1['split_type'] = 'rent_only';
        $merged_rents[] = $r1;
        
        // Component 2: Dues (goes to 'Other Charges' tab)
        $r2 = $row;
        $r2['amount'] = $row['dues'];
        $r2['source'] = 'elec_table';
        $r2['split_type'] = 'dues_only';
        $merged_rents[] = $r2;
    } else {
        $row['amount'] = $row['rent_amount'] + $row['maintenance'];
        $row['source'] = 'elec_table';
        $row['split_type'] = 'combined';
        $merged_rents[] = $row;
    }
}
mysqli_stmt_close($stmt);"""

for filepath in files_to_update:
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
