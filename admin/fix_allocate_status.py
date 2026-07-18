import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Fix rent status logic
    old_rent_status = "$new_status = ($total_paid >= $bill_amount - 0.01) ? 'Paid' : 'Partial';"
    new_rent_status = """$new_status = 'Due';
            if ($total_paid >= $bill_amount - 0.01) $new_status = 'Paid';
            elseif ($total_paid > 0) $new_status = 'Partial';"""
            
    content = content.replace(old_rent_status, new_rent_status)

    # Fix elec status logic
    old_elec_status = """            $elec_status = ($total_elec_paid >= $elec_part - 0.01) ? 'Paid' : 'Partial';
            $rent_status = ($total_rent_paid >= $rent_part - 0.01) ? 'Paid' : 'Partial';
            $overall_status = ($elec_status === 'Paid' && $rent_status === 'Paid') ? 'Paid' : 'Partial';"""
            
    new_elec_status = """            $elec_status = 'Due';
            if ($total_elec_paid >= $elec_part - 0.01) $elec_status = 'Paid';
            elseif ($total_elec_paid > 0) $elec_status = 'Partial';
            
            $rent_status = 'Due';
            if ($total_rent_paid >= $rent_part - 0.01) $rent_status = 'Paid';
            elseif ($total_rent_paid > 0) $rent_status = 'Partial';
            
            $overall_status = 'Due';
            if ($elec_status === 'Paid' && $rent_status === 'Paid') $overall_status = 'Paid';
            elseif ($total_elec_paid > 0 || $total_rent_paid > 0) $overall_status = 'Partial';"""
            
    content = content.replace(old_elec_status, new_elec_status)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
        print("Fixed recalculate logic in allocate_payment.php")
