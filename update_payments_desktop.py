import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date,
                                       (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $total_paid = (float)$m['total_paid'];
            $dues_amt = (float)$m['dues'];
            $rent_maint_amt = (float)$m['rent_amount'] + (float)$m['maintenance'];
            $orig_status = $m['status'];
            
            if ($dues_amt > 0) {
                $arrears_remaining = max(0, $dues_amt - $total_paid);
                $paid_for_rent = max(0, $total_paid - $dues_amt);
                $rent_remaining = max(0, $rent_maint_amt - $paid_for_rent);
                
                // Rent + Maint Component
                $r1_status = $orig_status;
                if ($orig_status == 'Partial' && $rent_remaining == 0) {
                    $r1_status = 'Paid';
                }
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $rent_maint_amt, 'remaining_amount' => $rent_remaining, 'status' => $r1_status,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home', 'color' => 'purple'
                ];
                
                // Arrears / Dues Component
                $r2_status = $orig_status;
                if ($orig_status == 'Partial' && $arrears_remaining == 0) {
                    $r2_status = 'Paid';
                }
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'other',
                    'title' => 'Arrears / Remaining', 'subtitle' => 'Carried forward',
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $dues_amt, 'remaining_amount' => $arrears_remaining, 'status' => $r2_status,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-history', 'color' => 'orange'
                ];
            } else {
                $rem = max(0, $rent_maint_amt - $total_paid);
                $st = $orig_status;
                if ($orig_status == 'Partial' && $rem == 0) $st = 'Paid';
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $rent_maint_amt, 'remaining_amount' => $rem, 'status' => $st,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home', 'color' => 'purple'
                ];
            }
        }"""


replacement1 = """        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date,
                                       (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid
                                       FROM electricity e LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p ON p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.extra_charges > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $total_paid = (float)$m['total_paid'];
            // Include dues in rent_maint_amt (it will be negative or 0)
            $dues_amt = (float)$m['dues']; 
            $extra_charges = (float)$m['extra_charges'];
            $rent_maint_amt = (float)$m['rent_amount'] + (float)$m['maintenance'] + $dues_amt; 
            $orig_status = $m['status'];
            
            if ($extra_charges > 0) {
                $extra_remaining = max(0, $extra_charges - $total_paid);
                $paid_for_rent = max(0, $total_paid - $extra_charges);
                $rent_remaining = max(0, $rent_maint_amt - $paid_for_rent);
                
                // Rent + Maint Component
                $r1_status = $orig_status;
                if ($orig_status == 'Partial' && $rent_remaining == 0) {
                    $r1_status = 'Paid';
                }
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $rent_maint_amt, 'remaining_amount' => $rent_remaining, 'status' => $r1_status,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home', 'color' => 'purple'
                ];
                
                // Extra Charges Component
                $r2_status = $orig_status;
                if ($orig_status == 'Partial' && $extra_remaining == 0) {
                    $r2_status = 'Paid';
                }
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'other',
                    'title' => 'Other Charges', 'subtitle' => $m['extra_charges_desc'] ? $m['extra_charges_desc'] : 'Miscellaneous',
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $extra_charges, 'remaining_amount' => $extra_remaining, 'status' => $r2_status,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-receipt', 'color' => 'orange'
                ];
            } else {
                $rem = max(0, $rent_maint_amt - $total_paid);
                $st = $orig_status;
                if ($orig_status == 'Partial' && $rem == 0) $st = 'Paid';
                
                $all_bills[] = [
                    'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                    'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                    'period' => $m['month'],
                    'bill_date' => date('01 M Y', strtotime($m['month'])),
                    'due_date' => date('07 M Y', strtotime($m['month'])),
                    'amount' => $rent_maint_amt, 'remaining_amount' => $rem, 'status' => $st,
                    'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                    'icon' => 'bx-home', 'color' => 'purple'
                ];
            }
        }"""

if target1 in content:
    content = content.replace(target1, replacement1)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-payments_desktop.php")
else:
    print("Target 1 not found")
