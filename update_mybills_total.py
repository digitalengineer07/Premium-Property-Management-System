import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-bills.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """    IFNULL(SUM(
        CASE WHEN rent_status IN ('Due', 'Partial') OR (rent_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND rent_status != 'Paid')
        THEN (rent_amount + maintenance) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as rent_portion_total"""

replacement = """    IFNULL(SUM(
        CASE WHEN rent_status IN ('Due', 'Partial') OR (rent_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND rent_status != 'Paid')
        THEN (rent_amount + maintenance + dues + extra_charges) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as rent_portion_total"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-bills.php total_due query")
else:
    print("Target not found")
