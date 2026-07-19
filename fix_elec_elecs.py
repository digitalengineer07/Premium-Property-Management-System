import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-payments.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """// Electricity list (only the usage part)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.units_consumed, e.amount, e.total_amount, e.status, p.adjustment_amount, p.adjustment_type 
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_res = mysqli_stmt_get_result($stmt);
$elecs = []; while ($row = mysqli_fetch_assoc($elec_res)) $elecs[] = $row;
mysqli_stmt_close($stmt);"""

replacement = """// Electricity list (only the usage part)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.units_consumed, e.amount, e.total_amount, e.status, p.adjustment_amount, p.adjustment_type,
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type='electricity' AND bill_id=e.id) as total_paid 
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_res = mysqli_stmt_get_result($stmt);
$elecs = []; 
while ($row = mysqli_fetch_assoc($elec_res)) {
    $rem = max(0, (float)$row['amount'] - (float)$row['total_paid']);
    if ($row['status'] == 'Paid') {
        $rem = 0;
    }
    $row['remaining_amount'] = $rem;
    $elecs[] = $row;
}
mysqli_stmt_close($stmt);"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-payments.php")
else:
    print("Target not found.")
