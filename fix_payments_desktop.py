import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => 'electricity',
                'title' => 'Electricity', 'subtitle' => 'Units: ' . $e['units_consumed'],
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 'status' => $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow'
            ];
        }"""

replacement = """        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date,
                                       (SELECT SUM(paid_amount) FROM payments WHERE bill_type='electricity' AND bill_id=e.id) as total_paid
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $rem = max(0, (float)$e['amount'] - (float)$e['total_paid']);
            if ($e['status'] == 'Paid') $rem = 0;
            
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => 'electricity',
                'title' => 'Electricity', 'subtitle' => 'Units: ' . $e['units_consumed'],
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 
                'remaining_amount' => $rem,
                'status' => $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow'
            ];
        }"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Successfully replaced in my-payments_desktop.php")
else:
    print("Target not found.")
