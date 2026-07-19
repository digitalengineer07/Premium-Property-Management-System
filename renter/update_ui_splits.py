import os

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-bills_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-bills_mobile.php'
]

target = """        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as combined_amount, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home-heart', 'color' => 'purple'
            ];
        }"""

replacement = """        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            if ($m['dues'] > 0) {
                // Rent + Maint Component
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $m['rent_amount'] + $m['maintenance'], 'status' => $m['status'],
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home-heart', 'color' => 'purple'
                ];
                // Arrears / Dues Component
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'other',
                    'title' => 'Arrears / Remaining', 'subtitle' => 'Carried forward',
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $m['dues'], 'status' => $m['status'],
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-history', 'color' => 'orange'
                ];
            } else {
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $m['rent_amount'] + $m['maintenance'], 'status' => $m['status'],
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home-heart', 'color' => 'purple'
                ];
            }
        }"""

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
