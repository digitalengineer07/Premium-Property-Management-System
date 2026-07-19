import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-bills_desktop.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, (e.rent_amount + e.maintenance + e.dues) as combined_amount, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : ($m['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $m['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $m['rent_amount'],
                    'Maintenance Charge' => $m['maintenance'],
                    'Other Charges' => $m['dues']
                ]
            ];
        }"""

replacement1 = """        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0 OR e.extra_charges > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $rent_maint_amt = (float)$m['rent_amount'] + (float)$m['maintenance'] + (float)$m['dues'];
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : ($m['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $m['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $rent_maint_amt, 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $m['rent_amount'],
                    'Maintenance Charge' => $m['maintenance']
                ]
            ];
            
            if ((float)$m['extra_charges'] > 0) {
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : ($m['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                    'title' => 'Other Charges', 'subtitle' => $m['extra_charges_desc'] ? $m['extra_charges_desc'] : 'Miscellaneous',
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => (float)$m['extra_charges'], 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-receipt', 'color' => 'orange',
                    'summary' => [
                        'Other Charges' => $m['extra_charges']
                    ]
                ];
            }
        }"""

if target1 in content:
    content = content.replace(target1, replacement1)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-bills_desktop.php")
else:
    print("Target 1 not found")
